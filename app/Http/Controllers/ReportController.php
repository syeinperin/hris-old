<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

// Models
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\LeaveRequest; 
use App\Models\PerformanceEvaluation;
use App\Models\DisciplinaryAction;
use App\Models\Loan;
use App\Models\SssContribution;
use App\Models\PhilhealthContribution;
use App\Models\PagibigContribution;

// PDF
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class ReportController extends Controller
{
    /** Custom paper size (points). 72 pt = 1 in. 4.25in × 11in => 306 × 792. */
     private const PAYSHEET_SIZE = [0, 0, 306, 792];

    /** ✅ Clear any buffered output before sending a streamed CSV */
    private function cleanBuffers(): void
    {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
    }

    /** Small helper for decimal hours between two datetimes (handles cross-midnight). */
    private function hoursBetween(Carbon $in, Carbon $out): float
    {
        if ($out->lt($in)) {
            $out = $out->copy()->addDay();
        }
        // seconds → hours (decimal)
        return round($in->diffInSeconds($out) / 3600, 4);
    }

    /** GET /reports */
    public function index()
    {
        return view('reports.index');
    }

    /** GET /reports/employees */
    public function indexEmployees()
    {
        $employees = Employee::with('department','designation')->orderBy('name')->get();
        return view('reports.employees.index', compact('employees'));
    }

    /** GET /reports/employees/csv */
    public function exportEmployees(): StreamedResponse
    {
        $employees = Employee::orderBy('name')->get();
        $columns   = ['Code','Name','Email','Department','Position'];

        return new StreamedResponse(function() use ($employees, $columns) {
            // UTF-8 BOM for Excel
            echo "\xEF\xBB\xBF";
            $fp = fopen('php://output','w');
            fputcsv($fp, $columns);

            foreach ($employees as $e) {
                fputcsv($fp, [
                    $e->employee_code,
                    $e->name,
                    $e->email,
                    optional($e->department)->name,
                    optional($e->designation)->name,
                ]);
            }
            fclose($fp);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
            'Content-Disposition' => 'attachment; filename="employees.csv"',
        ]);
    }


/** PAGE: /reports/payslips/list */
public function reportPayslips(Request $request)
{
    $from = Carbon::parse($request->input('from', now()->startOfMonth()))->startOfDay();
    $to   = Carbon::parse($request->input('to', now()->endOfMonth()))->endOfDay();

    $employees = Employee::query()
        ->select('id', 'name', 'employee_code')
        ->withCount(['attendances' => function ($q) use ($from, $to) {
            $q->whereBetween('time_in', [$from, $to]);
        }])
        ->orderBy('name')
        ->paginate(15);

    $employees->getCollection()->transform(function ($e) {
        $e->days_worked = $e->attendances_count;
        return $e;
    });

    return view('reports.payslips.list', [
        'employees' => $employees,
        'from'      => $from->toDateString(),
        'to'        => $to->toDateString(),
    ]);
}


    /** GET /reports/employees/{employee}/pdf */
    public function downloadEmployeePdf(Employee $employee)
    {
        $pdf = PDF::loadView('reports.pdf.employee_sheet', compact('employee'))
                  ->setPaper('A4', 'portrait');

        return $pdf->stream("employee_{$employee->employee_code}.pdf");
    }

    /** GET /reports/employees/{employee}/cert */
    public function downloadCertificate(Employee $employee)
    {
        $pdf = PDF::loadView('reports.pdf.certificate', compact('employee'))
                  ->setPaper('A4', 'portrait');

        return $pdf->stream("certificate_{$employee->employee_code}.pdf");
    }

public function exportAttendance(Request $request)
{
    try {
        while (ob_get_level() > 0) ob_end_clean();

        $from = Carbon::parse($request->input('from', now()->startOfMonth()));
        $to   = Carbon::parse($request->input('to', now()->endOfMonth()));

        $filename = 'attendance_' . $from->format('Ymd') . '_to_' . $to->format('Ymd') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ];

        // =============== Inline helper functions ===============
        $buildLeaveIndex = function (string $startDate, string $endDate) {
            $leaves = \App\Models\LeaveRequest::where('status', 'approved')
                ->whereDate('start_date', '<=', $endDate)
                ->whereDate('end_date', '>=', $startDate)
                ->get();

            $idx = [];
            foreach ($leaves as $lv) {
                $from = Carbon::parse($lv->start_date)->max($startDate);
                $to   = Carbon::parse($lv->end_date)->min($endDate);
                foreach (CarbonPeriod::create($from, $to) as $d) {
                    $idx[$lv->employee_id][$d->toDateString()] = $lv;
                }
            }
            return $idx;
        };

        $buildDisciplineIndex = function (string $startDate, string $endDate) {
            $start = Carbon::parse($startDate);
            $end   = Carbon::parse($endDate);

            $acts = \App\Models\DisciplinaryAction::where(function ($q) use ($start, $end) {
                    $q->where(function ($qq) use ($start, $end) {
                        $qq->where('action_type', 'suspension')
                           ->whereDate('start_date', '<=', $end->toDateString())
                           ->whereDate('end_date', '>=', $start->toDateString());
                    })->orWhere(function ($qq) use ($start, $end) {
                        $qq->where('action_type', 'violation')
                           ->whereDate(DB::raw('COALESCE(start_date, created_at)'), '>=', $start->toDateString())
                           ->whereDate(DB::raw('COALESCE(start_date, created_at)'), '<=', $end->toDateString());
                    });
                })
                ->get();

            $susp = [];
            $viol = [];
            foreach ($acts as $a) {
                if ($a->action_type === 'suspension' && $a->start_date && $a->end_date) {
                    for ($d = $a->start_date->copy(); $d->lte($a->end_date); $d->addDay()) {
                        if ($d->lt($start) || $d->gt($end)) continue;
                        $susp[$a->employee_id][$d->toDateString()] = $a;
                    }
                } else {
                    $d = optional($a->start_date)->toDateString() ?? $a->created_at->toDateString();
                    $viol[$a->employee_id][$d][] = $a;
                }
            }
            return ['suspensions' => $susp, 'violations' => $viol];
        };

        // =============== Prepare data ===============
        $leaveIndex = $buildLeaveIndex($from->toDateString(), $to->toDateString());
        $discipline = $buildDisciplineIndex($from->toDateString(), $to->toDateString());
        $employees = \App\Models\Employee::with('schedule')
            ->where('status', 'active')
            ->orderBy('employee_code')
            ->get();

        $callback = function () use ($employees, $from, $to, $leaveIndex, $discipline) {
            $fp = fopen('php://output', 'w');
            fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            fputcsv($fp, [
                'Date',
                'Employee Code',
                'Employee Name',
                'Time In',
                'Time Out',
                'Worked Hours',
                'Late (hrs)',
                'Overtime (hrs)',
                'Status',
            ]);

            foreach (CarbonPeriod::create($from, $to) as $day) {
                $dateStr = $day->toDateString();

                foreach ($employees as $emp) {
                    // Leave
                    if (!empty($leaveIndex[$emp->id][$dateStr])) {
                        $lv = $leaveIndex[$emp->id][$dateStr];
                        fputcsv($fp, [
                            $dateStr, $emp->employee_code, $emp->name,
                            '—','—','—','—','—',
                            'On Leave (' . ucwords(str_replace('_',' ',$lv->leave_type)) . ')'
                        ]);
                        continue;
                    }

                    // Suspension
                    if (!empty($discipline['suspensions'][$emp->id][$dateStr])) {
                        fputcsv($fp, [
                            $dateStr, $emp->employee_code, $emp->name,
                            '—','—','—','—','—','Suspended'
                        ]);
                        continue;
                    }

                    $att = \App\Models\Attendance::where('employee_id', $emp->id)
                        ->whereDate('time_in', $dateStr)
                        ->first();

                    $sched = $emp->schedule;
                    $status = 'Absent';
                    $workedHrs = $otHrs = $lateHrs = 0.0;
                    $timeIn = $timeOut = '—';

                    if ($att) {
                        $in = Carbon::parse($att->time_in);
                        $out = $att->time_out ? Carbon::parse($att->time_out) : null;
                        if ($out && $out->lt($in)) $out->addDay();

                        $timeIn = $in->format('H:i:s');
                        $timeOut = $out ? $out->format('H:i:s') : '—';
                        $workedHrs = $out ? round($in->diffInMinutes($out) / 60, 2) : 0;

                        $sIn = $sOut = null;
                        if ($sched && $sched->time_in) {
                            $sIn  = Carbon::parse($sched->time_in)->setDateFrom($day);
                            $sOut = Carbon::parse($sched->time_out)->setDateFrom($day);
                            if ($sOut->lte($sIn) && $sOut->hour < 6) $sOut->addDay();
                        }

                        $schedHrs = $sIn && $sOut ? round($sIn->diffInSeconds($sOut) / 3600, 2) : 0;
                        $otHrs = ($schedHrs > 0 && $workedHrs > $schedHrs)
                            ? round($workedHrs - $schedHrs, 2)
                            : 0;

                        // --- Determine Status ---
                        $status = 'On Time';
                        if ($sched && $sIn && $in->gt($sIn) && $in->lt($sOut)) {
                            $status = 'Late';
                            $minsLate = $sIn->diffInMinutes($in);
                            $lateHrs = round(ceil($minsLate / 15) * 0.25, 2);
                        } elseif ($sched && $out && $sOut && $out->lt($sOut) && $workedHrs >= 0.5) {
                            $status = 'Undertime'; // ✅ added undertime status
                        } elseif ($in->gte($sOut)) {
                            $status = 'Absent';
                        }

                        // Violations
                        if (!empty($discipline['violations'][$emp->id][$dateStr])) {
                            $status .= ' (Violation)';
                        }
                    }

                    fputcsv($fp, [
                        $dateStr,
                        $emp->employee_code,
                        $emp->name,
                        $timeIn,
                        $timeOut,
                        $workedHrs,
                        $lateHrs,
                        $otHrs,
                        $status,
                    ]);
                }
            }

            fclose($fp);
        };

        return response()->stream($callback, 200, $headers);
    } catch (\Throwable $e) {
        return response("Error generating CSV: " . $e->getMessage(), 500)
            ->header('Content-Type', 'text/plain');
    }
}


