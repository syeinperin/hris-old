<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanType;
use App\Models\LoanPlan;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));

        $loans = Loan::with(['employee', 'loanType', 'plan'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($qq) use ($search) {
                        $qq->where('name', 'like', "%{$search}%")
                            ->orWhere('employee_code', 'like', "%{$search}%");
                    });
            })
            ->latest('released_at')
            ->paginate(10);

        // Dropdowns for modal
        $employees = Employee::orderBy('name')->pluck('name', 'id');
        $types     = LoanType::orderBy('name')->pluck('name', 'id');

        // ✅ FIXED: Use full model collection, not pluck()
        $plans = LoanPlan::select('id', 'name', 'deduction_type', 'interest_rate')
            ->orderBy('name')
            ->get();

        return view('loans.index', compact('loans', 'employees', 'types', 'plans'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id'        => 'required|exists:employees,id',
            'loan_type_id'       => 'required|exists:loan_types,id',
            'plan_id'            => 'required|exists:loan_plans,id',
            'principal_amount'   => 'required|numeric|min:0.01',
            'interest_rate'      => 'nullable|numeric|min:0',
            'term_months'        => 'nullable|integer|min:1',
            'next_payment_date'  => 'required|date',
            'released_at'        => 'required|date',
            'status'             => 'nullable|in:active,paid,defaulted',
        ]);

        $plan = LoanPlan::findOrFail($data['plan_id']);

        $principal = (float)$data['principal_amount'];
        $rate      = (float)($data['interest_rate'] ?? $plan->interest_rate);

        // 🔹 Automatically determine # of deductions based on plan type
        switch ($plan->deduction_type) {
            case 'semi-monthly': $months = 2; break; // twice per month
            case 'monthly':      $months = 1; break;
            case 'quarterly':    $months = 3; break;
            case 'one-time':     $months = 1; break;
            default:             $months = 1; // fallback
        }

        // Allow manual override
        if (!empty($data['term_months'])) {
            $months = (int)$data['term_months'];
        }

        $total   = round($principal * (1 + ($rate / 100)), 2);
        $monthly = round($total / max(1, $months), 2);

        Loan::create([
            'employee_id'       => $data['employee_id'],
            'loan_type_id'      => $data['loan_type_id'],
            'plan_id'           => $data['plan_id'],
            'principal_amount'  => $principal,
            'interest_rate'     => $rate,
            'term_months'       => $months,
            'total_payable'     => $total,
            'monthly_amount'    => $monthly,
            'next_payment_date' => $data['next_payment_date'],
            'status'            => $data['status'] ?? 'active',
            'released_at'       => $data['released_at'],
        ]);

        return redirect()->route('loans.index')->with('success', 'Loan created successfully.');
    }

    public function edit(Loan $loan, Request $request)
    {
        $search = trim($request->input('search', ''));

        $loans = Loan::with(['employee', 'loanType', 'plan'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($qq) use ($search) {
                        $qq->where('name', 'like', "%{$search}%")
                            ->orWhere('employee_code', 'like', "%{$search}%");
                    });
            })
            ->latest('released_at')
            ->paginate(10);

        $employees = Employee::orderBy('name')->pluck('name', 'id');
        $types     = LoanType::orderBy('name')->pluck('name', 'id');
        $plans     = LoanPlan::select('id', 'name', 'deduction_type', 'interest_rate')
            ->orderBy('name')
            ->get();

        $editLoan = $loan;
        return view('loans.index', compact('loans', 'employees', 'types', 'plans', 'editLoan'));
    }

public function payments(Loan $loan)
{
    $loan->load(['employee', 'plan', 'loanType', 'payments' => function ($q) {
        $q->orderByDesc('payment_date');
    }]);

    $totalPaid = $loan->payments->sum('amount');
    $balance   = max(0, $loan->total_payable - $totalPaid);

    return view('loans.payments', compact('loan', 'totalPaid', 'balance'));
}


    public function update(Request $request, Loan $loan)
    {
        $data = $request->validate([
            'employee_id'        => 'required|exists:employees,id',
            'loan_type_id'       => 'required|exists:loan_types,id',
            'plan_id'            => 'required|exists:loan_plans,id',
            'principal_amount'   => 'required|numeric|min:0.01',
            'interest_rate'      => 'nullable|numeric|min:0',
            'term_months'        => 'nullable|integer|min:1',
            'next_payment_date'  => 'required|date',
            'released_at'        => 'required|date',
            'status'             => 'nullable|in:active,paid,defaulted',
        ]);

        $plan = LoanPlan::findOrFail($data['plan_id']);

        $principal = (float)$data['principal_amount'];
        $rate      = (float)($data['interest_rate'] ?? $plan->interest_rate);

        switch ($plan->deduction_type) {
            case 'semi-monthly': $months = 2; break;
            case 'monthly':      $months = 1; break;
            case 'quarterly':    $months = 3; break;
            case 'one-time':     $months = 1; break;
            default:             $months = 1;
        }

        if (!empty($data['term_months'])) {
            $months = (int)$data['term_months'];
        }

        $total   = round($principal * (1 + ($rate / 100)), 2);
        $monthly = round($total / max(1, $months), 2);

        $loan->update([
            'employee_id'       => $data['employee_id'],
            'loan_type_id'      => $data['loan_type_id'],
            'plan_id'           => $data['plan_id'],
            'principal_amount'  => $principal,
            'interest_rate'     => $rate,
            'term_months'       => $months,
            'total_payable'     => $total,
            'monthly_amount'    => $monthly,
            'next_payment_date' => $data['next_payment_date'],
            'status'            => $data['status'] ?? $loan->status,
            'released_at'       => $data['released_at'],
        ]);

        return redirect()->route('loans.index')->with('success', 'Loan updated successfully.');
    }

    public function destroy(Loan $loan)
    {
        $loan->delete();
        return back()->with('success', 'Loan deleted successfully.');
    }

    public function myLoans(Request $request)
    {
        $userId = Auth::id();
        $employeeId = Employee::where('user_id', $userId)->value('id');

        if (!$employeeId) {
            $loans = Loan::whereRaw('1=0')->paginate(10);
            return view('loans.employee_index', compact('loans'))
                ->with('error', 'No employee profile linked to this account.');
        }

        $loans = Loan::with(['loanType', 'plan'])
            ->where('employee_id', $employeeId)
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderByDesc('released_at')
            ->paginate(10);

        return view('loans.employee_index', compact('loans'));
    }
public function fetchPayments(Loan $loan)
{
    $payments = $loan->payments()
        ->select('payment_date', 'amount', 'penalty')
        ->orderBy('payment_date', 'desc')
        ->get()
        ->map(fn($p) => [
            'payment_date' => $p->payment_date?->format('Y-m-d') ?? '-',
            'amount'       => $p->amount ?? 0,
            'penalty'      => $p->penalty ?? 0,
        ]);

    return response()->json($payments);
}

}
