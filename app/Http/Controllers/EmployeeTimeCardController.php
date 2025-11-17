<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class EmployeeTimeCardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $employee = $user?->employee;
        abort_unless($employee, 403, 'No employee profile linked to your account.');

        // Determine week window: default = current week (Mon–Sun)
        if ($request->filled('start') || $request->filled('end')) {
            $start = Carbon::parse($request->input('start', Carbon::now()->toDateString()))->startOfDay();
            $end   = Carbon::parse($request->input('end',   Carbon::now()->toDateString()))->endOfDay();
        } else {
            $start = Carbon::now()->startOfWeek(Carbon::MONDAY);
            $end   = Carbon::now()->endOfWeek(Carbon::SUNDAY);
        }

        // Query attendance within window
        $records = Attendance::where('employee_id', $employee->id)
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'asc')
            ->get(['id','time_in','time_out','created_at']);

        // Seed all days in the window
        $days = [];
        foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $d) {
            $days[$d->toDateString()] = [
                'date'     => $d->toDateString(),
                'time_in'  => '—',
                'time_out' => '—',
                'hours'    => '',
                'status'   => 'Absent',
            ];
        }

        // Fill with real punches
        foreach ($records as $r) {
            $dateKey = $r->created_at->toDateString();
            if (!isset($days[$dateKey])) continue;

            $in  = $r->time_in ? Carbon::parse($r->time_in) : null;
            $out = $r->time_out ? Carbon::parse($r->time_out) : null;

            $hours = '';
            if ($in && $out) {
                if ($out->lt($in)) { $out->addDay(); }
                $hours = round($in->diffInMinutes($out) / 60, 2);
            }

            $days[$dateKey] = [
                'date'     => $dateKey,
                'time_in'  => $in  ? $in->format('h:i:s A') : '—',
                'time_out' => $out ? $out->format('h:i:s A') : ($in ? 'Still in' : '—'),
                'hours'    => $hours !== '' ? number_format((float)$hours, 2) : '',
                'status'   => $in ? ($out ? 'Present' : 'In-Progress') : 'Absent',
            ];
        }

        ksort($days);

        // Prev/next week helpers
        $prevStart = $start->copy()->subWeek()->startOfDay()->toDateString();
        $prevEnd   = $end->copy()->subWeek()->endOfDay()->toDateString();
        $nextStart = $start->copy()->addWeek()->startOfDay()->toDateString();
        $nextEnd   = $end->copy()->addWeek()->endOfDay()->toDateString();

        return view('timecard.index', [
            'employee'  => $employee,
            'rows'      => array_values($days),
            'start'     => $start->toDateString(),
            'end'       => $end->toDateString(),
            'prevStart' => $prevStart,
            'prevEnd'   => $prevEnd,
            'nextStart' => $nextStart,
            'nextEnd'   => $nextEnd,
        ]);
    }

    
}
