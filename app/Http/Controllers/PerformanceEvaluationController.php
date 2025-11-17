<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\PerformanceItem;
use App\Models\PerformanceEvaluation;
use App\Models\PerformanceScore;

class PerformanceEvaluationController extends Controller
{
    /* =====================================================
     * LIST DATA (for index)
     * ===================================================== */
    protected function listData(Request $request): array
    {
        $user = auth()->user();

        $q = PerformanceEvaluation::with(['employee.department', 'evaluator'])
            ->orderByDesc('period_end');

        /* =============================
         * ROLE-BASED VISIBILITY
         * ============================= */
        if ($user->hasRole('supervisor')) {
            $deptIds = $user->supervisedDepartments()->pluck('departments.id');

            // ✅ Supervisors see:
            //  - Evaluations they created, OR
            //  - Evaluations for employees in their supervised departments
            $q->where(function ($qq) use ($deptIds, $user) {
                $qq->where('evaluator_id', $user->id)
                   ->orWhereHas('employee', function ($sub) use ($deptIds) {
                       $sub->whereIn('department_id', $deptIds);
                   });
            });
        }

        if ($user->hasRole('employee')) {
            // ✅ Employees see only their own
            $q->whereHas('employee', fn($sub) => $sub->where('user_id', $user->id));
        }

        /* =============================
         * SEARCH AND FILTER
         * ============================= */
        if ($s = trim((string) $request->get('search'))) {
            $q->whereHas('employee', function ($qq) use ($s) {
                $qq->where('first_name', 'like', "%{$s}%")
                   ->orWhere('last_name', 'like', "%{$s}%")
                   ->orWhere('name', 'like', "%{$s}%")
                   ->orWhere('employee_code', 'like', "%{$s}%");
            });
        }

        if ($type = $request->get('type')) {
            $q->where('type', $type);
        }

        /* =============================
         * EMPLOYEE LIST (for dropdown)
         * ============================= */
        $employees = Employee::query()
            ->when($user->hasRole('supervisor'), function ($q) use ($user) {
                $deptIds = $user->supervisedDepartments()->pluck('departments.id');
                $q->whereIn('department_id', $deptIds);
            })
            ->orderBy('name')
            ->pluck('name', 'id');

        return [
            'evaluations' => $q->paginate(10)->withQueryString(),
            'employees'   => $employees,
            'items'       => PerformanceItem::where('is_active', true)
                ->orderBy('display_order')
                ->get(),
        ];
    }

    /* =====================================================
     * INDEX VIEW
     * ===================================================== */
    public function index(Request $request)
    {
        $user = auth()->user();
        $data = $this->listData($request);

        // Separate employee view
        if ($user->hasRole('employee')) {
            return view('evaluations.employee_index', $data);
        }

        return view('evaluations.index', $data);
    }

    /* =====================================================
     * SHOW VIEW
     * ===================================================== */
    public function show(PerformanceEvaluation $evaluation)
    {
        $evaluation->load(['employee', 'evaluator', 'scores.item']);
        $user = auth()->user();

        if ($user->hasRole('employee')) {
            return view('evaluations.partials.show-content', [
                'evaluation' => $evaluation,
                'embedded'   => true,
            ]);
        }

        return view('evaluations.partials.show', compact('evaluation'));
    }

    /* =====================================================
     * STORE NEW EVALUATION
     * ===================================================== */
    public function store(Request $request)
    {
        $items = PerformanceItem::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $itemIds = $items->pluck('id')->toArray();

        $data = $request->validate([
            'employee_id'  => 'required|exists:employees,id',
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
            'type'         => 'required|string|in:regular,probationary,kpi,360',
            'remarks'      => 'nullable|string',
            'scores'       => 'required|array',
            'scores.*'     => 'required|integer|min:1|max:5',
            'notes'        => 'array',
        ]);

        try {
            DB::transaction(function () use ($items, $data) {
                $eval = PerformanceEvaluation::create([
                    'employee_id'  => $data['employee_id'],
                    'evaluator_id' => auth()->id(),
                    'period_start' => $data['period_start'],
                    'period_end'   => $data['period_end'],
                    'type'         => $data['type'],
                    'remarks'      => $data['remarks'] ?? null,
                    'status'       => 'submitted',
                    'overall_score'=> 0,
                ]);

                $totalWeight = $items->sum('weight');
                if ($totalWeight <= 0) $totalWeight = 100;

                $total = 0;

                foreach ($items as $item) {
                    $score = (int) ($data['scores'][$item->id] ?? 0);
                    $weight = ($item->weight / $totalWeight) * 100;
                    $points = ($score / 5) * $weight;

                    PerformanceScore::create([
                        'evaluation_id' => $eval->id,
                        'item_id'       => $item->id,
                        'score'         => $score,
                        'notes'         => $data['notes'][$item->id] ?? null,
                        'weight_cache'  => round($weight, 2),
                        'weighted_score'=> round($points, 2),
                    ]);

                    $total += $points;
                }

                $eval->update(['overall_score' => round($total, 2)]);

                // ✅ Auto recommendations
                if ($eval->type === 'probationary' && $eval->overall_score >= 80) {
                    $eval->update(['regularization_recommended' => true]);
                }

                if (in_array($eval->type, ['regular', 'kpi', '360']) && $eval->overall_score >= 85) {
                    $eval->update(['promotion_recommended' => true]);
                }
            });

            return redirect()
                ->route('evaluations.index')
                ->with('success', 'Evaluation saved successfully.');

        } catch (\Throwable $e) {
            \Log::error('Performance Evaluation Save Failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return back()->withInput()
                ->with('error', 'Failed to save evaluation. Please check logs.');
        }
    }

    /* =====================================================
     * AJAX: Filter employees by type
     * ===================================================== */
public function filterEmployees(Request $request)
{
    if (!auth()->check()) {
        return response()->json([], 401);
    }

    $user = auth()->user();
    $type = $request->get('type');

    $query = Employee::query()
        ->where('status', 'active');

    // 🔹 Employment type filter
    switch ($type) {
        case 'probationary':
            $query->where('employment_type', 'probationary');
            break;
        case 'regular':
        case 'kpi':
        case '360':
            $query->where('employment_type', 'regular');
            break;
        default:
            return response()->json([]);
    }

    // 🔹 Role-based filtering
    if ($user->hasRole('supervisor')) {
        // Supervisors see only employees in their supervised departments
        $deptIds = $user->supervisedDepartments()->pluck('departments.id')->toArray();
        $query->whereIn('department_id', $deptIds);
    } elseif ($user->hasRole('employee')) {
        // Employees cannot evaluate anyone
        return response()->json([]);
    }

    // 🔹 Exclude the current user’s own employee record
    if ($user->employee) {
        $query->where('id', '!=', $user->employee->id);
    }

    // 🔹 Fetch filtered employees
    $employees = $query
        ->orderBy('name')
        ->get(['id', 'name']);

    return response()->json($employees);
}

    /* =====================================================
     * DELETE
     * ===================================================== */
    public function destroy(PerformanceEvaluation $evaluation)
    {
        $evaluation->delete();
        return redirect()->route('evaluations.index')->with('success', 'Evaluation deleted.');
    }
}
