<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Attendance
    Route::post('/attendance/check-in', [\App\Http\Controllers\Staff\AttendanceController::class, 'checkIn'])->name('attendance.check-in');
    Route::post('/attendance/check-out', [\App\Http\Controllers\Staff\AttendanceController::class, 'checkOut'])->name('attendance.check-out');

    // Chat
    Route::get('chat', [App\Http\Controllers\ChatController::class, 'index'])->name('chat.index');
    Route::get('chat/messages/{userId}', [App\Http\Controllers\ChatController::class, 'getMessages'])->name('chat.messages');
    Route::post('chat/send', [App\Http\Controllers\ChatController::class, 'sendMessage'])->name('chat.send');
    Route::get('chat/unread-count', [App\Http\Controllers\ChatController::class, 'unreadCount'])->name('chat.unread-count');

    // Mail
    Route::get('mail', [App\Http\Controllers\MailController::class, 'index'])->name('mail.index');
    Route::get('mail/sent', [App\Http\Controllers\MailController::class, 'sent'])->name('mail.sent');
    Route::get('mail/create', [App\Http\Controllers\MailController::class, 'create'])->name('mail.create');
    Route::post('mail', [App\Http\Controllers\MailController::class, 'store'])->name('mail.store');
    Route::get('mail/unread-count', [App\Http\Controllers\MailController::class, 'unreadCount'])->name('mail.unread-count');
    Route::get('mail/{email}', [App\Http\Controllers\MailController::class, 'show'])->name('mail.show');

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

    // Admin panel
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::resource('staff', \App\Http\Controllers\Admin\StaffController::class);
        Route::get('settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');
        Route::resource('departments', \App\Http\Controllers\Admin\DepartmentController::class);
        Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class);
        Route::get('roles-matrix', [\App\Http\Controllers\Admin\RoleController::class, 'matrix'])->name('roles.matrix');
        Route::post('roles-matrix', [\App\Http\Controllers\Admin\RoleController::class, 'updateMatrix'])->name('roles.matrix.update');
        Route::resource('attendances', \App\Http\Controllers\Admin\AttendanceController::class)->only(['index', 'edit', 'update']);
        Route::resource('projects', \App\Http\Controllers\Admin\ProjectController::class);
        Route::get('projects/{project}/documents/{index}/download', [\App\Http\Controllers\Admin\ProjectController::class, 'downloadDocument'])->name('projects.documents.download');
        Route::delete('projects/{project}/documents/{index}', [\App\Http\Controllers\Admin\ProjectController::class, 'deleteDocument'])->name('projects.documents.delete');
        Route::resource('tasks', \App\Http\Controllers\Admin\TaskController::class);

        // Leave management (admin)
        Route::get('leaves', [\App\Http\Controllers\Admin\LeaveController::class, 'index'])->name('leaves.index');
        Route::get('leaves/{leave}', [\App\Http\Controllers\Admin\LeaveController::class, 'show'])->name('leaves.show');
        Route::post('leaves/{leave}/approve', [\App\Http\Controllers\Admin\LeaveController::class, 'approve'])->name('leaves.approve');
        Route::post('leaves/{leave}/hr-approve', [\App\Http\Controllers\Admin\LeaveController::class, 'hrApprove'])->name('leaves.hr-approve');
        Route::post('leaves/{leave}/reject', [\App\Http\Controllers\Admin\LeaveController::class, 'reject'])->name('leaves.reject');

        // Leave Types
        Route::prefix('leave-types')->name('leaves.types.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\Leave\LeaveTypeController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\Leave\LeaveTypeController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\Leave\LeaveTypeController::class, 'store'])->name('store');
            Route::get('/{type}/edit', [\App\Http\Controllers\Admin\Leave\LeaveTypeController::class, 'edit'])->name('edit');
            Route::put('/{type}', [\App\Http\Controllers\Admin\Leave\LeaveTypeController::class, 'update'])->name('update');
            Route::delete('/{type}', [\App\Http\Controllers\Admin\Leave\LeaveTypeController::class, 'destroy'])->name('destroy');
        });

        // Leave Policies
        Route::prefix('leave-policies')->name('leaves.policies.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\Leave\LeavePolicyController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\Leave\LeavePolicyController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\Leave\LeavePolicyController::class, 'store'])->name('store');
            Route::delete('/{policy}', [\App\Http\Controllers\Admin\Leave\LeavePolicyController::class, 'destroy'])->name('destroy');
        });

        // Leave Balances
        Route::prefix('leave-balances')->name('leaves.balances.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\Leave\LeaveBalanceController::class, 'index'])->name('index');
            Route::post('/adjust', [\App\Http\Controllers\Admin\Leave\LeaveBalanceController::class, 'adjust'])->name('adjust');
            Route::post('/run-accrual', [\App\Http\Controllers\Admin\Leave\LeaveBalanceController::class, 'runAccrual'])->name('run-accrual');
        });

        // Leave Reports
        Route::prefix('leave-reports')->name('leaves.reports.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\Leave\LeaveReportController::class, 'index'])->name('index');
            Route::get('/compliance', [\App\Http\Controllers\Admin\Leave\LeaveReportController::class, 'compliance'])->name('compliance');
            Route::get('/trends', [\App\Http\Controllers\Admin\Leave\LeaveReportController::class, 'trends'])->name('trends');
        });

        // Leave Approval Dashboard
        Route::get('leave-approvals', [\App\Http\Controllers\Admin\LeaveController::class, 'approvalDashboard'])->name('leaves.approvals');

        // Team Leave Calendar
        Route::get('leave-calendar', [\App\Http\Controllers\Admin\LeaveController::class, 'calendar'])->name('leaves.calendar');

        // ── Time Tracking ──────────────────────────────────────────────────────
        Route::prefix('time')->name('time.')->group(function () {
            // Time log
            Route::get('/', [\App\Http\Controllers\Admin\TimeTrackerController::class, 'index'])->name('index');
            Route::delete('{entry}', [\App\Http\Controllers\Admin\TimeTrackerController::class, 'destroy'])->name('destroy');

            // Categories CRUD
            Route::resource('categories', \App\Http\Controllers\Admin\Time\TimeCategoryController::class)
                ->except(['show'])
                ->names('categories');

            // Billable Rates
            Route::get('rates', [\App\Http\Controllers\Admin\Time\BillableRateController::class, 'index'])->name('rates.index');
            Route::get('rates/create', [\App\Http\Controllers\Admin\Time\BillableRateController::class, 'create'])->name('rates.create');
            Route::post('rates', [\App\Http\Controllers\Admin\Time\BillableRateController::class, 'store'])->name('rates.store');
            Route::delete('rates/{rate}', [\App\Http\Controllers\Admin\Time\BillableRateController::class, 'destroy'])->name('rates.destroy');

            // Reports
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\Time\TimeReportController::class, 'index'])->name('index');
                Route::get('utilization', [\App\Http\Controllers\Admin\Time\TimeReportController::class, 'utilization'])->name('utilization');
                Route::get('revenue', [\App\Http\Controllers\Admin\Time\TimeReportController::class, 'revenue'])->name('revenue');
                Route::get('export', [\App\Http\Controllers\Admin\Time\TimeReportController::class, 'export'])->name('export');
            });
        });

        // Announcements
        Route::resource('announcements', \App\Http\Controllers\Admin\AnnouncementController::class);

        // ── Employee Monitoring ────────────────────────────────────────────────
        Route::prefix('monitoring')->name('monitoring.')->group(function () {
            // Live status board
            Route::get('/', [\App\Http\Controllers\Admin\Monitoring\MonitoringController::class, 'index'])->name('index');
            // Per-employee detail + activity
            Route::get('employees/{user}', [\App\Http\Controllers\Admin\Monitoring\MonitoringController::class, 'show'])->name('show');
            // Screenshot gallery
            Route::get('employees/{user}/screenshots', [\App\Http\Controllers\Admin\Monitoring\MonitoringScreenshotController::class, 'index'])->name('screenshots.index');
            Route::post('screenshots/{screenshot}/flag', [\App\Http\Controllers\Admin\Monitoring\MonitoringScreenshotController::class, 'flag'])->name('screenshots.flag');
            Route::delete('screenshots/{screenshot}', [\App\Http\Controllers\Admin\Monitoring\MonitoringScreenshotController::class, 'destroy'])->name('screenshots.destroy');
            // Settings & token management
            Route::get('settings', [\App\Http\Controllers\Admin\Monitoring\MonitoringSettingController::class, 'index'])->name('settings.index');
            Route::post('settings', [\App\Http\Controllers\Admin\Monitoring\MonitoringSettingController::class, 'store'])->name('settings.store');
            Route::delete('settings/{setting}', [\App\Http\Controllers\Admin\Monitoring\MonitoringSettingController::class, 'destroy'])->name('settings.destroy');
            Route::post('tokens/{user}/generate', [\App\Http\Controllers\Admin\Monitoring\MonitoringSettingController::class, 'generateToken'])->name('tokens.generate');
            Route::delete('tokens/{user}/revoke', [\App\Http\Controllers\Admin\Monitoring\MonitoringSettingController::class, 'revokeToken'])->name('tokens.revoke');
        });

        // Reports
        Route::get('reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/attendance', [\App\Http\Controllers\Admin\ReportController::class, 'attendance'])->name('reports.attendance');
        Route::get('reports/projects', [\App\Http\Controllers\Admin\ReportController::class, 'projects'])->name('reports.projects');
        Route::get('reports/bugs', [\App\Http\Controllers\Admin\ReportController::class, 'bugs'])->name('reports.bugs');

        // ── Shift Management ───────────────────────────────────────────────
        Route::prefix('shifts')->name('shifts.')->group(function () {
            // Dashboard
            Route::get('/',            [\App\Http\Controllers\Admin\Shift\ShiftDashboardController::class, 'index'])->name('dashboard');

            // Shift definitions (CRUD)
            Route::get('definitions',                [\App\Http\Controllers\Admin\Shift\ShiftController::class, 'index'])->name('index');
            Route::get('definitions/create',         [\App\Http\Controllers\Admin\Shift\ShiftController::class, 'create'])->name('create');
            Route::post('definitions',               [\App\Http\Controllers\Admin\Shift\ShiftController::class, 'store'])->name('store');
            Route::get('definitions/{shift}',        [\App\Http\Controllers\Admin\Shift\ShiftController::class, 'show'])->name('show');
            Route::get('definitions/{shift}/edit',   [\App\Http\Controllers\Admin\Shift\ShiftController::class, 'edit'])->name('edit');
            Route::put('definitions/{shift}',        [\App\Http\Controllers\Admin\Shift\ShiftController::class, 'update'])->name('update');
            Route::delete('definitions/{shift}',     [\App\Http\Controllers\Admin\Shift\ShiftController::class, 'destroy'])->name('destroy');

            // Assignments
            Route::get('assignments',                     [\App\Http\Controllers\Admin\Shift\ShiftAssignmentController::class, 'index'])->name('assignments.index');
            Route::get('assignments/create',              [\App\Http\Controllers\Admin\Shift\ShiftAssignmentController::class, 'create'])->name('assignments.create');
            Route::post('assignments',                    [\App\Http\Controllers\Admin\Shift\ShiftAssignmentController::class, 'store'])->name('assignments.store');
            Route::delete('assignments/{assignment}',     [\App\Http\Controllers\Admin\Shift\ShiftAssignmentController::class, 'destroy'])->name('assignments.destroy');

            // Exceptions
            Route::get('exceptions',                      [\App\Http\Controllers\Admin\Shift\AttendanceExceptionController::class, 'index'])->name('exceptions.index');
            Route::post('exceptions/bulk-approve',        [\App\Http\Controllers\Admin\Shift\AttendanceExceptionController::class, 'bulkApprove'])->name('exceptions.bulk-approve');
            Route::post('exceptions/validate-date',       [\App\Http\Controllers\Admin\Shift\AttendanceExceptionController::class, 'validateDate'])->name('exceptions.validate-date');
            Route::post('exceptions/{exception}/approve', [\App\Http\Controllers\Admin\Shift\AttendanceExceptionController::class, 'approve'])->name('exceptions.approve');
            Route::post('exceptions/{exception}/reject',  [\App\Http\Controllers\Admin\Shift\AttendanceExceptionController::class, 'reject'])->name('exceptions.reject');

            // Change requests
            Route::get('change-requests',                           [\App\Http\Controllers\Admin\Shift\ShiftChangeRequestController::class, 'index'])->name('change-requests.index');
            Route::post('change-requests/{changeRequest}/approve',  [\App\Http\Controllers\Admin\Shift\ShiftChangeRequestController::class, 'approve'])->name('change-requests.approve');
            Route::post('change-requests/{changeRequest}/reject',   [\App\Http\Controllers\Admin\Shift\ShiftChangeRequestController::class, 'reject'])->name('change-requests.reject');

            // Holidays
            Route::get('holidays',            [\App\Http\Controllers\Admin\Shift\ShiftHolidayController::class, 'index'])->name('holidays.index');
            Route::post('holidays',           [\App\Http\Controllers\Admin\Shift\ShiftHolidayController::class, 'store'])->name('holidays.store');
            Route::delete('holidays/{holiday}', [\App\Http\Controllers\Admin\Shift\ShiftHolidayController::class, 'destroy'])->name('holidays.destroy');
        });

        // Internal API
        Route::get('api/roles', [\App\Http\Controllers\Admin\RoleController::class, 'getRolesByDepartment'])->name('api.roles');
        Route::get('api/projects/{project}/members', [\App\Http\Controllers\Admin\TaskController::class, 'getProjectMembers'])->name('api.project.members');

        // ── HR Management ──────────────────────────────────────────────────
        Route::prefix('hr')->name('hr.')->group(function () {
            // Dashboard
            Route::get('/', [\App\Http\Controllers\Admin\HR\HRDashboardController::class, 'index'])->name('dashboard');

            // Employee profiles
            Route::get('employees', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'index'])->name('employees.index');
            Route::get('employees/{employee}', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'show'])->name('employees.show');
            Route::get('employees/{employee}/edit', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'editProfile'])->name('employees.edit');
            Route::put('employees/{employee}', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'updateProfile'])->name('employees.update');

            // Satellite: education
            Route::post('employees/{employee}/education', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'storeEducation'])->name('employees.education.store');
            Route::delete('employees/{employee}/education/{education}', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'destroyEducation'])->name('employees.education.destroy');

            // Satellite: experience
            Route::post('employees/{employee}/experience', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'storeExperience'])->name('employees.experience.store');
            Route::delete('employees/{employee}/experience/{experience}', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'destroyExperience'])->name('employees.experience.destroy');

            // Satellite: skills
            Route::post('employees/{employee}/skills', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'storeSkill'])->name('employees.skills.store');
            Route::delete('employees/{employee}/skills/{skill}', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'destroySkill'])->name('employees.skills.destroy');

            // Satellite: documents
            Route::post('employees/{employee}/documents', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'storeDocument'])->name('employees.documents.store');
            Route::delete('employees/{employee}/documents/{document}', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'destroyDocument'])->name('employees.documents.destroy');

            // Satellite: emergency contacts
            Route::post('employees/{employee}/contacts', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'storeEmergencyContact'])->name('employees.contacts.store');
            Route::delete('employees/{employee}/contacts/{contact}', [\App\Http\Controllers\Admin\HR\EmployeeProfileController::class, 'destroyEmergencyContact'])->name('employees.contacts.destroy');

            // Promotions
            Route::resource('promotions', \App\Http\Controllers\Admin\HR\PromotionController::class)->except(['edit', 'update']);
            Route::post('promotions/{promotion}/approve', [\App\Http\Controllers\Admin\HR\PromotionController::class, 'approve'])->name('promotions.approve');

            // Resignations
            Route::get('resignations', [\App\Http\Controllers\Admin\HR\ResignationController::class, 'index'])->name('resignations.index');
            Route::post('resignations', [\App\Http\Controllers\Admin\HR\ResignationController::class, 'store'])->name('resignations.store');
            Route::get('resignations/{resignation}', [\App\Http\Controllers\Admin\HR\ResignationController::class, 'show'])->name('resignations.show');
            Route::post('resignations/{resignation}/manager-decision', [\App\Http\Controllers\Admin\HR\ResignationController::class, 'managerDecision'])->name('resignations.manager-decision');
            Route::post('resignations/{resignation}/hr-approve', [\App\Http\Controllers\Admin\HR\ResignationController::class, 'hrApprove'])->name('resignations.hr-approve');
            Route::post('resignations/{resignation}/withdraw', [\App\Http\Controllers\Admin\HR\ResignationController::class, 'withdraw'])->name('resignations.withdraw');

            // Terminations
            Route::get('terminations', [\App\Http\Controllers\Admin\HR\TerminationController::class, 'index'])->name('terminations.index');
            Route::get('terminations/create', [\App\Http\Controllers\Admin\HR\TerminationController::class, 'create'])->name('terminations.create');
            Route::post('terminations', [\App\Http\Controllers\Admin\HR\TerminationController::class, 'store'])->name('terminations.store');
            Route::get('terminations/{termination}', [\App\Http\Controllers\Admin\HR\TerminationController::class, 'show'])->name('terminations.show');
            Route::post('terminations/{termination}/approve', [\App\Http\Controllers\Admin\HR\TerminationController::class, 'approve'])->name('terminations.approve');
            Route::post('terminations/{termination}/checklist/{item}/complete', [\App\Http\Controllers\Admin\HR\TerminationController::class, 'completeChecklistItem'])->name('terminations.checklist.complete');
            Route::post('terminations/{termination}/settlement/calculate', [\App\Http\Controllers\Admin\HR\TerminationController::class, 'calculateSettlement'])->name('terminations.settlement.calculate');
            Route::post('terminations/{termination}/settlement/approve', [\App\Http\Controllers\Admin\HR\TerminationController::class, 'approveSettlement'])->name('terminations.settlement.approve');
            Route::post('terminations/{termination}/finalize', [\App\Http\Controllers\Admin\HR\TerminationController::class, 'finalize'])->name('terminations.finalize');

            // Transfers
            Route::get('transfers',                                     [\App\Http\Controllers\Admin\HR\TransferController::class, 'index'])->name('transfers.index');
            Route::get('transfers/create',                              [\App\Http\Controllers\Admin\HR\TransferController::class, 'create'])->name('transfers.create');
            Route::post('transfers',                                    [\App\Http\Controllers\Admin\HR\TransferController::class, 'store'])->name('transfers.store');
            Route::get('transfers/{transfer}',                          [\App\Http\Controllers\Admin\HR\TransferController::class, 'show'])->name('transfers.show');
            Route::post('transfers/{transfer}/approve',                 [\App\Http\Controllers\Admin\HR\TransferController::class, 'approve'])->name('transfers.approve');
            Route::post('transfers/{transfer}/reject',                  [\App\Http\Controllers\Admin\HR\TransferController::class, 'reject'])->name('transfers.reject');

            // Warnings (disciplinary)
            Route::get('warnings',                                      [\App\Http\Controllers\Admin\HR\WarningController::class, 'index'])->name('warnings.index');
            Route::get('warnings/create',                               [\App\Http\Controllers\Admin\HR\WarningController::class, 'create'])->name('warnings.create');
            Route::post('warnings',                                     [\App\Http\Controllers\Admin\HR\WarningController::class, 'store'])->name('warnings.store');
            Route::get('warnings/{warning}',                            [\App\Http\Controllers\Admin\HR\WarningController::class, 'show'])->name('warnings.show');
            Route::delete('warnings/{warning}',                         [\App\Http\Controllers\Admin\HR\WarningController::class, 'destroy'])->name('warnings.destroy');
            Route::post('warnings/{warning}/resolve',                   [\App\Http\Controllers\Admin\HR\WarningController::class, 'resolve'])->name('warnings.resolve');
            Route::post('warnings/{warning}/acknowledge',               [\App\Http\Controllers\Admin\HR\WarningController::class, 'acknowledge'])->name('warnings.acknowledge');
        });

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

            // Tax Regimes & Brackets
            Route::get('tax-regimes',                        [\App\Http\Controllers\Admin\Payroll\TaxRegimeController::class, 'index'])->name('tax-regimes.index');
            Route::get('tax-regimes/create',                 [\App\Http\Controllers\Admin\Payroll\TaxRegimeController::class, 'create'])->name('tax-regimes.create');
            Route::post('tax-regimes',                       [\App\Http\Controllers\Admin\Payroll\TaxRegimeController::class, 'store'])->name('tax-regimes.store');
            Route::get('tax-regimes/{taxRegime}',            [\App\Http\Controllers\Admin\Payroll\TaxRegimeController::class, 'show'])->name('tax-regimes.show');
            Route::get('tax-regimes/{taxRegime}/edit',       [\App\Http\Controllers\Admin\Payroll\TaxRegimeController::class, 'edit'])->name('tax-regimes.edit');
            Route::put('tax-regimes/{taxRegime}',            [\App\Http\Controllers\Admin\Payroll\TaxRegimeController::class, 'update'])->name('tax-regimes.update');
            Route::delete('tax-regimes/{taxRegime}',         [\App\Http\Controllers\Admin\Payroll\TaxRegimeController::class, 'destroy'])->name('tax-regimes.destroy');

            // Payroll Adjustments
            Route::get('adjustments',                        [\App\Http\Controllers\Admin\Payroll\PayrollAdjustmentController::class, 'index'])->name('adjustments.index');
            Route::get('adjustments/create',                 [\App\Http\Controllers\Admin\Payroll\PayrollAdjustmentController::class, 'create'])->name('adjustments.create');
            Route::post('adjustments',                       [\App\Http\Controllers\Admin\Payroll\PayrollAdjustmentController::class, 'store'])->name('adjustments.store');
            Route::get('adjustments/{adjustment}',           [\App\Http\Controllers\Admin\Payroll\PayrollAdjustmentController::class, 'show'])->name('adjustments.show');
            Route::post('adjustments/{adjustment}/approve',  [\App\Http\Controllers\Admin\Payroll\PayrollAdjustmentController::class, 'approve'])->name('adjustments.approve');
            Route::post('adjustments/{adjustment}/reject',   [\App\Http\Controllers\Admin\Payroll\PayrollAdjustmentController::class, 'reject'])->name('adjustments.reject');
            Route::post('adjustments/{adjustment}/cancel',   [\App\Http\Controllers\Admin\Payroll\PayrollAdjustmentController::class, 'cancel'])->name('adjustments.cancel');

            Route::post('settlements/initiate', [\App\Http\Controllers\Admin\Payroll\SettlementController::class, 'initiate'])->name('settlements.initiate');
            Route::post('settlements/{termination}/finalize', [\App\Http\Controllers\Admin\Payroll\SettlementController::class, 'finalize'])->name('settlements.finalize');
        });

        // Time entry approvals
        Route::prefix('time-entries')->name('time-entries.')->group(function () {
            Route::get('approvals',                                [\App\Http\Controllers\Admin\Time\TimeEntryApprovalController::class, 'index'])->name('approvals.index');
            Route::post('approvals/{entry}/approve',               [\App\Http\Controllers\Admin\Time\TimeEntryApprovalController::class, 'approve'])->name('approvals.approve');
            Route::post('approvals/{entry}/reject',                [\App\Http\Controllers\Admin\Time\TimeEntryApprovalController::class, 'reject'])->name('approvals.reject');
        });
    });
});

Route::middleware('auth')->prefix('payroll')->name('payroll.')->group(function () {
    Route::get('slips/{payrollSlip}', [\App\Http\Controllers\Payroll\PayrollSlipController::class, 'showSlip'])->name('slips.show');
    Route::get('slips/{payrollSlip}/download', [\App\Http\Controllers\Payroll\PayrollSlipController::class, 'downloadSlip'])->name('slips.download');
});

require __DIR__.'/auth.php';
