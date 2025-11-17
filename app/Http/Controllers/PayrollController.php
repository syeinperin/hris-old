<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\SssContribution;
use App\Models\PhilhealthContribution;
use App\Models\PagibigContribution;
use App\Models\Loan;
use App\Models\Holiday;
use App\Models\Payslip;
use App\Services\PayrollCalculator;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use PDF;

class PayrollController extends Controller
{
    /** ----------------------------
     *  HELPER: LEAVE + HOLIDAY MAPS
     * ---------------------------- */
    private function leaveIndex(Carbon $start, Carbon $end)
    {
        $leaves = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->get();

        $index = collect();

        foreach ($leaves as $lv) {
            $from = Carbon::parse($lv->start_date)->max($start);
            $to   = Carbon::parse($lv->end_date)->min($end);
            foreach (CarbonPeriod::create($from, $to) as $day) {
                $index[$lv->employee_id][$day->toDateString()] = true;
            }
        }

        return $index;
    }

    private function holidaySetMap(Carbon $start, Carbon $end): array
    {
        return Holiday::whereBetween('date', [$start, $end])
            ->pluck('name', 'date')
            ->toArray();
    }

    
    /** ----------------------------
     *  PAYROLL CALENDAR
     * ---------------------------- */
    public function calendar(Request $request)
    {
        $search = $request->input('search', '');
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $start = Carbon::parse("$month-01")->startOfMonth();
        $end = $start->copy()->endOfMonth();

$employees = Employee::where('status', 'active')
    ->when($search, fn($q, $s) =>
        $q->where('employee_code', 'like', "%$s%")
          ->orWhere('name', 'like', "%$s%"))
    ->tap(fn($q) => $this->excludeSelf($q))
    ->with(['designation', 'schedule'])
    ->orderBy('name')
    ->get();



        $attendance = Attendance::whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('time_in', [$start, $end])
            ->get()
            ->groupBy('employee_id')
            ->map(fn($g) => $g->groupBy(fn($r) => Carbon::parse($r->time_in)->toDateString()));

        return view('payroll.calendar', [
            'employees' => $employees,
            'attendance' => $attendance,
            'start' => $start,
            'end' => $end,
            'search' => $search,
            'month' => $month,
        ]);
    }

    /** ----------------------------
     *  PAYROLL INDEX (SUMMARY)
     * ---------------------------- */
    public function index(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $search = $request->input('search', '');

$employees = Employee::where('status', 'active')
    ->when($search, fn($q, $s) =>
        $q->where('employee_code', 'like', "%$s%")
          ->orWhere('name', 'like', "%$s%"))
    ->tap(fn($q) => $this->excludeSelf($q))
    ->with(['designation', 'schedule'])
    ->orderBy('name')
    ->get();


        $calculator = new PayrollCalculator();
        $rows = [];

        foreach ($employees as $emp) {
            $computed = $calculator->compute($emp, Carbon::parse($date), Carbon::parse($date));
            $rows[] = [
                'employee_id' => $emp->id,
                'employee_code' => $emp->employee_code,
                'employee_name' => $emp->name,
                'net_pay' => $computed['net'],
            ];
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $slice = array_slice($rows, ($page - 1) * $perPage, $perPage);
        $paginator = new LengthAwarePaginator($slice, count($rows), $perPage, $page, [
            'path' => route('payroll.index'),
            'query' => $request->query(),
        ]);

        return view('payroll.index', compact('paginator', 'date', 'search'));
    }

    /** ----------------------------
     *  SHOW INDIVIDUAL PAYROLL
     * ---------------------------- */
    public function show(Request $request, $employeeId)
    {
        $employee = Employee::with(['user', 'designation', 'schedule'])->findOrFail($employeeId);

        $month = $request->input('month', now()->format('Y-m'));
        $from = Carbon::parse("$month-01")->startOfMonth();
        $to = $from->copy()->endOfMonth();

        $payslips = Payslip::where('user_id', $employee->user_id)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('period_start', [$from, $to])
                  ->orWhereBetween('period_end', [$from, $to])
                  ->orWhereBetween('date', [$from, $to])
                  ->orWhereNull('period_start');
            })
            ->orderBy('date', 'asc')
            ->get()
->keyBy(function ($p) {
    if ($p->date) return Carbon::parse($p->date)->toDateString();
    if ($p->period_start) return Carbon::parse($p->period_start)->toDateString();
    if ($p->period_end) return Carbon::parse($p->period_end)->toDateString();
    return null;
});

        $rows = [];
        $calculator = new PayrollCalculator();

