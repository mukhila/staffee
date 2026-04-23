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
    Route::resource('daily-status-reports', \App\Http\Controllers\Staff\DailyStatusReportController::class, ['as' => 'staff']);

    // Leave requests (staff)
    Route::resource('leaves', \App\Http\Controllers\Staff\LeaveController::class, ['as' => 'staff'])
        ->only(['index', 'create', 'store', 'destroy']);

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
        Route::get('kanban', [\App\Http\Controllers\Staff\KanbanController::class, 'index'])->name('kanban.index');
        Route::post('kanban/update-status/{id}', [\App\Http\Controllers\Staff\KanbanController::class, 'updateStatus'])->name('kanban.update-status');

        // Leave management (admin)
        Route::get('leaves', [\App\Http\Controllers\Admin\LeaveController::class, 'index'])->name('leaves.index');
        Route::post('leaves/{leave}/approve', [\App\Http\Controllers\Admin\LeaveController::class, 'approve'])->name('leaves.approve');
        Route::post('leaves/{leave}/reject', [\App\Http\Controllers\Admin\LeaveController::class, 'reject'])->name('leaves.reject');

        // Announcements
        Route::resource('announcements', \App\Http\Controllers\Admin\AnnouncementController::class);

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
        });
    });
});

require __DIR__.'/auth.php';