/** GET /reports/leaves */
/** GET /reports/leaves */
public function exportLeaves(Request $request)
{
    $from = Carbon::parse($request->input('from'))->startOfDay();
    $to   = Carbon::parse($request->input('to'))->endOfDay();

    // ✅ Use the corrected approver relation
    $leaves = LeaveRequest::with(['employee', 'supervisor', 'approvals.approver', 'type'])
        ->whereBetween('start_date', [$from, $to])
        ->orderBy('start_date', 'asc')
        ->get();

    $headers = [
        'Employee Code',
        'Employee Name',
        'Leave Type',
        'Start Date',
        'End Date',
        'Total Days',
        'Reason',
        'Status',
        'Approved By',
        'Date Approved',
    ];

    $rows = [];

    foreach ($leaves as $leave) {
        $approvedBy = '-';
        $approvedDate = '-';

        if ($leave->status === 'approved') {
            // ✅ 1. Use Approval table if exists
            if ($leave->approvals && $leave->approvals->isNotEmpty()) {
                $approval = $leave->approvals->sortByDesc('created_at')->first();
                $approvedBy = optional($approval->approver)->name ?? 'System';
                $approvedDate = optional($approval->created_at)->format('d/m/Y') ?? '-';

            // ✅ 2. Fallback to supervisor
            } elseif ($leave->supervisor) {
                $approvedBy = $leave->supervisor->name ?? 'Supervisor';
                $approvedDate = optional($leave->updated_at)->format('d/m/Y') ?? '-';

            // ✅ 3. Fallback to updated_by (if exists)
            } elseif (isset($leave->updated_by)) {
                $user = \App\Models\User::find($leave->updated_by);
                $approvedBy = $user?->name ?? 'HR Admin';
                $approvedDate = optional($leave->updated_at)->format('d/m/Y') ?? '-';
            }
        }

       $rows[] = [
    optional($leave->employee)->employee_code ?? '-',
    optional($leave->employee)->name ?? '-',
    optional($leave->type)->name ?? '-',
    optional($leave->start_date)->format('d/m/Y'),
    optional($leave->end_date)->format('d/m/Y'),
    ($leave->start_date && $leave->end_date)
        ? \Carbon\Carbon::parse($leave->start_date)->diffInDays(\Carbon\Carbon::parse($leave->end_date)) + 1
        : '-',
    $leave->reason ?? '-',
    ucfirst($leave->status),
    $approvedBy,
    $approvedDate,
];

    }

    $filename = 'leave_report_' . now()->format('Ymd_His') . '.csv';

    return response()->streamDownload(function () use ($headers, $rows) {
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
        fputcsv($output, $headers);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
    }, $filename, [
        'Content-Type' => 'text/csv',
        'Cache-Control' => 'no-store, no-cache',
    ]);
}



 /** ===================== FIXED PAYROLL REPORT ===================== */
