<?php

use App\Http\Controllers\OvertimeRequestController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Auth\{
    LoginController,
    LogoutController,
    ForgotPasswordController,
    ResetPasswordController
};
use App\Models\Employee;
use App\Http\Controllers\{
    Notifications\RegularizeProbation,
    DashboardController,
    EmployeeDashboardController,
    LeaveController,
    PayslipController,
    AttendanceController,
    ReportController,
    ConcernController,
    ApprovalController,
    UserController,
    EmployeeScheduleController,
    DepartmentController,
    DesignationController,
    EmployeeController,
    PayrollController,
    ScheduleController,
    EmployeeDocumentsController,
    ProfileController,
    AuditLogController,
    AnnouncementController,
    LeaveTypeController,
    LeaveAllocationController,
    HolidayController,
    LoanController,
    PaymentController,
    PerformanceEvaluationController,
    EmployeeEvaluationController,
    LateDeductionController,
    CalendarController,
    FaceRecognitionController,
    NotificationController,
    DisciplinaryActionController,
    MyDocumentsController,
    OffboardingController,
    EmployeeTimeCardController,
    DataAnalyticsController,
    DashboardAnalyticsController,
    Api\FingerPrintController


};

// ─────────────────────────────────────────────
// PUBLIC ROUTES
// ─────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('login'));
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

Route::get('password/request', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

// ───────── Attendance Kiosks ─────────
Route::get('kiosk', [AttendanceController::class, 'log'])->name('attendance.kiosk');
Route::post('kiosk', [AttendanceController::class, 'logAttendance'])->name('attendance.kiosk.post');
Route::get('attendance/employee/{code}', [AttendanceController::class, 'employeeInfo'])->name('attendance.employee.info');
Route::get('attendance/code/{name}', [AttendanceController::class, 'employeeCodeFromName'])->name('attendance.employee.code');

Route::get('/kiosk/face', [FaceRecognitionController::class, 'kiosk'])
    ->withoutMiddleware('auth')
    ->name('kiosk.face');

Route::post('/kiosk/face/match', [FaceRecognitionController::class, 'match'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class, 'auth'])
    ->name('kiosk.face.match');

Route::post('/attendance/face-log', [AttendanceController::class, 'faceLog'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class, 'auth'])
    ->name('attendance.faceLog');

Route::post('/fingerprint', FingerPrintController::class)->name('fingerprint.log');

// ───────── Public File Server ─────────
Route::get('files/{path}', function (string $path) {
    $disk = Storage::disk('public');
    abort_unless($disk->exists($path), 404);
    $mime = $disk->mimeType($path) ?? 'application/octet-stream';
    $stream = $disk->readStream($path);
    return response()->stream(fn() => fpassthru($stream), 200, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=31536000, immutable',
        'Content-Disposition' => 'inline',
    ]);
})->where('path', '.*')->name('public.files');



