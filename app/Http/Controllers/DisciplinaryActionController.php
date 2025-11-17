<?php

namespace App\Http\Controllers;

use App\Models\DisciplinaryAction;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class DisciplinaryActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* =========================================================
     * INDEX — table with filters and role-based visibility
     * ========================================================= */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = DisciplinaryAction::with(['employee', 'issuer'])->latest();

        // 🔹 Supervisors: only see actions within their supervised departments
        if ($user->hasRole('supervisor')) {
            $deptIds = $user->supervisedDepartments()->pluck('departments.id');
            $query->whereHas('employee', fn($q) => $q->whereIn('department_id', $deptIds));
        }

        // 🔹 Employees: only their own
        if ($user->hasRole('employee') && $user->employee) {
            $query->where('employee_id', $user->employee->id);
        }

        // 🔹 Filters
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('type')) {
            $query->where('action_type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $actions = $query->paginate(15)->withQueryString();

        // 🔹 Employee dropdown filtered by role
        $employees = Employee::query()
            ->where('status', 'active')
            ->when($user->hasRole('supervisor'), function ($q) use ($user) {
                $deptIds = $user->supervisedDepartments()->pluck('departments.id');
                $q->whereIn('department_id', $deptIds);
            })
            ->when($user->hasRole('employee') && $user->employee, function ($q) use ($user) {
                $q->where('id', $user->employee->id);
            })
            // 🚫 exclude self (supervisor’s own employee record)
            ->when($user->employee, function ($q) use ($user) {
                $q->where('id', '!=', $user->employee->id);
            })
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('discipline.index', compact('actions', 'employees'));
    }

    /* =========================================================
     * CREATE — form page or modal (filtered employees)
     * ========================================================= */
    public function create()
    {
        $user = auth()->user();

        $query = Employee::query()
            ->where('status', 'active');

        // 🔹 Supervisors: only their supervised departments
        if ($user->hasRole('supervisor')) {
            $deptIds = $user->supervisedDepartments()->pluck('departments.id');
            $query->whereIn('department_id', $deptIds);
        }

        // 🚫 Exclude the supervisor’s own employee record
        if ($user->employee) {
            $query->where('id', '!=', $user->employee->id);
        }

        // 🔹 Employees themselves cannot issue actions
        if ($user->hasRole('employee')) {
            $query->where('id', 0);
        }

        $employees = $query->orderBy('name')->pluck('name', 'id');
        $recentActions = DisciplinaryAction::latest()->take(10)->get();

        return view('discipline.create', compact('employees', 'recentActions'));
    }

    /* =========================================================
     * STORE — save violation / suspension
     * ========================================================= */
    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'action_type' => 'required|in:violation,suspension',
            'category'    => 'nullable|string|max:255',
            'severity'    => 'required|in:minor,major,critical',
            'points'      => 'nullable|integer|min:0|max:100',
            'reason'      => 'required|string',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'notes'       => 'nullable|string',
        ]);

        if ($data['action_type'] === 'suspension') {
            $request->validate([
                'start_date' => 'required|date',
                'end_date'   => 'required|date|after_or_equal:start_date',
            ]);
        } else {
            $data['start_date'] = null;
            $data['end_date']   = null;
        }

        foreach (['category', 'notes'] as $k) {
            if (empty($data[$k])) $data[$k] = null;
        }
        if (empty($data['points'])) $data['points'] = null;

        $data['issued_by'] = auth()->id();
        $data['status'] = 'active';

        try {
            DB::transaction(fn() => DisciplinaryAction::create($data));
            return redirect()->route('discipline.index')
                ->with('success', 'Disciplinary action recorded successfully.');
        } catch (\Throwable $e) {
            Log::error('DisciplinaryAction store failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return back()->withInput()->with('error', 'Failed to save disciplinary action.');
        }
    }

    /* =========================================================
     * RESOLVE
     * ========================================================= */
    public function resolve(DisciplinaryAction $action)
    {
        $action->update(['status' => 'resolved']);
        return back()->with('success', 'Action marked as resolved.');
    }

    /* =========================================================
     * DESTROY
     * ========================================================= */
    public function destroy(DisciplinaryAction $action)
    {
        $action->delete();
        return back()->with('success', 'Disciplinary action deleted.');
    }

    /* =========================================================
     * PDF
     * ========================================================= */
    public function pdf(DisciplinaryAction $action)
    {
        $action->load(['employee', 'issuer']);

        $company = [
            'name'    => 'Asia Textile Mills, Inc.',
            'address' => [
                'Old National Highway, Bgy San Cristobal,',
                'Calamba, Laguna, Philippines',
                '(049) 531 7239 | asiatex84@gmail.com',
            ],
            'logo' => public_path('images/asiatex.png'),
        ];

        $data = [
            'action'    => $action,
            'company'   => $company,
            'plant_mgr' => 'Mr. Moises A. Galicha',
        ];

        $pdf = Pdf::loadView('discipline.pdf', $data)->setPaper('a4');
        $name = Str::slug($action->action_type . '-' . $action->id) . '.pdf';
        return $pdf->stream($name);
    }
}