public function exportPayroll(Request $request): StreamedResponse
{
    if (!auth()->check()) abort(403);

    $from = Carbon::parse($request->input('from', now()->startOfMonth()))->startOfDay();
    $to   = Carbon::parse($request->input('to', now()->endOfMonth()))->endOfDay();

    $sssBr     = Cache::remember('sss_brackets', now()->addDay(), fn()=> SssContribution::all());
    $philBr    = Cache::remember('phil_brackets', now()->addDay(), fn()=> PhilhealthContribution::all());
    $pagibigBr = Cache::remember('pagibig_brackets', now()->addDay(), fn()=> PagibigContribution::all());
    $findBr    = fn($col, $g)=> $col->first(fn($b)=> $b->range_min <= $g && $b->range_max >= $g);

    $employees = Employee::with(['designation','schedule'])
        ->where('status','active')
        ->orderBy('employee_code')
        ->get();

    $columns = [
        'Period',
        'Employee Code',
        'Employee Name',
        'Worked Hours',
        'OT Hours',
        'ND Hours',
        'Base Pay',
        'OT Pay',
        'ND Pay',
        'Holiday Pay',
        'Late Deduction',
        'Loan Deduction',
        'Govt Deduction',
        'Gross Pay',
        'Net Pay',
        'Created At',
    ];

    return new StreamedResponse(function () use ($employees, $columns, $from, $to, $sssBr, $philBr, $pagibigBr, $findBr) {
        echo "\xEF\xBB\xBF";
        $fp = fopen('php://output', 'w');
        fputcsv($fp, $columns);

        foreach ($employees as $emp) {
            $rate = (float) ($emp->designation->rate_per_hour ?? 0);
            $loan = (float) Loan::where('employee_id', $emp->id)
                        ->where('status', 'active')
                        ->sum('monthly_amount');

            $attendances = Attendance::where('employee_id', $emp->id)
                ->whereBetween('time_in', [$from, $to])
                ->whereNotNull('time_out')
                ->get();

            $worked = $otHr = $ndHr = $holidayPay = 0.0;

            foreach ($attendances as $att) {
                $in = Carbon::parse($att->time_in);
                $out = Carbon::parse($att->time_out);
                if ($out->lt($in)) $out->addDay();

                $workHr = round($in->diffInMinutes($out) / 60, 2);
                $worked += $workHr;

                // Overtime
                if ($emp->schedule && $emp->schedule->time_in && $emp->schedule->time_out) {
                    $sIn  = Carbon::parse($emp->schedule->time_in)->setDateFrom($in);
                    $sOut = Carbon::parse($emp->schedule->time_out)->setDateFrom($in);
                    if ($sOut->lte($sIn)) $sOut->addDay();
                    $schedHrs = round($sIn->diffInMinutes($sOut) / 60, 2);
                    $otHr += max(0, $workHr - $schedHrs);
                }

                // Night Differential 10PM–6AM
                $ndStart = $in->copy()->setTime(22, 0);
                $ndEnd   = $in->copy()->setTime(6, 0)->addDay();
                $startND = $in->gt($ndStart) ? $in : $ndStart;
                $endND   = $out->lt($ndEnd) ? $out : $ndEnd;
                if ($endND->gt($startND)) {
                    $ndHr += round($startND->diffInMinutes($endND) / 60, 2);
                }

                // Holiday Pay
                $holiday = DB::table('holidays')->whereDate('date', $in->toDateString())->first();
                if ($holiday) {
                    $type = strtolower($holiday->type);
                    if ($type === 'regular') $holidayPay += ($rate * 8) * 2.00;
                    elseif ($type === 'special') $holidayPay += ($rate * 8) * 1.30;
                    elseif ($type === 'double') $holidayPay += ($rate * 8) * 3.00;
                }
            }

// --- Accurate computation (matches payroll view) ---

// Base pay covers up to 8 hours per day
$regularHours = min($worked, 8);
$basePay = round($regularHours * $rate, 2);

// Overtime (anything beyond 8 hr)
$otPay = round($otHr * $rate * 1.25, 2);

// Night differential = 10% of *base + OT* hours worked within ND window
$ndPay = round(($regularHours + $otHr) * $rate * 0.10 * ($ndHr > 0 ? 1 : 0), 2);

// Combine everything
$gross = round($basePay + $otPay + $ndPay + $holidayPay, 2);


            // Gov deductions
            $sss  = (float) ($findBr($sssBr, $gross)->employee_share ?? 0);
            $phil = round($gross * (($findBr($philBr, $gross)->rate_percent ?? 0) / 100) / 2, 2);
            $pag  = (float) ($findBr($pagibigBr, $gross)->employee_share ?? 0);
            $govtDeduction = round($sss + $phil + $pag, 2);

            // Late deduction placeholder (0 for now)
            $lateDeduction = 0;

            // Net
            $deductions = round($lateDeduction + $loan + $govtDeduction, 2);
            $net = round($gross - $deductions, 2);

            // Write CSV row
            fputcsv($fp, [
                $from->format('M d') . ' - ' . $to->format('M d, Y'),
                $emp->employee_code,
                $emp->name,
                number_format($worked, 2),
                number_format($otHr, 2),
                number_format($ndHr, 2),
                '₱' . number_format($basePay, 2),
                '₱' . number_format($otPay, 2),
                '₱' . number_format($ndPay, 2),
                '₱' . number_format($holidayPay, 2),
                '₱' . number_format($lateDeduction, 2),
                '₱' . number_format($loan, 2),
                '₱' . number_format($govtDeduction, 2),
                '₱' . number_format($gross, 2),
                '₱' . number_format($net, 2),
                now()->format('Y-m-d H:i:s'),
            ]);
        }

        fclose($fp);
    }, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Cache-Control'       => 'no-store, no-cache, must-revalidate',
        'Content-Disposition' => 'attachment; filename="payroll_summary.csv"',
    ]);
}


