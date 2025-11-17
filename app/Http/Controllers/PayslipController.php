<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Payslip;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Loan;
use App\Models\SssContribution;
use App\Models\PhilhealthContribution;
use App\Models\PagibigContribution;
use Carbon\Carbon;

class PayslipController extends Controller
{
    public function index()
    {
        $from = request('from');
        $to   = request('to');

        $payslips = Auth::user()
            ->payslips()
            ->when($from && $to, fn($q) =>
                $q->whereBetween('period_start', [$from, $to])
            )
            ->orderByDesc('period_start')
            ->paginate(10);

        return view('payslips.index', compact('payslips'));
    }

public function store(Request $request)
{
    $data = $request->validate([
        'month'  => 'required|date_format:Y-m',
        'cutoff' => 'required|in:first,second,whole',
    ]);

    $employee = Auth::user()->employee;
    if (!$employee) return back()->with('error', 'Complete your employee profile first.');

    $empId = $employee->id;
    $month = Carbon::createFromFormat('Y-m', $data['month']);

    // ───── DEFINE CUTOFF ─────────────────────────────────────────
    if ($data['cutoff'] === 'first') {
        $periodStart = $month->copy()->startOfMonth();
        $periodEnd   = $month->copy()->day(15)->endOfDay();
    }
    elseif ($data['cutoff'] === 'second') {
        $periodStart = $month->copy()->day(16)->startOfDay();
        $periodEnd   = $month->copy()->endOfMonth()->endOfDay();
    }
    else { // whole month
        $periodStart = $month->copy()->startOfMonth();
        $periodEnd   = $month->copy()->endOfMonth()->endOfDay();
    }

    // ───── GET ATTENDANCE ───────────────────────────────────────
    $attendances = Attendance::where('employee_id', $empId)
        ->whereBetween('time_in', [$periodStart, $periodEnd])
        ->whereNotNull('time_out')
        ->get();

    $workedMin = $otMin = $ndMin = $holidayMin = 0;

    foreach ($attendances as $a) {
        $in  = Carbon::parse($a->time_in);
        $out = Carbon::parse($a->time_out);
        if ($out->lt($in)) $out->addDay();

        $workedMin += $in->diffInMinutes($out);

        $hrs = $in->diffInHours($out);
        if ($hrs > 8) $otMin += ($hrs - 8) * 60;

        $ndStart = $in->copy()->setTime(22, 0);
        $ndEnd   = $in->copy()->setTime(6, 0)->addDay();
        $startND = $in->gt($ndStart) ? $in : $ndStart;
        $endND   = $out->lt($ndEnd) ? $out : $ndEnd;
        if ($endND->gt($startND)) $ndMin += $startND->diffInMinutes($endND);

        if (Holiday::whereDate('date', $in->toDateString())->exists()) {
            $holidayMin += $in->diffInMinutes($out);
        }
    }

    $workedHours  = round($workedMin / 60, 2);
    $otHours      = round($otMin / 60, 2);
    $ndHours      = round($ndMin / 60, 2);
    $holidayHours = round($holidayMin / 60, 2);

    $baseRate = (float) (optional($employee->designation)->rate_per_hour ?? 0);

    $basePay    = round($workedHours * $baseRate, 2);
    $otPay      = round($otHours * $baseRate * 1.25, 2);
    $ndPay      = round($ndHours * $baseRate * 0.10, 2);
    $holidayPay = round($holidayHours * $baseRate * 2.0, 2);

    $gross = round($basePay + $otPay + $ndPay + $holidayPay, 2);

    // ───── LOAN (EVERY 15th) ─────────────────────────────────────
    $loanDed = 0;
    if ($data['cutoff'] === 'first' || $data['cutoff'] === 'whole') {
        $loanDed = (float) Loan::where('employee_id', $empId)
            ->where('status', 'active')
            ->sum('monthly_amount');
    }

    // ───── GOVERNMENT (EVERY 30th/31st ONLY) ─────────────────────
    $sss = $phil = $pag = 0.0;

    if ($data['cutoff'] === 'second' || $data['cutoff'] === 'whole') {

        $sssBr     = Cache::remember('sss_brackets', now()->addDay(), fn() => SssContribution::all());
        $philBr    = Cache::remember('phil_brackets', now()->addDay(), fn() => PhilhealthContribution::all());
        $pagibigBr = Cache::remember('pagibig_brackets', now()->addDay(), fn() => PagibigContribution::all());

        $findBr = fn($col, $g) => $col->first(fn($b) => $b->range_min <= $g && $b->range_max >= $g);

        $sss  = (float) ($findBr($sssBr, $gross)->employee_share ?? 0);
        $phil = round($gross * (($findBr($philBr, $gross)->rate_percent ?? 0) / 100) / 2, 2);
        $pag  = (float) ($findBr($pagibigBr, $gross)->employee_share ?? 0);
    }

    $govt = round($sss + $phil + $pag, 2);
    $deductions = round($loanDed + $govt, 2);
    $net = round($gross - $deductions, 2);

    Payslip::updateOrCreate(
        [
            'user_id'      => Auth::id(),
            'period_start' => $periodStart->toDateString(),
            'period_end'   => $periodEnd->toDateString(),
        ],
        [
            'worked_hours'   => $workedHours,
            'ot_hours'       => $otHours,
            'ot_pay'         => $otPay,
            'nd_hours'       => $ndHours,
            'nd_pay'         => $ndPay,
            'holiday_hours'  => $holidayHours,
            'holiday_pay'    => $holidayPay,
            'loan_deduction' => $loanDed,
            'sss'            => $sss,
            'phil'           => $phil,
            'pagibig'        => $pag,
            'govt_deduction' => $govt,
            'deductions'     => $deductions,
            'gross_amount'   => $gross,
            'net_amount'     => $net,
        ]
    );

    return redirect()
        ->route('payslips.index', [
            'from' => $periodStart->toDateString(),
            'to'   => $periodEnd->toDateString(),
        ])
        ->with('success', 'Payslip generated successfully.');
}
 public function download(Payslip $payslip)
{
    abort_unless($payslip->user_id === Auth::id(), 403);

    $employee = Auth::user()->employee;
    if (!$employee) {
        return back()->with('error', 'Employee record not found.');
    }

    $periodStart = Carbon::parse($payslip->period_start);
    $periodEnd   = Carbon::parse($payslip->period_end);

    $baseRate    = optional($employee->designation)->rate_per_hour ?? 0;

    $workedHours = $payslip->worked_hours ?? 0;
    $otHours     = $payslip->ot_hours ?? 0;
    $otPay       = $payslip->ot_pay ?? 0;
    $gross       = $payslip->gross_amount ?? 0;

    // ───── BASE PAY FIX (for Regular Hours row) ─────────────────────
    $base_pay = round($workedHours * $baseRate, 2);

    // ───── DETERMINE IF THIS IS 2ND CUTOFF ──────────────────────────
    $isSecondCutoff = $periodEnd->isSameDay($periodEnd->copy()->endOfMonth());

    // ───── GOVERNMENT CONTRIBUTIONS ─────────────────────────────────
    $sss  = $payslip->sss;
    $phil = $payslip->phil;
    $pag  = $payslip->pagibig;

    if ($isSecondCutoff) {
        $gov_total = $sss + $phil + $pag;
    } else {
        // first cutoff → no govt deduction
        $sss = $phil = $pag = 0;
        $gov_total = 0;
    }

    // ───── LOAN DEDUCTIONS ─────────────────────────────────────────
    $pagibigLoan = Loan::where('employee_id', $employee->id)
        ->whereHas('loanType', fn($q) => $q->where('name', 'Pag-Ibig Loan'))
        ->where('status', 'active')
        ->sum('monthly_amount');

    $sssLoan = Loan::where('employee_id', $employee->id)
        ->whereHas('loanType', fn($q) => $q->where('name', 'SSS Loan'))
        ->where('status', 'active')
        ->sum('monthly_amount');

    $philhealthLoan = Loan::where('employee_id', $employee->id)
        ->whereHas('loanType', fn($q) => $q->where('name', 'PhilHealth Loan'))
        ->where('status', 'active')
        ->sum('monthly_amount');

    $cashAdvance = Loan::where('employee_id', $employee->id)
        ->whereHas('loanType', fn($q) => $q->where('name', 'Cash Advance'))
        ->where('status', 'active')
        ->sum('monthly_amount');

    $loan_total = $pagibigLoan + $sssLoan + $philhealthLoan + $cashAdvance;
    $total_ded  = $loan_total + $gov_total;
    $net        = round($gross - $total_ded, 2);

    // ───── PDF RENDER ──────────────────────────────────────────────
   $pdf = Pdf::loadView('reports.pdf.payroll', [
    'employee'        => $employee,
    'period_start'    => $periodStart,
    'period_end'      => $periodEnd,
    'rate_hr'         => $baseRate,   // ✅ use this key
    'worked_hours'    => $workedHours,
    'ot_hours'        => $otHours,
    'ot_pay'          => $otPay,
    'base_pay'        => $base_pay,
    'gross'           => $gross,
    'sss'             => $sss,
    'phil'            => $phil,
    'pag'             => $pag,
    'pagibigLoan'     => $pagibigLoan,
    'sssLoan'         => $sssLoan,
    'philhealthLoan'  => $philhealthLoan,
    'cashAdvance'     => $cashAdvance,
    'loan_total'      => $loan_total,
    'total_ded'       => $total_ded,
    'net'             => $net,

    ])->setPaper([0, 0, 306, 792], 'portrait');

    if (ob_get_length()) { @ob_end_clean(); }
    return $pdf->stream("payslip-{$payslip->id}.pdf");
}
}
