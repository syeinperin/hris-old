<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;

class EmployeeListController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $employeeCodes = Employee::select('employee_code')->get();

        $employeeCodes->transform(function ($item) {
            return ['employee_code' => $item->employee_code];
        });
        
        return response()->json($employeeCodes);
    }
}