/**
 * Download a single employee’s payslip for a specific date range.
 * Route: GET /reports/payslips/{employee}/download?from=YYYY-MM-DD&to=YYYY-MM-DD
 */
public function downloadPayslipRange(Employee $employee, Request $request)
{
    $from = Carbon::parse($request->input('from', now()->startOfMonth()))->startOfDay();
    $to   = Carbon::parse($request->input('to', now()->endOfMonth()))->endOfDay();

    // ✅ Load government contribution tables once
    $sssBr     = Cache::remember('sss_brackets', now()->addDay(), fn()=> SssContribution::all());
    $philBr    = Cache::remember('phil_brackets', now()->addDay(), fn()=> PhilhealthContribution::all());
    $pagibigBr = Cache::remember('pagibig_brackets', now()->addDay(), fn()=> PagibigContribution::all());
    $findBr    = fn($col, $g)=> $col->first(fn($b)=> $b->range_min <= $g && $b->range_max >= $g);

    // =============== ATTENDANCE LOOP ===============
    $logs = Attendance::where('employee_id', $employee->id)
        ->whereBetween('time_in', [$from, $to])
        ->whereNotNull('time_out')
        ->get();

    $worked = $ot = $nd = $holidayPay = 0.0;

    foreach ($logs as $log) {
        $in  = Carbon::parse($log->time_in);
        $out = Carbon::parse($log->time_out);
        if ($out->lt($in)) $out->addDay();

        $hrs = round($in->diffInMinutes($out) / 60, 2);
        $worked += $hrs;

        // Overtime (beyond schedule)
        if ($employee->schedule && $employee->schedule->time_in && $employee->schedule->time_out) {
            $sIn  = Carbon::parse($employee->schedule->time_in)->setDateFrom($in);
            $sOut = Carbon::parse($employee->schedule->time_out)->setDateFrom($in);
            if ($sOut->lte($sIn)) $sOut->addDay();
            $schedHrs = round($sIn->diffInMinutes($sOut) / 60, 2);
            $ot += max(0, $hrs - $schedHrs);
        }

        // Night Differential (10PM–6AM)
        $ndStart = $in->copy()->setTime(22,0);
        $ndEnd   = $in->copy()->setTime(6,0)->addDay();
        $startND = $in->gt($ndStart) ? $in : $ndStart;
        $endND   = $out->lt($ndEnd) ? $out : $ndEnd;
        if ($endND->gt($startND)) {
            $nd += round($startND->diffInMinutes($endND) / 60, 2);
        }

        // Holiday
        $holiday = DB::table('holidays')->whereDate('date', $in->toDateString())->first();
        if ($holiday) {
            $type = strtolower($holiday->type);
            $rate = ($employee->designation->rate_per_hour ?? 0);
            if ($type === 'regular') $holidayPay += $rate * 8 * 2.00;
            elseif ($type === 'special') $holidayPay += $rate * 8 * 1.30;
            elseif ($type === 'double') $holidayPay += $rate * 8 * 3.00;
        }
    }

    // =============== PAY COMPUTATION ===============
    $rate = (float) ($employee->designation->rate_per_hour ?? 0);

    // Determine standard hours (default 8 if no schedule)
    $standardHours = 8;
    if ($employee->schedule && $employee->schedule->time_in && $employee->schedule->time_out) {
        $sIn  = Carbon::parse($employee->schedule->time_in);
        $sOut = Carbon::parse($employee->schedule->time_out);
        if ($sOut->lte($sIn)) $sOut->addDay();
        $standardHours = round($sIn->diffInMinutes($sOut) / 60, 2);
    }

$regularHours = min($worked, 8);
    $overtimeHours = max(0, $worked - $standardHours);

    $basePay = round($regularHours * $rate, 2);
    $otPay   = round($overtimeHours * $rate * 1.25, 2);
    $ndPay   = round($nd * $rate * 0.10, 2);

    $gross = round($basePay + $otPay + $ndPay + $holidayPay, 2);

    // =============== DEDUCTIONS ===============
    $loanTotal = Loan::where('employee_id', $employee->id)
        ->where('status', 'active')
        ->sum('monthly_amount');

    $sss  = (float) ($findBr($sssBr, $gross)->employee_share ?? 0);
    $phil = round($gross * (($findBr($philBr, $gross)->rate_percent ?? 0) / 100) / 2, 2);
    $pag  = (float) ($findBr($pagibigBr, $gross)->employee_share ?? 0);

    $govtDeduction = round($sss + $phil + $pag, 2);
    $totalDeduction = round($loanTotal + $govtDeduction, 2);

    $net = round($gross - $totalDeduction, 2);

    // =============== PDF GENERATION ===============
    $pdf = PDF::loadView('reports.pdf.payroll', [
        'employee'       => $employee,
        'period_start'   => $from,
        'period_end'     => $to,
        'rate_hr'        => $rate,        // ✅ ensure consistent variable
        'worked_hours'   => $worked,
        'ot_hours'       => $ot,
        'nd_hours'       => $nd,
        'base_pay'       => $basePay,
        'ot_pay'         => $otPay,
        'nd_pay'         => $ndPay,
        'holiday_pay'    => $holidayPay,
        'gross'          => $gross,
        'loan_total'     => $loanTotal,
        'sss'            => $sss,
        'phil'           => $phil,
        'pag'            => $pag,
        'deductions'     => $totalDeduction,
        'net'            => $net,
    ])->setPaper(self::PAYSHEET_SIZE, 'portrait');

    $filename = sprintf('Payslip_%s_%s_to_%s.pdf',
        $employee->employee_code,
        $from->format('Ymd'),
        $to->format('Ymd')
    );

    if (ob_get_length()) @ob_end_clean();
    return $pdf->stream($filename);
}


    /**
     * Export Payslips CSV
     */
  public function exportPayslips(Request $request): StreamedResponse
{
    if (!auth()->check()) abort(403);
    if (ob_get_level() > 0) ob_end_clean();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="payslips_daily.csv"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    $from = Carbon::parse($request->input('from', now()->startOfMonth()))->startOfDay();
    $to   = Carbon::parse($request->input('to', now()->endOfMonth()))->endOfDay();

    $sssBr     = Cache::remember('sss_brackets', now()->addDay(), fn()=> SssContribution::all());
    $philBr    = Cache::remember('phil_brackets', now()->addDay(), fn()=> PhilhealthContribution::all());
    $pagibigBr = Cache::remember('pagibig_brackets', now()->addDay(), fn()=> PagibigContribution::all());
    $findBr    = fn($col, $g)=> $col->first(fn($b)=> $b->range_min <= $g && $b->range_max >= $g);

    $employees = Employee::with(['designation','schedule','attendances'=>fn($q)=>$q
        ->whereBetween('time_in',[$from,$to])
        ->whereNotNull('time_out')
        ->orderBy('time_in')])
        ->orderBy('name')->get();

    $columns = [
        'Code','Name','Date','Worked (hr)','Rate/hr','OT (hr)','OT Pay',
        'ND (hr)','ND Pay','SSS','PhilHealth','Pag-IBIG','Loan','Gross Pay','Net Pay'
    ];

    return new StreamedResponse(function () use ($employees, $columns, $from, $to, $sssBr, $philBr, $pagibigBr, $findBr) {
        echo "\xEF\xBB\xBF";
        $fp = fopen('php://output','w');
        fputcsv($fp, $columns);

        foreach ($employees as $emp) {
            $rate = (float) ($emp->designation->rate_per_hour ?? 0);
            $loan = (float) Loan::where('employee_id',$emp->id)
                        ->where('status','active')
                        ->sum('monthly_amount');

            // Loop through each day of the period
            foreach (CarbonPeriod::create($from, $to) as $day) {
                $dateStr = $day->toDateString();
                $att = $emp->attendances->first(fn($a) => Carbon::parse($a->time_in)->isSameDay($day));

                $worked = $ot = $nd = 0.0;

                if ($att && $att->time_in && $att->time_out) {
                    $in = Carbon::parse($att->time_in);
                    $out = Carbon::parse($att->time_out);
                    if ($out->lt($in)) $out->addDay();

                    $hrs = round($in->diffInMinutes($out)/60,2);
                    $worked = $hrs;

                    if ($emp->schedule && $emp->schedule->time_in && $emp->schedule->time_out) {
                        $sIn  = Carbon::parse($emp->schedule->time_in)->setDateFrom($day);
                        $sOut = Carbon::parse($emp->schedule->time_out)->setDateFrom($day);
                        $sHrs = round($sIn->diffInMinutes($sOut)/60,2);
                        $ot = max(0,$hrs - $sHrs);
                    }

                    // Night Differential
                    $ndStart=$in->copy()->setTime(22,0);
                    $ndEnd=$in->copy()->setTime(6,0)->addDay();
                    $startND=$in->gt($ndStart)?$in:$ndStart;
                    $endND=$out->lt($ndEnd)?$out:$ndEnd;
                    $nd += $endND->gt($startND)?round($startND->diffInMinutes($endND)/60,2):0;
                }

// ───────── Accurate Computation (mirrors payroll table) ─────────

// Base pay = up to 8 hours × rate
$regularHours = min($worked, 8);
$basePay = round($regularHours * $rate, 2);

// Overtime pay (anything beyond 8 hrs)
$otPay = round($ot * $rate * 1.25, 2);

// ND pay = 10% of (base + OT) hours if ND > 0
$ndPay = $nd > 0
    ? round(($regularHours + $ot) * $rate * 0.10, 2)
    : 0;

// Holiday adjustments
$gross = round($basePay + $otPay + $ndPay + ($holidayPay ?? 0), 2);

// Government deductions
$sss  = (float) ($findBr($sssBr, $gross)->employee_share ?? 0);
$phil = round($gross * (($findBr($philBr, $gross)->rate_percent ?? 0) / 100) / 2, 2);
$pag  = (float) ($findBr($pagibigBr, $gross)->employee_share ?? 0);

// Spread loan evenly per working day in period
$loanDaily = $loan > 0 ? round($loan / max($to->diffInDays($from) + 1, 1), 2) : 0;

// Total deductions + net
$ded = round($sss + $phil + $pag + $loanDaily, 2);
$net = round($gross - $ded, 2);


                fputcsv($fp, [
                    $emp->employee_code,
                    $emp->name,
                    $dateStr,
                    number_format($worked,2),
                    number_format($rate,2),
                    number_format($ot,2),
                    '₱'.number_format($otPay,2),
                    number_format($nd,2),
                    '₱'.number_format($ndPay,2),
                    '₱'.number_format($sss,2),
                    '₱'.number_format($phil,2),
                    '₱'.number_format($pag,2),
                    '₱'.number_format($loan / $to->daysInMonth,2),
                    '₱'.number_format($gross,2),
                    '₱'.number_format($net,2),
                ]);
            }
        }

        fclose($fp);
    });
}



    /**
     * HR side: single employee payslip PDF (one slip per page),
     * EXACT page size 4.25in × 11in.
     * GET /reports/payslips/{employee}/pdf?from=YYYY-MM-DD&to=YYYY-MM-DD
     */
 public function payslipEmployeeDownload(Employee $employee, Request $request)
{
    $period_start = Carbon::parse($request->input('from', now()->startOfMonth()))->startOfDay();
    $period_end   = Carbon::parse($request->input('to', now()->endOfMonth()))->endOfDay();

    // Attendance
    $logs = Attendance::where('employee_id', $employee->id)
        ->whereBetween('time_in', [$period_start, $period_end])
        ->whereNotNull('time_out')
        ->get();

    $workedHours = $otHours = $ndHours = 0.0;

    foreach ($logs as $log) {
        $in  = Carbon::parse($log->time_in);
        $out = Carbon::parse($log->time_out);
        if ($out->lt($in)) $out->addDay();

        $hours = $this->hoursBetween($in, $out);
        $workedHours += $hours;
        $otHours += max(0, $hours - 8);

        // ND 22:00–06:00
        $ndStart = $in->copy()->setTime(22,0);
        $ndEnd   = $in->copy()->setTime(6,0)->addDay();
        $startND = $in->gt($ndStart) ? $in : $ndStart;
        $endND   = $out->lt($ndEnd) ? $out : $ndEnd;
        if ($endND->gt($startND)) {
            $ndHours += round($startND->diffInMinutes($endND) / 60, 2);
        }
    }

    $rate_hr = (float) optional($employee->designation)->rate_per_hour ?? 0;

    $basePay = round($workedHours * $rate_hr, 2);
    $otPay   = round($otHours * $rate_hr * 1.25, 2);
    $ndPay   = round($ndHours * $rate_hr * 0.10, 2);

    // HOLIDAY PAY
    $regHolPay = $specHolPay = $dblHolPay = 0.0;

    foreach ($logs as $log) {
        $date = Carbon::parse($log->time_in)->toDateString();
        $holiday = DB::table('holidays')->whereDate('date', $date)->first();
        if (!$holiday) continue;

        switch (strtolower($holiday->type)) {
           case 'regular':
    $regHolPay += ($rate_hr * 8) * 2.00;   // change 2.00 to 1.00 if only basic pay
    break;
case 'special':
    $specHolPay += ($rate_hr * 8) * 1.30;  // or adjust to 1.20 if your policy differs
    break;
case 'double':
    $dblHolPay += ($rate_hr * 8) * 3.00;   // 3x standard, can adjust
    break;
        }
    }

    $gross = round($basePay + $otPay + $ndPay + $regHolPay + $specHolPay + $dblHolPay, 2);

    // Loans (basic total)
    $loanTotal = Loan::where('employee_id', $employee->id)
        ->where('status', 'active')
        ->sum('monthly_amount');

    // Gov contributions
    $sss = $phil = $pag = 0;
    $net = $gross - $loanTotal;

    // SEND TO PDF (THE FIX)
    $pdf = PDF::loadView('reports.pdf.payroll', [
        'employee'        => $employee,
        'period_start'    => $period_start,
        'period_end'      => $period_end,
        'rate_hr'         => $rate_hr,
        'worked_hours'    => $workedHours,
        'ot_hours'        => $otHours,
        'nd_hours'        => $ndHours,
        'base_pay'        => $basePay,
        'ot_pay'          => $otPay,
        'nd_pay'          => $ndPay,
        'reg_hol_pay'     => $regHolPay,
        'spec_hol_pay'    => $specHolPay,
        'dbl_hol_pay'     => $dblHolPay,
        'gross'           => $gross,
        'loan_total'      => $loanTotal,
        'sss'             => $sss,
        'phil'            => $phil,
        'pag'             => $pag,
        'deductions'      => $loanTotal,
        'net'             => $net,
    ])->setPaper(self::PAYSHEET_SIZE, 'portrait');

    return $pdf->stream(
        "payslip_{$employee->employee_code}_{$period_start->format('Ymd')}_{$period_end->format('Ymd')}.pdf"
    );
}


    /** PAGE: /reports/performance */
    public function performanceIndex(Request $request)
    {
        $from = $request->input('from');
        $to   = $request->input('to');

        $q = PerformanceEvaluation::with(['employee','evaluator'])
            ->orderByDesc('period_start');

        if ($from) $q->whereDate('period_end', '>=', $from);
        if ($to)   $q->whereDate('period_start', '<=', $to);

        $evaluations = $q->get();
        $evalCount   = $evaluations->count();
        $evalAvg     = $evalCount ? round($evaluations->avg('overall_score'), 2) : 0;

        $a = DisciplinaryAction::with(['employee','issuer'])->latest();

        if ($from) {
            $a->where(function ($x) use ($from) {
                $x->where(function ($y) use ($from) {
                    $y->whereNotNull('start_date')->whereDate('start_date', '>=', $from);
                })->orWhere(function ($y) use ($from) {
                    $y->whereNull('start_date')->whereDate('created_at', '>=', $from);
                });
            });
        }
        if ($to) {
            $a->where(function ($x) use ($to) {
                $x->where(function ($y) use ($to) {
                    $y->whereNotNull('end_date')->whereDate('end_date', '<=', $to);
                })->orWhere(function ($y) use ($to) {
                    $y->whereNull('end_date')->whereDate('created_at', '<=', $to);
                });
            });
        }

        $actions        = $a->get();
        $actionsCount   = $actions->count();
        $violationsCnt  = $actions->where('action_type', 'violation')->count();
        $suspensionsCnt = $actions->where('action_type', 'suspension')->count();

        return view('reports.performance', compact(
            'from','to',
            'evaluations','evalCount','evalAvg',
            'actions','actionsCount','violationsCnt','suspensionsCnt'
        ));
    }

    /** CSV: /reports/performance/csv */
    public function exportPerformance(Request $request): StreamedResponse
    {
        $from = $request->input('from');
        $to   = $request->input('to');

        $q = PerformanceEvaluation::with('employee','evaluator')->orderBy('period_start');
        if ($from) $q->whereDate('period_end', '>=', $from);
        if ($to)   $q->whereDate('period_start', '<=', $to);

        $records = $q->get();
        $cols    = ['Code','Name','Period Start','Period End','Overall %','Evaluator','Status','Comments'];

        return new StreamedResponse(function() use ($records, $cols) {
            echo "\xEF\xBB\xBF";
            $fp = fopen('php://output','w');
            fputcsv($fp, $cols);
            foreach($records as $ev){
                fputcsv($fp, [
                    optional($ev->employee)->employee_code,
                    optional($ev->employee)->name,
                    optional($ev->period_start)->toDateString(),
                    optional($ev->period_end)->toDateString(),
                    number_format((float)$ev->overall_score, 2),
                    optional($ev->evaluator)->name,
                    $ev->status,
                    $ev->comments,
                ]);
            }
            fclose($fp);
        },200,[
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
            'Content-Disposition' => 'attachment; filename="performance_evaluations.csv"',
        ]);
    }

    /** CSV: /reports/discipline/csv */
    public function exportDiscipline(Request $request): StreamedResponse
    {
        $from = $request->input('from');
        $to   = $request->input('to');

        $a = DisciplinaryAction::with('employee')->latest();

        if ($from) {
            $a->where(function ($x) use ($from) {
                $x->where(function ($y) use ($from) {
                    $y->whereNotNull('start_date')->whereDate('start_date', '>=', $from);
                })->orWhere(function ($y) use ($from) {
                    $y->whereNull('start_date')->whereDate('created_at', '>=', $from);
                });
            });
        }
        if ($to) {
            $a->where(function ($x) use ($to) {
                $x->where(function ($y) use ($to) {
                    $y->whereNotNull('end_date')->whereDate('end_date', '<=', $to);
                })->orWhere(function ($y) use ($to) {
                    $y->whereNull('end_date')->whereDate('created_at', '<=', $to);
                });
            });
        }

        $records = $a->get();
        $cols = ['Date','Code','Name','Type','Category','Severity','Points','Reason','Status','Start','End'];

        return new StreamedResponse(function() use ($records, $cols) {
            echo "\xEF\xBB\xBF";
            $fp = fopen('php://output','w');
            fputcsv($fp, $cols);
            foreach($records as $r){
                $date = $r->start_date ?? $r->created_at;
                fputcsv($fp, [
                    optional($date)->toDateString(),
                    optional($r->employee)->employee_code,
                    optional($r->employee)->name,
                    ucfirst($r->action_type),
                    $r->category,
                    ucfirst($r->severity),
                    $r->points,
                    $r->reason,
                    ucfirst($r->status),
                    optional($r->start_date)->toDateString(),
                    optional($r->end_date)->toDateString(),
                ]);
            }
            fclose($fp);
        },200,[
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
            'Content-Disposition' => 'attachment; filename="disciplinary_actions.csv"',
        ]);
    }

    /**
     * Legacy single layout (kept) — locked to 4.25×11
     * GET /reports/payslips/{employee}/pdf-single
     */
    public function employeePayslipPdf(Employee $employee, Request $request)
    {
        $fromStr = $request->input('from', now()->startOfMonth()->toDateString());
        $toStr   = $request->input('to',   now()->endOfMonth()->toDateString());

        $rate = (float) (optional($employee->designation)->rate_per_hour ?? 0);

        $atts = Attendance::where('employee_id', $employee->id)
            ->whereBetween('time_in', ["{$fromStr} 00:00:00", "{$toStr} 23:59:59"])
            ->orderBy('time_in')->get();

        $workedHours = 0.0; $schedHours = 0.0; $otHours = 0.0;

        foreach ($atts as $att) {
            if (!$att->time_in || !$att->time_out) continue;
            $in  = Carbon::parse($att->time_in);
            $out = Carbon::parse($att->time_out);

            $w = $this->hoursBetween($in, $out);
            $workedHours += $w;

            if ($employee->schedule && $employee->schedule->time_in && $employee->schedule->time_out) {
                $schIn  = Carbon::parse($employee->schedule->time_in)->setDate($in->year,$in->month,$in->day);
                $schOut = Carbon::parse($employee->schedule->time_out)->setDate($in->year,$in->month,$in->day);
                $s = $this->hoursBetween($schIn, $schOut);
                $schedHours += $s; 
                $otHours += max(0, $w - $s);
            }
        }

        $basePay = round($workedHours * $rate, 2);
        $otPay   = round($otHours * $rate * 1.25, 2);
        $gross   = round($basePay + $otPay, 2);
        $net     = $gross;

        $pdf = PDF::loadView('reports.pdf.payroll', [
            'employee'      => $employee,
            'period_start'  => Carbon::parse($fromStr),
            'period_end'    => Carbon::parse($toStr),
            'rate'          => $rate,
            'worked_hours'  => round($workedHours,2),
            'sched_hours'   => round($schedHours,2),
            'ot_hours'      => round($otHours,2),
            'base_pay'      => $basePay,
            'ot_pay'        => $otPay,
            'gross'         => $gross,
            'deductions'    => 0,
            'net'           => $net,
        ])->setPaper(self::PAYSHEET_SIZE, 'portrait');

        $filename = sprintf('payslip_%s_%s_%s.pdf',
            $employee->employee_code,
            Carbon::parse($fromStr)->format('Ymd'),
            Carbon::parse($toStr)->format('Ymd')
        );

        return $pdf->download($filename);
    }

    /** NEW: Bulk merged payslips (one page per employee) */
      public function bulkPayslipsPdf(Request $request)
{
    $period_start = Carbon::parse($request->input('from', now()->startOfMonth()))->startOfDay();
    $period_end   = Carbon::parse($request->input('to', now()->endOfMonth()))->endOfDay();

    $sssBr     = Cache::remember('sss_brackets', now()->addDay(), fn()=> SssContribution::all());
    $philBr    = Cache::remember('phil_brackets', now()->addDay(), fn()=> PhilhealthContribution::all());
    $pagibigBr = Cache::remember('pagibig_brackets', now()->addDay(), fn()=> PagibigContribution::all());
    $findBr    = fn($col, $g)=> $col->first(fn($b)=> $b->range_min <= $g && $b->range_max >= $g);

    $employees = Employee::with(['department','designation'])->orderBy('name')->get();
    $items = [];

    foreach ($employees as $employee) {
        $logs = Attendance::where('employee_id', $employee->id)
            ->whereBetween('time_in', [$period_start, $period_end])
            ->whereNotNull('time_out')
            ->get();

        $workedHours = $otHours = $ndHours = 0.0;

        foreach ($logs as $log) {
            $in  = Carbon::parse($log->time_in);
            $out = Carbon::parse($log->time_out);
            if ($out->lt($in)) $out->addDay();

            $hours = $this->hoursBetween($in, $out);
            $workedHours += $hours;
            $otHours += max(0, $hours - 8);

            // ND 22:00–06:00
            $ndStart = $in->copy()->setTime(22,0);
            $ndEnd   = $in->copy()->setTime(6,0)->addDay();
            $startND = $in->gt($ndStart) ? $in : $ndStart;
            $endND   = $out->lt($ndEnd) ? $out : $ndEnd;
            if ($endND->gt($startND)) {
                $ndHours += round($startND->diffInMinutes($endND) / 60, 2);
            }
        }

        $rate = (float) optional($employee->designation)->rate_per_hour ?? 0;
        $basePay = round($workedHours * $rate, 2);
        $otPay   = round($otHours * $rate * 1.25, 2);
        $ndPay   = round($ndHours * $rate * 0.10, 2);

        // Holidays
        $regHolPay = $specHolPay = $dblHolPay = 0.0;
        foreach ($logs as $log) {
            $date = Carbon::parse($log->time_in)->toDateString();
            $holiday = DB::table('holidays')->whereDate('date', $date)->first();
            if (!$holiday) continue;

            switch (strtolower($holiday->type)) {
                case 'regular': $regHolPay += ($rate * 8) * 2.00; break;
                case 'special': $specHolPay += ($rate * 8) * 1.30; break;
                case 'double':  $dblHolPay  += ($rate * 8) * 3.00; break;
            }
        }

        $gross = round($basePay + $otPay + $ndPay + $regHolPay + $specHolPay + $dblHolPay, 2);

        // Loans
        $carLoan       = Loan::where('employee_id', $employee->id)->whereHas('loanType', fn($q)=>$q->where('name','like','%car%'))->where('status','active')->sum('monthly_amount');
        $educationLoan = Loan::where('employee_id', $employee->id)->whereHas('loanType', fn($q)=>$q->where('name','like','%education%'))->where('status','active')->sum('monthly_amount');
        $housingLoan   = Loan::where('employee_id', $employee->id)->whereHas('loanType', fn($q)=>$q->where('name','like','%housing%'))->where('status','active')->sum('monthly_amount');
        $personalLoan  = Loan::where('employee_id', $employee->id)->whereHas('loanType', fn($q)=>$q->where('name','like','%personal%'))->where('status','active')->sum('monthly_amount');
        $loanTotal     = $carLoan + $educationLoan + $housingLoan + $personalLoan;

        // Gov Deductions
        $sss  = (float) ($findBr($sssBr, $gross)->employee_share ?? 0);
        $phil = round($gross * (($findBr($philBr, $gross)->rate_percent ?? 0)/100) / 2, 2);
        $pag  = (float) ($findBr($pagibigBr, $gross)->employee_share ?? 0);
        $deductions = round($loanTotal + $sss + $phil + $pag, 2);
        $net = round($gross - $deductions, 2);

    $items[] = [
    'employee'      => $employee,
    'period_start'  => $period_start,
    'period_end'    => $period_end,
    'rate'          => $rate,
    'worked_hours'  => $workedHours,
    'ot_hours'      => $otHours,
    'nd_hours'      => $ndHours,
    'base_pay'      => $basePay,
    'ot_pay'        => $otPay,
    'nd_pay'        => $ndPay,
    'reg_hol_pay'   => $regHolPay,
    'spec_hol_pay'  => $specHolPay,
    'dbl_hol_pay'   => $dblHolPay,
    'gross'         => $gross,
    'personalLoan'  => $personalLoan,
    'carLoan'       => $carLoan,
    'educationLoan' => $educationLoan ?? 0,
    'housingLoan'   => $housingLoan ?? 0,
    'otherLoan'     => $otherLoan ?? 0,
    'sss'           => $sss,
    'phil'          => $phil,
    'pag'           => $pag,
    'deductions'    => $deductions,
    'net'           => $net,
];
    }

    $pdf = PDF::loadView('reports.pdf.payroll_bulk', ['items' => $items])
        ->setPaper(self::PAYSHEET_SIZE, 'portrait');

    $filename = sprintf('Payslips_All_%s_%s.pdf',
        $period_start->format('Ymd'),
        $period_end->format('Ymd')
    );

    if (ob_get_length()) @ob_end_clean();
    return $pdf->stream($filename);
}



