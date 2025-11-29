<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Loan;
use App\Models\SssContribution;
use App\Models\PhilhealthContribution;
use App\Models\PagibigContribution;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;

class PayrollCalculator
{
    /**
     * Compute payroll for an employee between given dates.
     * Handles holidays, ND, OT, late, loans, govt deductions, and absences.
     */
    public function compute($employee, Carbon $from, Carbon $to): array
    {
        $rateHr = (float) optional($employee->designation)->rate_per_hour ?? 0;
        $sched = $employee->schedule;
        $workedHours = $otHours = $ndHours = 0;
        $holidayPay = $lateDeduction = $loanDeduction = $govtDeduction = 0;

        $sssBr = Cache::remember('sss_brackets', now()->addDay(), fn() => SssContribution::all());
        $philBr = Cache::remember('phil_brackets', now()->addDay(), fn() => PhilhealthContribution::all());
        $pagibigBr = Cache::remember('pagibig_brackets', now()->addDay(), fn() => PagibigContribution::all());

        $findBr = fn($col, $gross) =>
            $col->first(fn($b) => $b->range_min <= $gross && $b->range_max >= $gross);

        foreach (CarbonPeriod::create($from, $to) as $day) {
            $dateStr = $day->toDateString();
            $holiday = Holiday::whereDate('date', $dateStr)->first();
            $atts = Attendance::where('employee_id', $employee->id)
                ->whereDate('time_in', $dateStr)
                ->orderBy('time_in')
                ->get();

            $hrs = $ot = $nd = 0;
            $schedIn = $schedOut = null;
            $schedH = 0;

            if ($sched) {
                $schedIn = Carbon::parse($sched->time_in)->setDateFrom($day);
                $schedOut = Carbon::parse($sched->time_out)->setDateFrom($day);
                if ($schedOut->lt($schedIn))
                    $schedOut->addDay();
                $schedH = $schedIn->diffInHours($schedOut);
            }

            if ($atts->count()) {
                $firstIn = Carbon::parse($atts->min('time_in'));
                $lastOut = Carbon::parse($atts->max('time_out'));

                if ($lastOut->lt($firstIn))
                    $lastOut->addDay();

                $totalMinutes = $firstIn->diffInMinutes($lastOut);
                $totalHours = $totalMinutes / 60;

                $regularHours = min($totalHours, $schedH);
                $overtimeHours = max(0, $totalHours - $schedH);

                $hrs = $regularHours;
                $ot = $overtimeHours;

                // Late deduction
                if ($schedIn && $firstIn->gt($schedIn)) {
                    $minsLate = $schedIn->diffInMinutes($firstIn);
                    $lateDeduction += round($rateHr * ceil($minsLate / 15) * 0.25, 2);
                }

                // ND hours (22:00–06:00)
                $ndStart = $firstIn->copy()->setTime(22, 0);
                $ndEnd = $firstIn->copy()->setTime(6, 0)->addDay();
                $startND = $firstIn->gt($ndStart) ? $firstIn : $ndStart;
                $endND = $lastOut->lt($ndEnd) ? $lastOut : $ndEnd;
                if ($endND->gt($startND)) {
                    $nd += floor($startND->diffInMinutes($endND) / 60);
                }
            }

            // Holiday Pay Logic
            if ($holiday) {
                $type = strtolower($holiday->type);
                $isRestDay = $day->isSunday();

                // Always appear in payroll if regular/double
                if (in_array($type, ['regular', 'double'])) {
                    if ($hrs <= 0)
                        $hrs = $schedH; // paid even if absent
                }

                // Compute holiday multipliers
                [$working, $otPay] = $this->computeHolidayPay($rateHr, $hrs, $ot, $type, $isRestDay);
                $holidayPay += $working + $otPay;
            }

            $workedHours += $hrs;
            $otHours += $ot;
            $ndHours += $nd;

            // Compute loan deduction if due
            $loanDeduction += $this->computeLoanDeduction($employee, $day);
        }

        // Base Pay
        $basePay = round($workedHours * $rateHr, 2);
        $otPay = round($otHours * $rateHr * 1.25, 2);
        $ndPay = round($ndHours * $rateHr * 0.10, 2);

        $gross = round($otPay + $ndPay + $holidayPay, 2);

        // Government Deductions (last day of month)
        if ($to->isSameDay($to->copy()->endOfMonth())) {
            $sss = (float) ($findBr($sssBr, $gross)->employee_share ?? 0);
            $phil = round($gross * (($findBr($philBr, $gross)->rate_percent ?? 0) / 100) / 2, 2);
            $pag = (float) ($findBr($pagibigBr, $gross)->employee_share ?? 0);
            $govtDeduction = round($sss + $phil + $pag, 2);
        }

        $totalDeduction = round($lateDeduction + $loanDeduction + $govtDeduction, 2);
        $net = round($gross - $totalDeduction, 2);

        return [
            'worked_hours' => $workedHours,
            'ot_hours' => $otHours,
            'ot_pay' => $otPay,
            'nd_hours' => $ndHours,
            'nd_pay' => $ndPay,
            'holiday_pay' => $holidayPay,
            'late_deduction' => $lateDeduction,
            'loan_deduction' => $loanDeduction,
            'govt_deduction' => $govtDeduction,
            'gross' => $gross,
            'net' => $net,
        ];
    }

    /** Compute holiday pay based on type and rest day */
    private function computeHolidayPay($rate, $workedHrs, $otHrs, $type, $isRestDay = false): array
    {
        if (!$type)
            return [0, 0];

        $multipliers = [
            'regular' => ['work' => 2.00, 'ot' => 2.60],
            'special' => ['work' => 1.30, 'ot' => 1.69],
            'double' => ['work' => 3.00, 'ot' => 3.90],
        ];

        if ($isRestDay) {
            $multipliers['regular'] = ['work' => 2.60, 'ot' => 3.38];
            $multipliers['special'] = ['work' => 1.50, 'ot' => 1.95];
            $multipliers['double'] = ['work' => 3.90, 'ot' => 5.07];
        }

        $set = $multipliers[$type] ?? ['work' => 0, 'ot' => 0];

        return [
            round($rate * $workedHrs * $set['work'], 2),
            round($rate * $otHrs * $set['ot'], 2),
        ];
    }

    /** Compute loan deduction for due date */
    private function computeLoanDeduction($emp, Carbon $date): float
    {
        $total = 0.0;

        $activeLoans = Loan::with('plan')
            ->where('employee_id', $emp->id)
            ->where('status', 'active')
            ->get();

        foreach ($activeLoans as $loan) {
            if (!$loan->plan || !$loan->next_payment_date)
                continue;
            if ($date->isSameDay($loan->next_payment_date)) {
                $total += $loan->monthly_amount;
            }
        }

        return round($total, 2);
    }
}