// ─────────────────────────────────────────────
// AUTHENTICATED ROUTES
// ─────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // ───────── Dashboard Routes ─────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/employee/dashboard', [EmployeeDashboardController::class, 'index'])->name('dashboard.employee');
    Route::get('/dashboard/analytics.json', [DashboardAnalyticsController::class, 'summary'])->name('dashboard.analytics.json');

    // ───────── Announcements ─────────
    Route::resource('announcements', AnnouncementController::class);

    // ───────── Documents ─────────
    Route::get('/documents', [EmployeeDocumentsController::class, 'index'])->name('documents.index');
    Route::get('/documents/{document}/view', [EmployeeDocumentsController::class, 'view'])->name('documents.view');
    Route::get('/documents/{document}/download', [EmployeeDocumentsController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{document}', [EmployeeDocumentsController::class, 'destroy'])->name('documents.destroy');
    // ✅ KEEP ONLY THIS
    Route::prefix('my-documents')->name('mydocs.')->group(function () {
        Route::get('/', [MyDocumentsController::class, 'index'])->name('index');
        Route::get('/create', [MyDocumentsController::class, 'create'])->name('create');
        Route::post('/', [MyDocumentsController::class, 'store'])->name('store');

        // ✅ PDF generation must come first
        Route::get('/coe', [MyDocumentsController::class, 'downloadCoE'])->name('coe');
        Route::get('/eis', [MyDocumentsController::class, 'downloadEIS'])->name('eis');

        // Single document actions — keep these last
        Route::get('/{document}', [MyDocumentsController::class, 'show'])->name('show');
        Route::get('/{document}/edit', [MyDocumentsController::class, 'edit'])->name('edit');
        Route::put('/{document}', [MyDocumentsController::class, 'update'])->name('update');
        Route::delete('/{document}', [MyDocumentsController::class, 'destroy'])->name('destroy');
        Route::get('/{document}/download', [MyDocumentsController::class, 'download'])->name('download');

    });

    // ───────── Payroll Routes ─────────
    Route::prefix('payroll')->name('payroll.')->group(function () {

        Route::put('overtime-request/change-status/{overtimerequestId}', [OvertimeRequestController::class, 'changeStatus'])->name('overtimeRequest.changeStatus');
        // Payroll index & calendar
        Route::get('/', [PayrollController::class, 'index'])->name('index');
        Route::get('/calendar', [PayrollController::class, 'calendar'])->name('calendar');

        // Manual payroll create
        Route::post('/', [PayrollController::class, 'store'])->name('store');

        // Manual payroll update
        Route::match(['put', 'patch'], '/{id}', [PayrollController::class, 'update'])->name('update');

        // Employee-specific payroll
        Route::get('/{employee}/show', [PayrollController::class, 'show'])->name('show');
        Route::get('/{employee}/edit', [PayrollController::class, 'edit'])->name('edit');

        // Manual payslip CRUD
        Route::prefix('payslip')->name('payslip.')->group(function () {
            Route::get('/{payslip}/edit', [PayrollController::class, 'editPayslip'])->name('edit');
            Route::put('/{payslip}', [PayrollController::class, 'updatePayslip'])->name('update');
            Route::delete('/{id}', [PayrollController::class, 'deletePayslip'])->name('delete');
        });

        // AJAX utilities
        Route::post('/calendar/biometric', [PayrollController::class, 'markBiometric'])->name('calendar.biometric');
        Route::post('/calendar/toggle-manual', [PayrollController::class, 'toggleManual'])->name('calendar.toggleManual');
        Route::delete('/calendar/remove', [PayrollController::class, 'removeAttendance'])->name('calendar.remove');
        Route::get('/export-pdf', [PayrollController::class, 'exportPdf'])->name('exportPdf');
    });


    Route::get('/payslips', [PayslipController::class, 'index'])->name('payslips.index');
    Route::post('/payslips', [PayslipController::class, 'store'])->name('payslips.store');
    Route::get('/payslips/{payslip}/download', [PayslipController::class, 'download'])->name('payslips.download');

    Route::get('payslips/bulk/pdf', [ReportController::class, 'bulkPayslipsPdf'])->name('reports.pdf.payroll_bulk');
    // AJAX routes for attendance cell actions

    // Attendance-based edits (by date)
    Route::prefix('employee')->name('employee.')->group(function () {
        Route::get('/{employee}/edit-by-date', [PayrollController::class, 'editByDate'])->name('editByDate');
        Route::put('/{employee}/update-by-date', [PayrollController::class, 'updateByDate'])->name('updateByDate');
        Route::get('/lookup', [PayrollController::class, 'employeeLookup'])->name('lookup');
    });

    // Toggle manual flag
    Route::post('/toggle-manual', [PayrollController::class, 'toggleManual'])->name('toggleManual');
});