        foreach (CarbonPeriod::create($from, $to) as $day) {
            $dateStr = $day->toDateString();
            $computed = $calculator->compute($employee, $day, $day);

            $row = [
                'date' => $dateStr,
                'worked_hr' => $computed['worked_hours'],
                'ot_hr' => $computed['ot_hours'],
                'ot_pay' => $computed['ot_pay'],
                'nd_hr' => $computed['nd_hours'],
                'nd_pay' => $computed['nd_pay'],
                'holiday_pay' => $computed['holiday_pay'],
                'late' => $computed['late_deduction'],
                'loan' => $computed['loan_deduction'],
                'govt_deduction' => $computed['govt_deduction'],
                'gross' => $computed['gross'],
                'net' => $computed['net'],
            ];

// Combine with manual entry if exists
if ($payslips->has($dateStr)) {
    $manual = $payslips[$dateStr];
    foreach (['worked_hr','ot_hr','ot_pay','nd_hr','nd_pay','holiday_pay','late','loan','govt_deduction','gross','net'] as $field) {
        $map = [
            'worked_hr'=>'worked_hours','ot_hr'=>'ot_hours','ot_pay'=>'ot_pay','nd_hr'=>'nd_hours','nd_pay'=>'nd_pay',
            'holiday_pay'=>'holiday_pay','late'=>'late_deduction','loan'=>'personal_loan','govt_deduction'=>'govt_deduction',
            'gross'=>'gross_amount','net'=>'net_amount'
        ];
        
        // ✅ Manual values override system-generated values
        if (!is_null($manual->{$map[$field]})) {
            $row[$field] = $manual->{$map[$field]};
        }
    }
}

            $rows[] = $row;
        }

        $firstRows = array_filter($rows, fn($r) => (int)Carbon::parse($r['date'])->day <= 15);
        $secondRows = array_filter($rows, fn($r) => (int)Carbon::parse($r['date'])->day > 15);

