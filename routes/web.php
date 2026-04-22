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

        // Internal API
        Route::get('api/roles', [\App\Http\Controllers\Admin\RoleController::class, 'getRolesByDepartment'])->name('api.roles');
        Route::get('api/projects/{project}/members', [\App\Http\Controllers\Admin\TaskController::class, 'getProjectMembers'])->name('api.project.members');
    });
});

require __DIR__.'/auth.php';
