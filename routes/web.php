<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

// ── Public job board (unauthenticated) ────────────────────────────────────────
Route::get('jobs', [\App\Http\Controllers\Admin\Recruitment\JobPostingController::class, 'publicIndex'])->name('jobs.index');
Route::get('jobs/{posting}', [\App\Http\Controllers\Admin\Recruitment\JobPostingController::class, 'publicShow'])->name('jobs.show');
Route::post('jobs/{posting}/apply', [\App\Http\Controllers\Admin\Recruitment\JobApplicationController::class, 'apply'])->name('jobs.apply');

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Attendance
    Route::post('/attendance/check-in', [\App\Http\Controllers\Staff\AttendanceController::class, 'checkIn'])->name('attendance.check-in');
    Route::post('/attendance/check-out', [\App\Http\Controllers\Staff\AttendanceController::class, 'checkOut'])->name('attendance.check-out');

    // Chat (direct messages)
    Route::get('chat', [App\Http\Controllers\ChatController::class, 'index'])->name('chat.index');
    Route::get('chat/messages/{userId}', [App\Http\Controllers\ChatController::class, 'getMessages'])->name('chat.messages');
    Route::post('chat/send', [App\Http\Controllers\ChatController::class, 'sendMessage'])->name('chat.send');
    Route::get('chat/unread-count', [App\Http\Controllers\ChatController::class, 'unreadCount'])->name('chat.unread-count');

    // Chat channels (group)
    Route::get('channels', [App\Http\Controllers\ChatChannelController::class, 'index'])->name('chat.channels.index');
    Route::post('channels', [App\Http\Controllers\ChatChannelController::class, 'store'])->name('chat.channels.store');
    Route::get('channels/{channel}', [App\Http\Controllers\ChatChannelController::class, 'show'])->name('chat.channels.show');
    Route::post('channels/{channel}/messages', [App\Http\Controllers\ChatChannelController::class, 'sendMessage'])->name('chat.channels.send');
    Route::get('channels/{channel}/messages', [App\Http\Controllers\ChatChannelController::class, 'messages'])->name('chat.channels.messages');
    Route::post('channels/{channel}/join', [App\Http\Controllers\ChatChannelController::class, 'join'])->name('chat.channels.join');
    Route::post('channels/{channel}/read', [App\Http\Controllers\ChatChannelController::class, 'markRead'])->name('chat.channels.read');

    // Mail
    Route::get('mail', [App\Http\Controllers\MailController::class, 'index'])->name('mail.index');
    Route::get('mail/sent', [App\Http\Controllers\MailController::class, 'sent'])->name('mail.sent');
    Route::get('mail/drafts', [App\Http\Controllers\MailController::class, 'drafts'])->name('mail.drafts');
    Route::get('mail/create', [App\Http\Controllers\MailController::class, 'create'])->name('mail.create');
    Route::post('mail', [App\Http\Controllers\MailController::class, 'store'])->name('mail.store');
    Route::get('mail/unread-count', [App\Http\Controllers\MailController::class, 'unreadCount'])->name('mail.unread-count');
    Route::get('mail/{email}', [App\Http\Controllers\MailController::class, 'show'])->name('mail.show');
    Route::delete('mail/{email}', [App\Http\Controllers\MailController::class, 'destroy'])->name('mail.destroy');

    // Task comments
    Route::post('tasks/{task}/comments', [\App\Http\Controllers\Staff\TaskCommentController::class, 'store'])->name('tasks.comments.store');
    Route::delete('task-comments/{comment}', [\App\Http\Controllers\Staff\TaskCommentController::class, 'destroy'])->name('tasks.comments.destroy');

    // Staff self-service profile
    Route::get('my-profile', [\App\Http\Controllers\Staff\ProfileController::class, 'index'])->name('staff.profile.index');
    Route::get('my-profile/documents/{document}/download', [\App\Http\Controllers\Staff\ProfileController::class, 'downloadDocument'])->name('staff.profile.document.download');

    // Notifications
    Route::get('notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::get('notifications/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::get('notifications/recent', [\App\Http\Controllers\NotificationController::class, 'recent'])->name('notifications.recent');

    // Staff tasks & work
    Route::get('/my-tasks', [\App\Http\Controllers\Staff\TaskController::class, 'index'])->name('staff.tasks.index');
    Route::put('/my-tasks/{task}', [\App\Http\Controllers\Staff\TaskController::class, 'update'])->name('staff.tasks.update');

    Route::resource('test-cases', \App\Http\Controllers\Staff\TestCaseController::class, ['as' => 'staff']);
    Route::resource('bugs', \App\Http\Controllers\Staff\BugController::class, ['as' => 'staff']);
    Route::post('time-tracker/start', [App\Http\Controllers\Staff\TimeTrackerController::class, 'start'])->name('time-tracker.start');
    Route::post('time-tracker/stop', [App\Http\Controllers\Staff\TimeTrackerController::class, 'stop'])->name('time-tracker.stop');
    Route::get('time-tracker/active', [App\Http\Controllers\Staff\TimeTrackerController::class, 'active'])->name('time-tracker.active');
    Route::get('time-tracker/categories', [App\Http\Controllers\Staff\TimeTrackerController::class, 'categories'])->name('time-tracker.categories');
    Route::resource('daily-status-reports', \App\Http\Controllers\Staff\DailyStatusReportController::class, ['as' => 'staff']);

    // Leave requests (staff)
    Route::resource('leaves', \App\Http\Controllers\Staff\LeaveController::class, ['as' => 'staff'])
        ->only(['index', 'create', 'store', 'show', 'destroy'])
        ->parameters(['leaves' => 'leave']);

    // Team leave calendar (staff-visible)
    Route::get('leaves/team-calendar', [\App\Http\Controllers\Staff\LeaveController::class, 'teamCalendar'])->name('staff.leaves.team-calendar');

    // Kanban board (all authenticated users — staff see their own tasks)
    Route::get('/kanban', [\App\Http\Controllers\Staff\KanbanController::class, 'index'])->name('kanban.index');
    Route::post('/kanban/update-status/{id}', [\App\Http\Controllers\Staff\KanbanController::class, 'updateStatus'])->name('kanban.update-status');

    // ── Staff self-service pages ───────────────────────────────────────────────

    // Attendance history
    Route::get('/my-attendance', [\App\Http\Controllers\Staff\AttendanceHistoryController::class, 'index'])->name('staff.attendance.index');

    // Personal time log
    Route::get('/my-time-log',              [\App\Http\Controllers\Staff\TimeLogController::class, 'index'])->name('staff.time-log.index');
    Route::post('/my-time-log',             [\App\Http\Controllers\Staff\TimeLogController::class, 'store'])->name('staff.time-log.store');
    Route::delete('/my-time-log/{entry}',   [\App\Http\Controllers\Staff\TimeLogController::class, 'destroy'])->name('staff.time-log.destroy');

    // Payslips
    Route::get('/my-payslips', [\App\Http\Controllers\Staff\PayslipController::class, 'index'])->name('staff.payslips.index');

    // My shifts + shift change requests
    Route::get('/my-shifts',                                                         [\App\Http\Controllers\Staff\MyShiftController::class, 'index'])->name('staff.shifts.index');
    Route::post('/my-shifts/change-request',                                         [\App\Http\Controllers\Staff\MyShiftController::class, 'requestChange'])->name('staff.shifts.change-request');
    Route::delete('/my-shifts/change-request/{changeRequest}/cancel',                [\App\Http\Controllers\Staff\MyShiftController::class, 'cancelRequest'])->name('staff.shifts.cancel-request');

    // Staff performance (self-service)
    Route::prefix('performance')->name('staff.performance.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Staff\PerformanceController::class, 'index'])->name('index');
        Route::get('/{review}', [\App\Http\Controllers\Staff\PerformanceController::class, 'show'])->name('show');
        Route::post('/{review}/self-assessment', [\App\Http\Controllers\Staff\PerformanceController::class, 'selfAssessment'])->name('self-assessment');
        Route::post('/{review}/acknowledge', [\App\Http\Controllers\Staff\PerformanceController::class, 'acknowledge'])->name('acknowledge');
    });

    // Admin panel — admins get full access; PMs get permission-gated access
    Route::middleware(['role:admin,pm'])->prefix('admin')->name('admin.')->group(function () {

        // ── Admin-exclusive routes (role:admin required) ───────────────────────
        Route::middleware(['role:admin'])->group(function () {
            Route::get('settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
            Route::post('settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');
            Route::resource('departments', \App\Http\Controllers\Admin\DepartmentController::class);
            Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class);
            Route::get('roles-matrix', [\App\Http\Controllers\Admin\RoleController::class, 'matrix'])->name('roles.matrix');
            Route::post('roles-matrix', [\App\Http\Controllers\Admin\RoleController::class, 'updateMatrix'])->name('roles.matrix.update');

            // Staff import + create/edit/delete (view is permission-gated below)
            Route::get('staff/import', [\App\Http\Controllers\Admin\StaffController::class, 'importForm'])->name('staff.import');
            Route::post('staff/import', [\App\Http\Controllers\Admin\StaffController::class, 'import'])->name('staff.import.upload');
            Route::resource('staff', \App\Http\Controllers\Admin\StaffController::class)->except(['index', 'show']);

            // Leave configuration
            Route::prefix('leave-types')->name('leaves.types.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\Leave\LeaveTypeController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Admin\Leave\LeaveTypeController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Admin\Leave\LeaveTypeController::class, 'store'])->name('store');
                Route::get('/{type}/edit', [\App\Http\Controllers\Admin\Leave\LeaveTypeController::class, 'edit'])->name('edit');
                Route::put('/{type}', [\App\Http\Controllers\Admin\Leave\LeaveTypeController::class, 'update'])->name('update');
                Route::delete('/{type}', [\App\Http\Controllers\Admin\Leave\LeaveTypeController::class, 'destroy'])->name('destroy');
            });
            Route::prefix('leave-policies')->name('leaves.policies.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\Leave\LeavePolicyController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Admin\Leave\LeavePolicyController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Admin\Leave\LeavePolicyController::class, 'store'])->name('store');
                Route::delete('/{policy}', [\App\Http\Controllers\Admin\Leave\LeavePolicyController::class, 'destroy'])->name('destroy');
            });
            Route::prefix('leave-balances')->name('leaves.balances.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\Leave\LeaveBalanceController::class, 'index'])->name('index');
                Route::post('/adjust', [\App\Http\Controllers\Admin\Leave\LeaveBalanceController::class, 'adjust'])->name('adjust');
                Route::post('/run-accrual', [\App\Http\Controllers\Admin\Leave\LeaveBalanceController::class, 'runAccrual'])->name('run-accrual');
            });

            // Announcements
            Route::resource('announcements', \App\Http\Controllers\Admin\AnnouncementController::class);

            // Time tracking (admin-managed)
            Route::prefix('time')->name('time.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\TimeTrackerController::class, 'index'])->name('index');
                Route::delete('{entry}', [\App\Http\Controllers\Admin\TimeTrackerController::class, 'destroy'])->name('destroy');
                Route::resource('categories', \App\Http\Controllers\Admin\Time\TimeCategoryController::class)->except(['show'])->names('categories');
                Route::get('rates', [\App\Http\Controllers\Admin\Time\BillableRateController::class, 'index'])->name('rates.index');
                Route::get('rates/create', [\App\Http\Controllers\Admin\Time\BillableRateController::class, 'create'])->name('rates.create');
                Route::post('rates', [\App\Http\Controllers\Admin\Time\BillableRateController::class, 'store'])->name('rates.store');
                Route::delete('rates/{rate}', [\App\Http\Controllers\Admin\Time\BillableRateController::class, 'destroy'])->name('rates.destroy');
                Route::prefix('reports')->name('reports.')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Admin\Time\TimeReportController::class, 'index'])->name('index');
                    Route::get('utilization', [\App\Http\Controllers\Admin\Time\TimeReportController::class, 'utilization'])->name('utilization');
                    Route::get('revenue', [\App\Http\Controllers\Admin\Time\TimeReportController::class, 'revenue'])->name('revenue');
                    Route::get('export', [\App\Http\Controllers\Admin\Time\TimeReportController::class, 'export'])->name('export');
                });
            });

            // Time entry approvals
            Route::prefix('time-entries')->name('time-entries.')->group(function () {
                Route::get('approvals', [\App\Http\Controllers\Admin\Time\TimeEntryApprovalController::class, 'index'])->name('approvals.index');
                Route::post('approvals/{entry}/approve', [\App\Http\Controllers\Admin\Time\TimeEntryApprovalController::class, 'approve'])->name('approvals.approve');
                Route::post('approvals/{entry}/reject', [\App\Http\Controllers\Admin\Time\TimeEntryApprovalController::class, 'reject'])->name('approvals.reject');
            });

            // Payroll bank export
            Route::get('payroll/bank-export', [\App\Http\Controllers\Admin\PayrollBankExportController::class, 'index'])->name('payroll.bank-export');
            Route::post('payroll/bank-export/generate', [\App\Http\Controllers\Admin\PayrollBankExportController::class, 'generate'])->name('payroll.bank-export.generate');

            // Payroll (incl. publish — admin-exclusive)
            Route::prefix('payroll')->name('payroll.')->group(function () {
                Route::get('dashboard', [\App\Http\Controllers\Admin\Payroll\PayrollController::class, 'dashboard'])->name('dashboard');
                Route::get('salary-structures', [\App\Http\Controllers\Admin\Payroll\SalaryStructureController::class, 'index'])->name('salary-structures.index');
                Route::get('salary-structures/create', [\App\Http\Controllers\Admin\Payroll\SalaryStructureController::class, 'create'])->name('salary-structures.create');
                Route::post('salary-structures', [\App\Http\Controllers\Admin\Payroll\SalaryStructureController::class, 'store'])->name('salary-structures.store');
                Route::get('salary-structures/{salaryStructure}/edit', [\App\Http\Controllers\Admin\Payroll\SalaryStructureController::class, 'edit'])->name('salary-structures.edit');
                Route::put('salary-structures/{salaryStructure}', [\App\Http\Controllers\Admin\Payroll\SalaryStructureController::class, 'update'])->name('salary-structures.update');
                Route::get('salary-structures/{salaryStructure}/revisions', [\App\Http\Controllers\Admin\Payroll\SalaryStructureController::class, 'revisions'])->name('salary-structures.revisions');
                Route::get('runs', [\App\Http\Controllers\Admin\Payroll\PayrollController::class, 'index'])->name('runs.index');
                Route::post('runs/initiate', [\App\Http\Controllers\Admin\Payroll\PayrollController::class, 'initiateRun'])->name('runs.initiate');
                Route::post('runs/{payrollRun}/process', [\App\Http\Controllers\Admin\Payroll\PayrollController::class, 'processPayroll'])->name('runs.process');
                Route::post('runs/{payrollRun}/publish', [\App\Http\Controllers\Admin\Payroll\PayrollController::class, 'publishSlips'])->name('runs.publish');
                Route::get('runs/{payrollRun}/status', [\App\Http\Controllers\Admin\Payroll\PayrollController::class, 'viewStatus'])->name('runs.status');
                Route::get('tax-regimes', [\App\Http\Controllers\Admin\Payroll\TaxRegimeController::class, 'index'])->name('tax-regimes.index');
                Route::get('tax-regimes/create', [\App\Http\Controllers\Admin\Payroll\TaxRegimeController::class, 'create'])->name('tax-regimes.create');
                Route::post('tax-regimes', [\App\Http\Controllers\Admin\Payroll\TaxRegimeController::class, 'store'])->name('tax-regimes.store');
                Route::get('tax-regimes/{taxRegime}', [\App\Http\Controllers\Admin\Payroll\TaxRegimeController::class, 'show'])->name('tax-regimes.show');
                Route::get('tax-regimes/{taxRegime}/edit', [\App\Http\Controllers\Admin\Payroll\TaxRegimeController::class, 'edit'])->name('tax-regimes.edit');
                Route::put('tax-regimes/{taxRegime}', [\App\Http\Controllers\Admin\Payroll\TaxRegimeController::class, 'update'])->name('tax-regimes.update');
                Route::delete('tax-regimes/{taxRegime}', [\App\Http\Controllers\Admin\Payroll\TaxRegimeController::class, 'destroy'])->name('tax-regimes.destroy');
                Route::get('adjustments', [\App\Http\Controllers\Admin\Payroll\PayrollAdjustmentController::class, 'index'])->name('adjustments.index');
                Route::get('adjustments/create', [\App\Http\Controllers\Admin\Payroll\PayrollAdjustmentController::class, 'create'])->name('adjustments.create');
                Route::post('adjustments', [\App\Http\Controllers\Admin\Payroll\PayrollAdjustmentController::class, 'store'])->name('adjustments.store');
                Route::get('adjustments/{adjustment}', [\App\Http\Controllers\Admin\Payroll\PayrollAdjustmentController::class, 'show'])->name('adjustments.show');
                Route::post('adjustments/{adjustment}/approve', [\App\Http\Controllers\Admin\Payroll\PayrollAdjustmentController::class, 'approve'])->name('adjustments.approve');
                Route::post('adjustments/{adjustment}/reject', [\App\Http\Controllers\Admin\Payroll\PayrollAdjustmentController::class, 'reject'])->name('adjustments.reject');
                Route::post('adjustments/{adjustment}/cancel', [\App\Http\Controllers\Admin\Payroll\PayrollAdjustmentController::class, 'cancel'])->name('adjustments.cancel');
                Route::post('settlements/initiate', [\App\Http\Controllers\Admin\Payroll\SettlementController::class, 'initiate'])->name('settlements.initiate');
                Route::post('settlements/{termination}/finalize', [\App\Http\Controllers\Admin\Payroll\SettlementController::class, 'finalize'])->name('settlements.finalize');
                Route::get('loans', [\App\Http\Controllers\Admin\Payroll\LoanController::class, 'index'])->name('loans.index');
                Route::get('loans/create', [\App\Http\Controllers\Admin\Payroll\LoanController::class, 'create'])->name('loans.create');
                Route::post('loans', [\App\Http\Controllers\Admin\Payroll\LoanController::class, 'store'])->name('loans.store');
                Route::get('loans/{loan}', [\App\Http\Controllers\Admin\Payroll\LoanController::class, 'show'])->name('loans.show');
                Route::post('loans/{loan}/cancel', [\App\Http\Controllers\Admin\Payroll\LoanController::class, 'cancel'])->name('loans.cancel');
            });

            // Shift management
            Route::prefix('shifts')->name('shifts.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\Shift\ShiftDashboardController::class, 'index'])->name('dashboard');
                Route::get('definitions', [\App\Http\Controllers\Admin\Shift\ShiftController::class, 'index'])->name('index');
                Route::get('definitions/create', [\App\Http\Controllers\Admin\Shift\ShiftController::class, 'create'])->name('create');
                Route::post('definitions', [\App\Http\Controllers\Admin\Shift\ShiftController::class, 'store'])->name('store');
                Route::get('definitions/{shift}', [\App\Http\Controllers\Admin\Shift\ShiftController::class, 'show'])->name('show');
                Route::get('definitions/{shift}/edit', [\App\Http\Controllers\Admin\Shift\ShiftController::class, 'edit'])->name('edit');
                Route::put('definitions/{shift}', [\App\Http\Controllers\Admin\Shift\ShiftController::class, 'update'])->name('update');
                Route::delete('definitions/{shift}', [\App\Http\Controllers\Admin\Shift\ShiftController::class, 'destroy'])->name('destroy');
                Route::get('assignments', [\App\Http\Controllers\Admin\Shift\ShiftAssignmentController::class, 'index'])->name('assignments.index');
                Route::get('assignments/create', [\App\Http\Controllers\Admin\Shift\ShiftAssignmentController::class, 'create'])->name('assignments.create');
                Route::post('assignments', [\App\Http\Controllers\Admin\Shift\ShiftAssignmentController::class, 'store'])->name('assignments.store');
                Route::delete('assignments/{assignment}', [\App\Http\Controllers\Admin\Shift\ShiftAssignmentController::class, 'destroy'])->name('assignments.destroy');
                Route::get('exceptions', [\App\Http\Controllers\Admin\Shift\AttendanceExceptionController::class, 'index'])->name('exceptions.index');
                Route::post('exceptions/bulk-approve', [\App\Http\Controllers\Admin\Shift\AttendanceExceptionController::class, 'bulkApprove'])->name('exceptions.bulk-approve');
                Route::post('exceptions/validate-date', [\App\Http\Controllers\Admin\Shift\AttendanceExceptionController::class, 'validateDate'])->name('exceptions.validate-date');
                Route::post('exceptions/{exception}/approve', [\App\Http\Controllers\Admin\Shift\AttendanceExceptionController::class, 'approve'])->name('exceptions.approve');
                Route::post('exceptions/{exception}/reject', [\App\Http\Controllers\Admin\Shift\AttendanceExceptionController::class, 'reject'])->name('exceptions.reject');
                Route::get('change-requests', [\App\Http\Controllers\Admin\Shift\ShiftChangeRequestController::class, 'index'])->name('change-requests.index');
                Route::post('change-requests/{changeRequest}/approve', [\App\Http\Controllers\Admin\Shift\ShiftChangeRequestController::class, 'approve'])->name('change-requests.approve');
                Route::post('change-requests/{changeRequest}/reject', [\App\Http\Controllers\Admin\Shift\ShiftChangeRequestController::class, 'reject'])->name('change-requests.reject');
                Route::get('holidays', [\App\Http\Controllers\Admin\Shift\ShiftHolidayController::class, 'index'])->name('holidays.index');
                Route::post('holidays', [\App\Http\Controllers\Admin\Shift\ShiftHolidayController::class, 'store'])->name('holidays.store');
                Route::delete('holidays/{holiday}', [\App\Http\Controllers\Admin\Shift\ShiftHolidayController::class, 'destroy'])->name('holidays.destroy');
            });

            // HR management
            Route::prefix('hr')->name('hr.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\HR\HRDashboardController::class, 'index'])->name('dashboard');
                Route::get('employees', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'index'])->name('employees.index');
                Route::get('employees/{employee}', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'show'])->name('employees.show');
                Route::get('employees/{employee}/edit', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'editProfile'])->name('employees.edit');
                Route::put('employees/{employee}', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'updateProfile'])->name('employees.update');
                Route::post('employees/{employee}/education', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'storeEducation'])->name('employees.education.store');
                Route::delete('employees/{employee}/education/{education}', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'destroyEducation'])->name('employees.education.destroy');
                Route::post('employees/{employee}/experience', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'storeExperience'])->name('employees.experience.store');
                Route::delete('employees/{employee}/experience/{experience}', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'destroyExperience'])->name('employees.experience.destroy');
                Route::post('employees/{employee}/skills', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'storeSkill'])->name('employees.skills.store');
                Route::delete('employees/{employee}/skills/{skill}', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'destroySkill'])->name('employees.skills.destroy');
                Route::post('employees/{employee}/documents', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'storeDocument'])->name('employees.documents.store');
                Route::delete('employees/{employee}/documents/{document}', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'destroyDocument'])->name('employees.documents.destroy');
                Route::post('employees/{employee}/contacts', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'storeEmergencyContact'])->name('employees.contacts.store');
                Route::delete('employees/{employee}/contacts/{contact}', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'destroyEmergencyContact'])->name('employees.contacts.destroy');
                Route::resource('promotions', \App\Http\Controllers\Admin\HR\PromotionController::class)->except(['edit', 'update']);
                Route::post('promotions/{promotion}/approve', [\App\Http\Controllers\Admin\HR\PromotionController::class, 'approve'])->name('promotions.approve');
                Route::get('resignations', [\App\Http\Controllers\Admin\HR\ResignationController::class, 'index'])->name('resignations.index');
                Route::post('resignations', [\App\Http\Controllers\Admin\HR\ResignationController::class, 'store'])->name('resignations.store');
                Route::get('resignations/{resignation}', [\App\Http\Controllers\Admin\HR\ResignationController::class, 'show'])->name('resignations.show');
                Route::post('resignations/{resignation}/manager-decision', [\App\Http\Controllers\Admin\HR\ResignationController::class, 'managerDecision'])->name('resignations.manager-decision');
                Route::post('resignations/{resignation}/hr-approve', [\App\Http\Controllers\Admin\HR\ResignationController::class, 'hrApprove'])->name('resignations.hr-approve');
                Route::post('resignations/{resignation}/withdraw', [\App\Http\Controllers\Admin\HR\ResignationController::class, 'withdraw'])->name('resignations.withdraw');
                Route::get('terminations', [\App\Http\Controllers\Admin\HR\TerminationController::class, 'index'])->name('terminations.index');
                Route::get('terminations/create', [\App\Http\Controllers\Admin\HR\TerminationController::class, 'create'])->name('terminations.create');
                Route::post('terminations', [\App\Http\Controllers\Admin\HR\TerminationController::class, 'store'])->name('terminations.store');
                Route::get('terminations/{termination}', [\App\Http\Controllers\Admin\HR\TerminationController::class, 'show'])->name('terminations.show');
                Route::post('terminations/{termination}/approve', [\App\Http\Controllers\Admin\HR\TerminationController::class, 'approve'])->name('terminations.approve');
                Route::post('terminations/{termination}/checklist/{item}/complete', [\App\Http\Controllers\Admin\HR\TerminationController::class, 'completeChecklistItem'])->name('terminations.checklist.complete');
                Route::post('terminations/{termination}/settlement/calculate', [\App\Http\Controllers\Admin\HR\TerminationController::class, 'calculateSettlement'])->name('terminations.settlement.calculate');
                Route::post('terminations/{termination}/settlement/approve', [\App\Http\Controllers\Admin\HR\TerminationController::class, 'approveSettlement'])->name('terminations.settlement.approve');
                Route::post('terminations/{termination}/finalize', [\App\Http\Controllers\Admin\HR\TerminationController::class, 'finalize'])->name('terminations.finalize');
                Route::get('transfers', [\App\Http\Controllers\Admin\HR\TransferController::class, 'index'])->name('transfers.index');
                Route::get('transfers/create', [\App\Http\Controllers\Admin\HR\TransferController::class, 'create'])->name('transfers.create');
                Route::post('transfers', [\App\Http\Controllers\Admin\HR\TransferController::class, 'store'])->name('transfers.store');
                Route::get('transfers/{transfer}', [\App\Http\Controllers\Admin\HR\TransferController::class, 'show'])->name('transfers.show');
                Route::post('transfers/{transfer}/approve', [\App\Http\Controllers\Admin\HR\TransferController::class, 'approve'])->name('transfers.approve');
                Route::post('transfers/{transfer}/reject', [\App\Http\Controllers\Admin\HR\TransferController::class, 'reject'])->name('transfers.reject');
                Route::get('warnings', [\App\Http\Controllers\Admin\HR\WarningController::class, 'index'])->name('warnings.index');
                Route::get('warnings/create', [\App\Http\Controllers\Admin\HR\WarningController::class, 'create'])->name('warnings.create');
                Route::post('warnings', [\App\Http\Controllers\Admin\HR\WarningController::class, 'store'])->name('warnings.store');
                Route::get('warnings/{warning}', [\App\Http\Controllers\Admin\HR\WarningController::class, 'show'])->name('warnings.show');
                Route::delete('warnings/{warning}', [\App\Http\Controllers\Admin\HR\WarningController::class, 'destroy'])->name('warnings.destroy');
                Route::post('warnings/{warning}/resolve', [\App\Http\Controllers\Admin\HR\WarningController::class, 'resolve'])->name('warnings.resolve');
                Route::post('warnings/{warning}/acknowledge', [\App\Http\Controllers\Admin\HR\WarningController::class, 'acknowledge'])->name('warnings.acknowledge');
            });

            // Monitoring settings & tokens
            Route::prefix('monitoring')->name('monitoring.')->group(function () {
                Route::get('settings', [\App\Http\Controllers\Admin\Monitoring\MonitoringSettingController::class, 'index'])->name('settings.index');
                Route::post('settings', [\App\Http\Controllers\Admin\Monitoring\MonitoringSettingController::class, 'store'])->name('settings.store');
                Route::delete('settings/{setting}', [\App\Http\Controllers\Admin\Monitoring\MonitoringSettingController::class, 'destroy'])->name('settings.destroy');
                Route::post('tokens/{user}/generate', [\App\Http\Controllers\Admin\Monitoring\MonitoringSettingController::class, 'generateToken'])->name('tokens.generate');
                Route::delete('tokens/{user}/revoke', [\App\Http\Controllers\Admin\Monitoring\MonitoringSettingController::class, 'revokeToken'])->name('tokens.revoke');
            });

            // Performance cycles & admin review management
            Route::prefix('performance')->name('performance.')->group(function () {
                Route::get('cycles', [\App\Http\Controllers\Admin\Performance\PerformanceCycleController::class, 'index'])->name('cycles.index');
                Route::get('cycles/create', [\App\Http\Controllers\Admin\Performance\PerformanceCycleController::class, 'create'])->name('cycles.create');
                Route::post('cycles', [\App\Http\Controllers\Admin\Performance\PerformanceCycleController::class, 'store'])->name('cycles.store');
                Route::get('cycles/{cycle}', [\App\Http\Controllers\Admin\Performance\PerformanceCycleController::class, 'show'])->name('cycles.show');
                Route::post('cycles/{cycle}/close', [\App\Http\Controllers\Admin\Performance\PerformanceCycleController::class, 'close'])->name('cycles.close');
                Route::post('cycles/{cycle}/reviews', [\App\Http\Controllers\Admin\Performance\PerformanceReviewController::class, 'store'])->name('reviews.store');
                Route::get('reviews/{review}', [\App\Http\Controllers\Admin\Performance\PerformanceReviewController::class, 'show'])->name('reviews.show');
                Route::post('reviews/{review}/submit', [\App\Http\Controllers\Admin\Performance\PerformanceReviewController::class, 'submit'])->name('reviews.submit');
            });

            // Recruitment
            Route::prefix('recruitment')->name('recruitment.')->group(function () {
                Route::get('postings', [\App\Http\Controllers\Admin\Recruitment\JobPostingController::class, 'index'])->name('postings.index');
                Route::get('postings/create', [\App\Http\Controllers\Admin\Recruitment\JobPostingController::class, 'create'])->name('postings.create');
                Route::post('postings', [\App\Http\Controllers\Admin\Recruitment\JobPostingController::class, 'store'])->name('postings.store');
                Route::get('postings/{posting}', [\App\Http\Controllers\Admin\Recruitment\JobPostingController::class, 'show'])->name('postings.show');
                Route::post('postings/{posting}/publish', [\App\Http\Controllers\Admin\Recruitment\JobPostingController::class, 'publish'])->name('postings.publish');
                Route::post('postings/{posting}/close', [\App\Http\Controllers\Admin\Recruitment\JobPostingController::class, 'close'])->name('postings.close');
                Route::get('applications/{application}', [\App\Http\Controllers\Admin\Recruitment\JobApplicationController::class, 'show'])->name('applications.show');
                Route::post('applications/{application}/status', [\App\Http\Controllers\Admin\Recruitment\JobApplicationController::class, 'updateStatus'])->name('applications.status');
                Route::post('applications/{application}/hire', [\App\Http\Controllers\Admin\Recruitment\JobApplicationController::class, 'hire'])->name('applications.hire');
            });

            // Onboarding
            Route::prefix('onboarding')->name('onboarding.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\HR\OnboardingController::class, 'index'])->name('index');
                Route::get('{checklist}', [\App\Http\Controllers\Admin\HR\OnboardingController::class, 'show'])->name('show');
                Route::post('tasks/{task}/complete', [\App\Http\Controllers\Admin\HR\OnboardingController::class, 'completeTask'])->name('tasks.complete');
            });

            // Internal API (admin-only — used by admin UI)
            Route::get('api/roles', [\App\Http\Controllers\Admin\RoleController::class, 'getRolesByDepartment'])->name('api.roles');
            Route::get('api/projects/{project}/members', [\App\Http\Controllers\Admin\TaskController::class, 'getProjectMembers'])->name('api.project.members');
        }); // end role:admin

        // ── Staff: view list & profile ────────────────────────────────────────
        Route::middleware(['permission:view-staff'])->group(function () {
            Route::get('staff', [\App\Http\Controllers\Admin\StaffController::class, 'index'])->name('staff.index');
            Route::get('staff/{staff}', [\App\Http\Controllers\Admin\StaffController::class, 'show'])->name('staff.show');
        });

        // ── Attendance ────────────────────────────────────────────────────────
        Route::get('attendances', [\App\Http\Controllers\Admin\AttendanceController::class, 'index'])
            ->middleware('permission:view-attendance')->name('attendances.index');
        Route::middleware(['permission:manage-attendance'])->group(function () {
            Route::get('attendances/{attendance}/edit', [\App\Http\Controllers\Admin\AttendanceController::class, 'edit'])->name('attendances.edit');
            Route::put('attendances/{attendance}', [\App\Http\Controllers\Admin\AttendanceController::class, 'update'])->name('attendances.update');
        });

        // ── Projects ──────────────────────────────────────────────────────────
        Route::middleware(['permission:view-projects'])->group(function () {
            Route::get('projects', [\App\Http\Controllers\Admin\ProjectController::class, 'index'])->name('projects.index');
            Route::get('projects/{project}', [\App\Http\Controllers\Admin\ProjectController::class, 'show'])->name('projects.show');
            Route::get('projects/{project}/timeline', [\App\Http\Controllers\Admin\ProjectController::class, 'timeline'])->name('projects.timeline');
            Route::get('projects/{project}/documents/{index}/download', [\App\Http\Controllers\Admin\ProjectController::class, 'downloadDocument'])->name('projects.documents.download');
        });
        Route::middleware(['permission:create-project'])->group(function () {
            Route::get('projects/create', [\App\Http\Controllers\Admin\ProjectController::class, 'create'])->name('projects.create');
            Route::post('projects', [\App\Http\Controllers\Admin\ProjectController::class, 'store'])->name('projects.store');
        });
        Route::middleware(['permission:edit-project'])->group(function () {
            Route::get('projects/{project}/edit', [\App\Http\Controllers\Admin\ProjectController::class, 'edit'])->name('projects.edit');
            Route::put('projects/{project}', [\App\Http\Controllers\Admin\ProjectController::class, 'update'])->name('projects.update');
            Route::delete('projects/{project}/documents/{index}', [\App\Http\Controllers\Admin\ProjectController::class, 'deleteDocument'])->name('projects.documents.delete');
            Route::get('projects/{project}/milestones', [\App\Http\Controllers\Admin\MilestoneController::class, 'index'])->name('projects.milestones.index');
            Route::post('projects/{project}/milestones', [\App\Http\Controllers\Admin\MilestoneController::class, 'store'])->name('projects.milestones.store');
            Route::put('projects/{project}/milestones/{milestone}', [\App\Http\Controllers\Admin\MilestoneController::class, 'update'])->name('projects.milestones.update');
            Route::delete('projects/{project}/milestones/{milestone}', [\App\Http\Controllers\Admin\MilestoneController::class, 'destroy'])->name('projects.milestones.destroy');
            Route::get('projects/{project}/sprints', [\App\Http\Controllers\Admin\SprintController::class, 'index'])->name('projects.sprints.index');
            Route::post('projects/{project}/sprints', [\App\Http\Controllers\Admin\SprintController::class, 'store'])->name('projects.sprints.store');
            Route::get('projects/{project}/sprints/{sprint}', [\App\Http\Controllers\Admin\SprintController::class, 'show'])->name('projects.sprints.show');
            Route::put('projects/{project}/sprints/{sprint}', [\App\Http\Controllers\Admin\SprintController::class, 'update'])->name('projects.sprints.update');
            Route::delete('projects/{project}/sprints/{sprint}', [\App\Http\Controllers\Admin\SprintController::class, 'destroy'])->name('projects.sprints.destroy');
        });
        Route::delete('projects/{project}', [\App\Http\Controllers\Admin\ProjectController::class, 'destroy'])
            ->middleware('permission:archive-project')->name('projects.destroy');

        // ── Tasks ─────────────────────────────────────────────────────────────
        Route::middleware(['permission:view-tasks'])->group(function () {
            Route::get('tasks', [\App\Http\Controllers\Admin\TaskController::class, 'index'])->name('tasks.index');
            Route::get('tasks/{task}', [\App\Http\Controllers\Admin\TaskController::class, 'show'])->name('tasks.show');
        });
        Route::middleware(['permission:create-task'])->group(function () {
            Route::get('tasks/create', [\App\Http\Controllers\Admin\TaskController::class, 'create'])->name('tasks.create');
            Route::post('tasks', [\App\Http\Controllers\Admin\TaskController::class, 'store'])->name('tasks.store');
        });
        Route::middleware(['permission:update-task'])->group(function () {
            Route::get('tasks/{task}/edit', [\App\Http\Controllers\Admin\TaskController::class, 'edit'])->name('tasks.edit');
            Route::put('tasks/{task}', [\App\Http\Controllers\Admin\TaskController::class, 'update'])->name('tasks.update');
        });
        Route::delete('tasks/{task}', [\App\Http\Controllers\Admin\TaskController::class, 'destroy'])
            ->middleware('permission:delete-task')->name('tasks.destroy');
        Route::middleware(['permission:assign-task'])->group(function () {
            Route::post('tasks/{task}/dependencies', [\App\Http\Controllers\Admin\TaskController::class, 'addDependency'])->name('tasks.dependencies.add');
            Route::delete('tasks/{task}/dependencies/{blocker}', [\App\Http\Controllers\Admin\TaskController::class, 'removeDependency'])->name('tasks.dependencies.remove');
        });

        // ── Leave management ──────────────────────────────────────────────────
        Route::middleware(['permission:approve-leave'])->group(function () {
            Route::get('leaves', [\App\Http\Controllers\Admin\LeaveController::class, 'index'])->name('leaves.index');
            Route::get('leaves/{leave}', [\App\Http\Controllers\Admin\LeaveController::class, 'show'])->name('leaves.show');
            Route::post('leaves/{leave}/approve', [\App\Http\Controllers\Admin\LeaveController::class, 'approve'])->name('leaves.approve');
            Route::post('leaves/{leave}/hr-approve', [\App\Http\Controllers\Admin\LeaveController::class, 'hrApprove'])->name('leaves.hr-approve');
            Route::post('leaves/{leave}/reject', [\App\Http\Controllers\Admin\LeaveController::class, 'reject'])->name('leaves.reject');
            Route::get('leave-approvals', [\App\Http\Controllers\Admin\LeaveController::class, 'approvalDashboard'])->name('leaves.approvals');
            Route::get('leave-calendar', [\App\Http\Controllers\Admin\LeaveController::class, 'calendar'])->name('leaves.calendar');
        });
        Route::middleware(['permission:view-reports'])->prefix('leave-reports')->name('leaves.reports.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\Leave\LeaveReportController::class, 'index'])->name('index');
            Route::get('/compliance', [\App\Http\Controllers\Admin\Leave\LeaveReportController::class, 'compliance'])->name('compliance');
            Route::get('/trends', [\App\Http\Controllers\Admin\Leave\LeaveReportController::class, 'trends'])->name('trends');
        });

        // ── Monitoring: live view & screenshots ───────────────────────────────
        Route::middleware(['permission:view-monitoring'])->prefix('monitoring')->name('monitoring.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\Monitoring\MonitoringController::class, 'index'])->name('index');
            Route::get('employees/{user}', [\App\Http\Controllers\Admin\Monitoring\MonitoringController::class, 'show'])->name('show');
        });
        Route::middleware(['permission:view-screenshots'])->prefix('monitoring')->name('monitoring.')->group(function () {
            Route::get('employees/{user}/screenshots', [\App\Http\Controllers\Admin\Monitoring\MonitoringScreenshotController::class, 'index'])->name('screenshots.index');
            Route::get('screenshots/{screenshot}/serve', [\App\Http\Controllers\Admin\Monitoring\MonitoringScreenshotController::class, 'serve'])->name('screenshots.serve');
            Route::post('screenshots/{screenshot}/flag', [\App\Http\Controllers\Admin\Monitoring\MonitoringScreenshotController::class, 'flag'])->name('screenshots.flag');
            Route::post('screenshots/{screenshot}/accept', [\App\Http\Controllers\Admin\Monitoring\MonitoringScreenshotController::class, 'accept'])->name('screenshots.accept');
            Route::post('screenshots/{screenshot}/escalate', [\App\Http\Controllers\Admin\Monitoring\MonitoringScreenshotController::class, 'escalate'])->name('screenshots.escalate');
            Route::delete('screenshots/{screenshot}', [\App\Http\Controllers\Admin\Monitoring\MonitoringScreenshotController::class, 'destroy'])->name('screenshots.destroy');
        });

        // ── Reports ───────────────────────────────────────────────────────────
        Route::middleware(['permission:view-reports'])->group(function () {
            Route::get('reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
            Route::get('reports/attendance', [\App\Http\Controllers\Admin\ReportController::class, 'attendance'])->name('reports.attendance');
            Route::get('reports/projects', [\App\Http\Controllers\Admin\ReportController::class, 'projects'])->name('reports.projects');
            Route::get('reports/bugs', [\App\Http\Controllers\Admin\ReportController::class, 'bugs'])->name('reports.bugs');
            Route::get('reports/leaves', [\App\Http\Controllers\Admin\ReportController::class, 'leaves'])->name('reports.leaves');
        });
        Route::middleware(['permission:export-reports'])->group(function () {
            Route::get('reports/attendance/export', [\App\Http\Controllers\Admin\ReportController::class, 'exportAttendance'])->name('reports.attendance.export');
            Route::get('reports/leaves/export', [\App\Http\Controllers\Admin\ReportController::class, 'exportLeaves'])->name('reports.leaves.export');
            Route::get('reports/time/export', [\App\Http\Controllers\Admin\ReportController::class, 'exportTime'])->name('reports.time.export');
        });
        Route::middleware(['permission:view-payroll-reports'])->group(function () {
            Route::get('reports/payroll', [\App\Http\Controllers\Admin\ReportController::class, 'payroll'])->name('reports.payroll');
            Route::get('reports/payroll/export', [\App\Http\Controllers\Admin\ReportController::class, 'exportPayroll'])->name('reports.payroll.export');
        });
        Route::middleware(['permission:view-reports'])->prefix('monitoring/reports')->name('monitoring.reports.')->group(function () {
            Route::get('daily', [\App\Http\Controllers\Admin\Monitoring\MonitoringReportController::class, 'daily'])->name('daily');
            Route::get('weekly', [\App\Http\Controllers\Admin\Monitoring\MonitoringReportController::class, 'weekly'])->name('weekly');
            Route::get('department', [\App\Http\Controllers\Admin\Monitoring\MonitoringReportController::class, 'department'])->name('department');
        });

    }); // end role:admin,pm
});

Route::middleware('auth')->prefix('payroll')->name('payroll.')->group(function () {
    Route::get('slips/{payrollSlip}', [\App\Http\Controllers\Payroll\PayrollSlipController::class, 'showSlip'])->name('slips.show');
    Route::get('slips/{payrollSlip}/download', [\App\Http\Controllers\Payroll\PayrollSlipController::class, 'downloadSlip'])->name('slips.download');
});

require __DIR__.'/auth.php';
