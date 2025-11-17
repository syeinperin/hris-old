<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\DisciplinaryAction; // ⬅️ NEW
use Illuminate\Http\Request;
use Carbon\Carbon;

class CalendarController extends Controller
{
    /**
     * Build discipline overlays for the calendar:
     *  - suspensions[emp_id][Y-m-d] = DisciplinaryAction
     *  - violations[emp_id][Y-m-d] = [DisciplinaryAction,...]
     */
    private function buildDisciplineIndex($empIds, Carbon $start, Carbon $end): array
    {
        $acts = DisciplinaryAction::whereIn('employee_id', $empIds)
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($qq) use ($start, $end) {
                    $qq->where('action_type', 'suspension')
                       ->whereDate('start_date', '<=', $end->toDateString())
                       ->whereDate('end_date',   '>=', $start->toDateString());
                })->orWhere(function ($qq) use ($start, $end) {
                    $qq->where('action_type', 'violation')
                       ->whereDate(\DB::raw('COALESCE(start_date, created_at)'), '>=', $start->toDateString())
                       ->whereDate(\DB::raw('COALESCE(start_date, created_at)'), '<=', $end->toDateString());
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
    }

    /**
     * Render the interactive calendar.
     */
    public function index(Request $request)
    {
        $month      = $request->get('month', Carbon::now()->format('Y-m'));
        $search     = $request->input('search', '');
        $start      = Carbon::parse("{$month}-01");
        $end        = (clone $start)->endOfMonth();

        $employees  = Employee::where('status','active')
            ->when($search, fn($q,$s)=>
                $q->where('employee_code','like',"%{$s}%")
                  ->orWhere('name','like',         "%{$s}%")
            )
            ->with(['department','designation','schedule'])
            ->orderBy('name')
            ->paginate(25)
            ->appends(compact('month','search'));

        $attendance = Attendance::whereBetween('time_in', [
                $start->toDateString().' 00:00:00',
                $end->toDateString()  .' 23:59:59',
            ])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->groupBy('employee_id')
            ->map(fn($grp)=>
                $grp->mapWithKeys(fn($r)=>[
                    $r->time_in->toDateString() => $r
                ])
            );
        $leaveIndex = LeaveRequest::whereIn('employee_id',$employees->pluck('id'))
            ->where(function($q) use($start,$end){
                $q->whereBetween('start_date',[$start->toDateString(),$end->toDateString()])
                  ->orWhereBetween('end_date',  [$start->toDateString(),$end->toDateString()]);
            })
            ->get()
            ->groupBy('employee_id')
            ->map(fn($grp)=>
                $grp->groupBy(fn($r)=>$r->start_date->toDateString())
            );
        $holidays = Holiday::whereYear('date',$start->year)
            ->pluck('name','date')
            ->all();

        // ⬅️ NEW: discipline overlays
        $discipline = $this->buildDisciplineIndex($employees->pluck('id'), $start, $end);

        return view('payroll.calendar', compact(
            'employees','attendance','leaveIndex',
            'holidays','start','end','month','search','discipline'
        ));
    }

    /**
     * Toggle manual override (POST).
     */
    public function toggleManual(Request $request)
    {
        $data = $request->validate([
            'employee_id'=>'required|exists:employees,id',
            'date'       =>'required|date',
        ]);

        $existing = Attendance::where('employee_id',$data['employee_id'])
            ->whereDate('time_in',$data['date'])
            ->where('is_manual',true)
            ->first();

        if($existing){
            $existing->delete();
            $state = false;
        } else {
            Attendance::create([
                'employee_id'=>$data['employee_id'],
                'schedule_id'=>null,
                'time_in'    =>"{$data['date']} 00:00:00",
                'time_out'   =>null,
                'is_manual'  =>true,
            ]);
            $state = true;
        }

        return response()->json(['manual' => $state]);
    }

    /**
     * Set biometric (auto) attendance (POST).
     */
    public function setBiometric(Request $request)
    {
        $data = $request->validate([
            'employee_id'=>'required|exists:employees,id',
            'date'       =>'required|date',
        ]);

        $att = Attendance::firstOrNew([
            'employee_id'=> $data['employee_id'],
            'time_in'    => "{$data['date']} 00:00:00",
        ]);
        $att->is_manual = false;
        $att->save();

        return response()->json(['biometric' => true]);
    }

    /**
     * Delete any record for that cell (DELETE).
     */
    public function removeManual(Request $request)
    {
        $data = $request->validate([
            'employee_id'=>'required|exists:employees,id',
            'date'       =>'required|date',
        ]);

        Attendance::where('employee_id',$data['employee_id'])
            ->whereDate('time_in',$data['date'])
            ->delete();

        return response()->json(['removed' => true]);
    }
}