/** 
 * Export Loans CSV
 * GET /reports/loans?from=YYYY-MM-DD&to=YYYY-MM-DD
 */
public function exportLoans(Request $request)
{
    $from = Carbon::parse($request->input('from', now()->startOfMonth()))->startOfDay();
    $to   = Carbon::parse($request->input('to', now()->endOfMonth()))->endOfDay();

    $loans = Loan::with(['employee', 'loanType'])
        ->whereBetween('created_at', [$from, $to])
        ->orderBy('created_at', 'desc')
        ->get();

    $headers = [
        'Loan ID',
        'Employee Code',
        'Employee Name',
        'Loan Type',
        'Principal Amount',
        'Monthly Amount',
        'Balance',
        'Start Date',
        'End Date',
        'Status',
    ];

    $rows = [];
    foreach ($loans as $loan) {
        $rows[] = [
            $loan->id,
            optional($loan->employee)->employee_code ?? '-',
            optional($loan->employee)->name ?? '-',
            optional($loan->loanType)->name ?? '-',
            number_format($loan->amount ?? 0, 2),
            number_format($loan->monthly_amount ?? 0, 2),
            number_format($loan->balance ?? 0, 2),
            optional($loan->start_date)->format('Y-m-d') ?? '-',
            optional($loan->end_date)->format('Y-m-d') ?? '-',
            ucfirst($loan->status ?? '-'),
        ];
    }

    $filename = 'loan_report_' . $from->format('Ymd') . '_to_' . $to->format('Ymd') . '.csv';

    return response()->streamDownload(function () use ($headers, $rows) {
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
        fputcsv($output, $headers);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
    }, $filename, [
        'Content-Type' => 'text/csv',
        'Cache-Control' => 'no-store, no-cache, must-revalidate',
    ]);
}


