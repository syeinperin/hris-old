<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ReportController;

class EmployeeSelfReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth'); // no role middleware
    }

    public function coe()
    {
        $employee = auth()->user()->employee;
        abort_unless($employee, 403, 'Employee profile required.');
        return app(ReportController::class)->downloadCertificate($employee); // reuses HR CoE
    }

    public function eis()
    {
        $employee = auth()->user()->employee;
        abort_unless($employee, 403, 'Employee profile required.');
        return app(ReportController::class)->downloadEmployeePdf($employee); // reuses HR EIS
    }
}
