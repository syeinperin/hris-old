<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\EmployeeRegularized;
use Carbon\Carbon;

class RegularizeProbation extends Controller
{
    /**
     * Automatically regularizes employees whose probationary period has ended
     * and notifies Admin + HR users.
     */
    public function __invoke(): JsonResponse
    {
        $today = Carbon::today();

        // Find probationary employees whose end date has passed
        $toRegularize = Employee::where('employment_type', 'probationary')
            ->whereNotNull('employment_end_date')
            ->whereDate('employment_end_date', '<=', $today)
            ->get();

        if ($toRegularize->isEmpty()) {
            return response()->json([
                'message' => 'No employees due for regularization today.',
                'count'   => 0,
                'updated' => [],
            ]);
        }

        // Notify Admin and HR roles
        $notifyUsers = User::whereIn('role', ['admin', 'hr'])->get();

        $updatedEmployees = [];

        foreach ($toRegularize as $employee) {

            // Regularize employee
            $employee->update([
                'employment_type'     => 'regular',
                'employment_end_date' => null,
            ]);

            // Send notification to admin + hr
            foreach ($notifyUsers as $user) {
                $user->notify(new EmployeeRegularized($employee));
            }

            $updatedEmployees[] = [
                'id'        => $employee->id,
                'name'      => $employee->full_name,
                'code'      => $employee->employee_code,
            ];
        }

        return response()->json([
            'message' => 'Probationary employees successfully regularized.',
            'count'   => count($updatedEmployees),
            'updated' => $updatedEmployees,
        ]);
    }
}