/**
 * Export Offboarding CSV
 * GET /reports/offboarding?from=YYYY-MM-DD&to=YYYY-MM-DD
 */
public function exportOffboarding(Request $request)
{
    $from = Carbon::parse($request->input('from', now()->startOfMonth()))->startOfDay();
    $to   = Carbon::parse($request->input('to', now()->endOfMonth()))->endOfDay();

    $offboards = \App\Models\Offboarding::with(['employee.department', 'employee.designation'])
        ->whereBetween('created_at', [$from, $to])
        ->orderBy('created_at', 'desc')
        ->get();

    $headers = [
        'Employee Code',
        'Employee Name',
        'Department',
        'Designation',
        'Reason',
        'Effective Date',
        'Status',
    ];

    $rows = [];
    foreach ($offboards as $off) {
        $rows[] = [
            optional($off->employee)->employee_code ?? '-',
            optional($off->employee)->name ?? '-',
            optional($off->employee->department)->name ?? '-',
            optional($off->employee->designation)->name ?? '-',
            $off->reason ?? '-',
            optional($off->effective_date)->format('Y-m-d') ?? '-',
            ucfirst($off->status ?? '-'),
        ];
    }

    $filename = 'offboarding_report_' . $from->format('Ymd') . '_to_' . $to->format('Ymd') . '.csv';

    return response()->streamDownload(function () use ($headers, $rows) {
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
        fputcsv($output, $headers);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
    }, $filename, [
        'Content-Type' => 'text/csv',
        'Cache-Control' => 'no-store, no-cache, must-revalidate',
    ]);
}

}