// ───────── PAYSLIP REPORTS ─────────
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/payslips/list', [PayrollController::class, 'reportPayslips'])->name('payslips.list');
    Route::get('/payslips/{employee}/download', [ReportController::class, 'downloadPayslipRange'])
        ->name('payslips.download');

    // Other HR reports
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('employees', [ReportController::class, 'indexEmployees'])->name('employees.index');
    Route::get('employees/csv', [ReportController::class, 'exportEmployees'])->name('employees.csv');
    Route::get('employees/{employee}/pdf', [ReportController::class, 'downloadEmployeePdf'])->name('employees.pdf');
    Route::get('employees/{employee}/cert', [ReportController::class, 'downloadCertificate'])->name('employees.cert');
    Route::get('attendance', [ReportController::class, 'exportAttendance'])->name('attendance');
    Route::get('payroll', [ReportController::class, 'exportPayroll'])->name('payroll');
    Route::get('payslips', [ReportController::class, 'exportPayslips'])->name('payslips');
    Route::get('leaves', [ReportController::class, 'exportLeaves'])->name('leaves');
    Route::get('performance', [ReportController::class, 'performanceIndex'])->name('performance');
    Route::get('performance/csv', [ReportController::class, 'exportPerformance'])->name('performance.csv');
    Route::get('discipline/csv', [ReportController::class, 'exportDiscipline'])->name('discipline.csv');
    Route::get('analytics', [DataAnalyticsController::class, 'index'])->name('analytics');
    Route::get('/loans', [ReportController::class, 'exportLoans'])->name('loans');
    Route::get('/offboarding', [ReportController::class, 'exportOffboarding'])->name('offboarding');


});

// ───────── Attendance Routes ─────────
Route::resource('attendance', AttendanceController::class)->only(['index', 'show', 'destroy']);
Route::get('attendance/{attendance}/edit', [AttendanceController::class, 'edit'])->name('attendance.edit');
Route::put('attendance/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');