        return view('payroll.show', compact('employee', 'rows', 'firstRows', 'secondRows', 'month', 'from', 'to'));
    }

    /** ----------------------------
     *  EDIT MANUAL PAYROLL
     * ---------------------------- */
  public function edit(Request $request, $employeeId)
{
    $employee = Employee::with(['user', 'designation', 'schedule'])->findOrFail($employeeId);
    $date = Carbon::parse($request->query('date'))->toDateString();

    // 🧮 1️⃣ Compute the system-generated data
    $calculator = new \App\Services\PayrollCalculator();
    $computed = $calculator->compute($employee, Carbon::parse($date), Carbon::parse($date));

    // 🧾 2️⃣ Try to fetch any existing manual payslip for this date
    $payslip = Payslip::where('user_id', $employee->user_id)
        ->where(function ($q) use ($date) {
            $q->whereDate('date', $date)
              ->orWhere(function ($x) use ($date) {
                  $x->whereDate('period_start', '<=', $date)
                    ->whereDate('period_end', '>=', $date);
              });
        })
        ->orderByDesc('date')
        ->first();

    // 🧩 3️⃣ Merge system-generated + manual (if any)
    $data = [
        'date'             => $date,
        'worked_hours'     => $computed['worked_hours'] ?? 0,
        'ot_hours'         => $computed['ot_hours'] ?? 0,
        'ot_pay'           => $computed['ot_pay'] ?? 0,
        'nd_hours'         => $computed['nd_hours'] ?? 0,
        'nd_pay'           => $computed['nd_pay'] ?? 0,
        'holiday_pay'      => $computed['holiday_pay'] ?? 0,
        'late_deduction'   => $computed['late_deduction'] ?? 0,
        'personal_loan'    => $computed['loan_deduction'] ?? 0,
        'govt_deduction'   => $computed['govt_deduction'] ?? 0,
        'gross_amount'     => $computed['gross'] ?? 0,
        'net_amount'       => $computed['net'] ?? 0,
        'remarks'          => '',
    ];

    if ($payslip) {
        // Merge manual values, giving priority to manual
        foreach ($data as $key => $value) {
            if (!empty($payslip->$key) && $payslip->$key > 0) {
                $data[$key] = $payslip->$key;
            }
        }
    }

    // 🧱 4️⃣ Create a temporary model for the view
    $mergedPayslip = new Payslip($data);
    $mergedPayslip->id = $payslip->id ?? null;
    $mergedPayslip->exists = (bool) $payslip;

    // ✅ Pass everything to the Blade view
    return view('payroll.edit', [
        'employee' => $employee,
        'payslip'  => $mergedPayslip,
        'date'     => $date,
    ]);
}

    /** ----------------------------
     *  STORE MANUAL PAYROLL
     * ---------------------------- */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'worked_hours' => 'nullable|numeric|min:0',
            'ot_hours' => 'nullable|numeric|min:0',
            'ot_pay' => 'nullable|numeric|min:0',
            'nd_hours' => 'nullable|numeric|min:0',
            'nd_pay' => 'nullable|numeric|min:0',
            'holiday_pay' => 'nullable|numeric|min:0',
            'late_deduction' => 'nullable|numeric|min:0',
            'personal_loan' => 'nullable|numeric|min:0',
            'govt_deduction' => 'nullable|numeric|min:0',
            'gross_amount' => 'nullable|numeric|min:0',
            'net_amount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:255',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        foreach (['worked_hours','ot_hours','ot_pay','nd_hours','nd_pay','holiday_pay','late_deduction','personal_loan','govt_deduction','gross_amount','net_amount'] as $f) {
            $validated[$f] = $validated[$f] ?? 0;
        }

        $validated['user_id'] = $employee->user_id;
        $validated['source'] = 'manual';
        $validated['date'] = Carbon::parse($validated['date'])->toDateString();
        $validated['period_start'] = Carbon::parse($validated['date'])->startOfDay();
        $validated['period_end'] = Carbon::parse($validated['date'])->endOfDay();

        Payslip::create($validated);

        return redirect()->route('payroll.show', $employee->id)->with('success', '✅ Manual payslip added successfully.');
    }

    /** ----------------------------
     *  UPDATE MANUAL PAYROLL
     * ---------------------------- */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'worked_hours' => 'nullable|numeric|min:0',
            'ot_hours' => 'nullable|numeric|min:0',
            'ot_pay' => 'nullable|numeric|min:0',
            'nd_hours' => 'nullable|numeric|min:0',
            'nd_pay' => 'nullable|numeric|min:0',
            'holiday_pay' => 'nullable|numeric|min:0',
            'late_deduction' => 'nullable|numeric|min:0',
            'personal_loan' => 'nullable|numeric|min:0',
            'govt_deduction' => 'nullable|numeric|min:0',
            'gross_amount' => 'nullable|numeric|min:0',
            'net_amount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:255',
        ]);

        $payslip = Payslip::findOrFail($id);
        foreach (['worked_hours','ot_hours','ot_pay','nd_hours','nd_pay','holiday_pay','late_deduction','personal_loan','govt_deduction','gross_amount','net_amount'] as $f) {
            $validated[$f] = $validated[$f] ?? 0;
        }

        $payslip->update([
            'date' => Carbon::parse($validated['date'])->toDateString(),
            'period_start' => Carbon::parse($validated['date'])->startOfDay(),
            'period_end' => Carbon::parse($validated['date'])->endOfDay(),
            'worked_hours' => $validated['worked_hours'],
            'ot_hours' => $validated['ot_hours'],
            'ot_pay' => $validated['ot_pay'],
            'nd_hours' => $validated['nd_hours'],
            'nd_pay' => $validated['nd_pay'],
            'holiday_pay' => $validated['holiday_pay'],
            'late_deduction' => $validated['late_deduction'],
            'personal_loan' => $validated['personal_loan'],
            'govt_deduction' => $validated['govt_deduction'],
            'gross_amount' => $validated['gross_amount'],
            'net_amount' => $validated['net_amount'],
            'remarks' => $validated['remarks'],
        ]);

    return redirect()->route('payroll.show', $payslip->employee_id)
    ->with('success', '✅ Payroll entry updated successfully.');
    }
    /** ----------------------------
     *  DELETE MANUAL PAYSLIP
     * ---------------------------- */
    public function deletePayslip($id)
    {
        $payslip = Payslip::findOrFail($id);
        if ($payslip->source !== 'manual') {
            return back()->with('warning', '❌ You can only delete manual payslips.');
        }

        $payslip->delete();
        return back()->with('success', '🗑 Manual payslip deleted successfully.');
    }

    /** PAGE: /reports/payslips/list */
public function reportPayslips(Request $request)
{
    $from = Carbon::parse($request->input('from', now()->startOfMonth()))->startOfDay();
    $to   = Carbon::parse($request->input('to', now()->endOfMonth()))->endOfDay();

$employees = Employee::query()
    ->select('id', 'name', 'employee_code')
->tap(fn($q) => $this->excludeSelf($q))

    ->withCount(['attendances' => function ($q) use ($from, $to) {
        $q->whereBetween('time_in', [$from, $to]);
    }])
    ->orderBy('name')
    ->paginate(15);


    $employees->getCollection()->transform(function ($e) {
        $e->days_worked = $e->attendances_count;
        return $e;
    });

    return view('reports.payslips', [
        'employees' => $employees,
        'from'      => $from->toDateString(),
        'to'        => $to->toDateString(),
    ]);
}

/**
 * Remove the currently logged-in HR/Supervisor from any Employee query.
 */
private function excludeSelf($query)
{
    $user = auth()->user();
    if ($user && $user->hasRole(['hr', 'supervisor']) && $user->employee) {
        $query->where('id', '!=', $user->employee->id);
    }
}


}