// ───────── Approvals ─────────
Route::get('approvals', [ApprovalController::class, 'index'])->name('approvals.index');
Route::get('approvals/history', [ApprovalController::class, 'history'])->name('approvals.history');
Route::post('approvals/{t}/{id}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
Route::delete('approvals/{t}/{id}', [ApprovalController::class, 'destroy'])->name('approvals.destroy');

Route::post('/leaves/{id}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
Route::post('/leaves/{id}/reject', [LeaveController::class, 'reject'])->name('leaves.reject');


// ───────── Employee Self-Service ─────────
Route::prefix('employee')->name('employee.')->group(function () {

    // 🕒 My Time Card
    Route::prefix('my-timecard')->name('timecard.')->group(function () {
        Route::get('/', [EmployeeTimeCardController::class, 'index'])->name('index'); // employee.timecard.index
        Route::get('/week/{date?}', [EmployeeTimeCardController::class, 'week'])->name('week');
        Route::get('/export/csv', [EmployeeTimeCardController::class, 'exportCsv'])->name('exportCsv');

    });

    Route::resource('overtime-request', OvertimeRequestController::class);
    // 🗓 My Schedule
    Route::prefix('my-schedule')->group(function () {
        Route::get('/', [EmployeeScheduleController::class, 'index'])->name('schedule');
        Route::get('/history', [EmployeeScheduleController::class, 'history'])->name('schedule-history');
    });

    // 💸 My Loans
    Route::get('loans', [LoanController::class, 'myLoans'])->name('loans.index');
});

// ───────── Users & Roles ─────────
Route::match(['patch', 'put'], '/users/{user}/role', [UserController::class, 'updateRole'])->name('users.updateRole');
Route::resource('users', UserController::class)->except(['show', 'update']);
Route::get('users/{user}/password', [UserController::class, 'editPassword'])->name('users.editPassword');
Route::put('users/{user}/password', [UserController::class, 'updatePassword'])->name('users.updatePassword');
Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

// ───────── Core HR CRUD ─────────
Route::resource('departments', DepartmentController::class);
// Department search route
Route::get('/departments/search', [DepartmentController::class, 'search'])->name('departments.search');

Route::resource('designations', DesignationController::class);
Route::resource('loans', LoanController::class);
// Loan payments page
Route::get('loans/{loan}/payments', [LoanController::class, 'payments'])->name('loans.payments');
// Store loan payments
Route::post('loans/{loan}/payments', [PaymentController::class, 'store'])->name('payments.store');
Route::get('/employee/loans/{loan}/payments', [App\Http\Controllers\LoanController::class, 'fetchPayments'])
    ->middleware(['auth'])
    ->name('employee.loans.payments');



// ───────── Schedule Management ─────────
Route::prefix('schedule')->group(function () {
    Route::get('/', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::post('/store', [ScheduleController::class, 'store'])->name('schedule.store');
    Route::put('/{schedule}', [ScheduleController::class, 'update'])->name('schedule.update');
    Route::delete('/{schedule}', [ScheduleController::class, 'destroy'])->name('schedule.destroy');
    Route::post('/assign', [ScheduleController::class, 'assignStore'])->name('attendance.schedule-history');
    Route::post('/restday/all', [ScheduleController::class, 'applyRestDayToAll'])->name('schedule.restday.all');
    Route::get('/history', [ScheduleController::class, 'history'])->name('schedule.history');
    Route::post('/history/store', [ScheduleController::class, 'historyStore'])->name('schedule.history.store');
    Route::put('/history/{assignment}', [ScheduleController::class, 'historyUpdate'])->name('schedule.history.update');
    Route::delete('/history/{assignment}', [ScheduleController::class, 'historyDestroy'])->name('schedule.history.destroy');
    Route::post('/schedule/assign', [ScheduleController::class, 'assignStore'])
        ->name('schedule.assign');

    Route::get('/notify/regularize-probation', [RegularizeProbation::class, '__invoke'])
        ->name('notify.regularize.probation');


    // ───────── Employee Management ─────────
    Route::get('/employees/endings', [EmployeeController::class, 'endings'])->name('employees.endings');
    Route::get('/employees/inactive', [EmployeeController::class, 'inactive'])->name('employees.inactive');
    Route::patch('/employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('employees.restore');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

    Route::resource('employees', EmployeeController::class);
    Route::post('/employees/{employee}/regularize', [EmployeeController::class, 'regularize'])

        ->name('employees.regularize');
    Route::post('/employees/{employee}/extend-probation', [EmployeeController::class, 'extendProbation'])->name('employees.extendProbation');
    Route::post('/employees/{employee}/extend-term', [EmployeeController::class, 'extendTerm'])->name('employees.extendTerm');
    Route::post('/employees/{employee}/extend-season', [EmployeeController::class, 'extendSeason'])->name('employees.extendSeason');
    Route::post('/employees/{employee}/extend-project', [EmployeeController::class, 'extendProject'])->name('employees.extendProject');
    Route::post('/employees/{employee}/extend-casual', [EmployeeController::class, 'extendCasual'])->name('employees.extendCasual');
    Route::post('/employees/{employee}/reject-probation', [EmployeeController::class, 'rejectProbation'])->name('employees.rejectProbation');
    Route::delete('/employees/{employee}/terminate', [EmployeeController::class, 'terminate'])->name('employees.terminate');

    // ───── Info (AJAX modal fetch) ─────
    Route::get('/employees/{employee}/info', [EmployeeController::class, 'info'])->name('employees.info');



    // ───────── Leaves & Deductions ─────────
    Route::get('/leaves/check-balance/{typeId}', [LeaveController::class, 'checkBalance'])
        ->name('leaves.check-balance');
    Route::resource('leaves', LeaveController::class);
    Route::resource('leave-types', LeaveTypeController::class);
    Route::resource('leave-allocations', LeaveAllocationController::class);
    Route::resource('late-deductions', LateDeductionController::class);



    // ───────── Audit Logs ─────────
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('audit-logs/{id}', [AuditLogController::class, 'show'])->name('audit-logs.show');

    // ───────── Settings / Profile ─────────
    Route::get('/settings', [ProfileController::class, 'edit'])->name('settings');
    Route::put('/settings', [ProfileController::class, 'update'])->name('settings.update');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // ───────── Holidays ─────────
    Route::resource('holidays', HolidayController::class);

    // ───────── Notifications ─────────
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('{id}', [NotificationController::class, 'show'])->name('show');
        Route::post('{id}/mark-read', [NotificationController::class, 'markRead'])->name('markRead');
        Route::post('mark-all-read', [NotificationController::class, 'markAllRead'])->name('markAllRead');
    });

    // ───────── Face Recognition Internal ─────────
    Route::get('/face', [FaceRecognitionController::class, 'index'])->name('face.index');
    Route::get('/face/enroll', [FaceRecognitionController::class, 'enroll'])->name('face.enroll');
    Route::post('/face/enroll', [FaceRecognitionController::class, 'enrollStore'])->name('face.enroll.store');
    Route::delete('/face/templates/{template}', [FaceRecognitionController::class, 'destroy'])->name('face.templates.destroy');
    Route::get('/face/attendance', [FaceRecognitionController::class, 'attendance'])->name('face.attendance');
    Route::post('/face/match', [FaceRecognitionController::class, 'match'])->name('face.match');

    // ───────── Offboarding ─────────
    Route::prefix('offboarding')->name('offboarding.')->group(function () {
        Route::get('/', [OffboardingController::class, 'index'])->name('index');         // list page
        Route::get('/create', [OffboardingController::class, 'create'])->name('create'); // creation form
        Route::post('/', [OffboardingController::class, 'store'])->name('store');
        Route::post('/{id}/approve', [OffboardingController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [OffboardingController::class, 'reject'])->name('reject');
        Route::delete('/{offboarding}', [OffboardingController::class, 'destroy'])->name('destroy');
    });

    // ───────── Discipline ─────────
    Route::prefix('discipline')->name('discipline.')->group(function () {
        Route::get('/', [DisciplinaryActionController::class, 'index'])->name('index');
        Route::get('/create', [DisciplinaryActionController::class, 'create'])->name('create');
        Route::post('/', [DisciplinaryActionController::class, 'store'])->name('store');
        Route::put('/{action}/resolve', [DisciplinaryActionController::class, 'resolve'])->name('resolve');
        Route::delete('/{action}', [DisciplinaryActionController::class, 'destroy'])->name('destroy');
        Route::get('/{action}/pdf', [DisciplinaryActionController::class, 'pdf'])->name('pdf');
    });
    // ───────── Evaluations ─────────
    Route::prefix('evaluations')->name('evaluations.')->group(function () {
        Route::get('/', [PerformanceEvaluationController::class, 'index'])->name('index');
        Route::post('/', [PerformanceEvaluationController::class, 'store'])->name('store');
        Route::get('/{evaluation}', [PerformanceEvaluationController::class, 'show'])->name('show');
        Route::get('/{evaluation}/view', [PerformanceEvaluationController::class, 'show'])->name('partials.show');
        Route::get('/{evaluation}/edit', [PerformanceEvaluationController::class, 'edit'])->name('edit');
        Route::put('/{evaluation}', [PerformanceEvaluationController::class, 'update'])->name('update');
        Route::delete('/{evaluation}', [PerformanceEvaluationController::class, 'destroy'])->name('destroy');
        Route::get('/filter/employees', [PerformanceEvaluationController::class, 'filterEmployees'])->name('filterEmployees');
    });
    Route::get('/my-evaluations', [App\Http\Controllers\EmployeeEvaluationController::class, 'index'])
        ->name('my.evaluations.index');

    Route::get('/my-evaluations/{evaluation}', [App\Http\Controllers\EmployeeEvaluationController::class, 'show'])
        ->name('my.evaluations.show');

    // Work-related Concerns
    Route::get('/concerns', [ConcernController::class, 'index'])->name('concerns.index');
    Route::post('/concerns', [ConcernController::class, 'store'])->name('concerns.store');
    Route::get('/concerns/{concern}', [ConcernController::class, 'show'])->name('concerns.show');
    Route::post('/concerns/{concern}/reply', [ConcernController::class, 'reply'])->name('concerns.reply');
    Route::post('/concerns/{concern}/status', [ConcernController::class, 'updateStatus'])->name('concerns.updateStatus');
    Route::delete('/concerns/{concern}', [ConcernController::class, 'destroy'])->name('concerns.destroy');



});
