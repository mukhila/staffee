<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/attendance/check-in', [\App\Http\Controllers\Staff\AttendanceController::class, 'checkIn'])->name('attendance.check-in');
    Route::post('/attendance/check-out', [\App\Http\Controllers\Staff\AttendanceController::class, 'checkOut'])->name('attendance.check-out');

    Route::get('chat', [App\Http\Controllers\ChatController::class, 'index'])->name('chat.index');  
    Route::get('chat/messages/{userId}', [App\Http\Controllers\ChatController::class, 'getMessages'])->name('chat.messages');
    Route::post('chat/send', [App\Http\Controllers\ChatController::class, 'sendMessage'])->name('chat.send');
    
    Route::get('mail', [App\Http\Controllers\MailController::class, 'index'])->name('mail.index');
    Route::get('mail/sent', [App\Http\Controllers\MailController::class, 'sent'])->name('mail.sent');
    Route::get('mail/create', [App\Http\Controllers\MailController::class, 'create'])->name('mail.create');
    Route::post('mail', [App\Http\Controllers\MailController::class, 'store'])->name('mail.store');
    Route::get('mail/{email}', [App\Http\Controllers\MailController::class, 'show'])->name('mail.show');

    Route::get('/my-tasks', [\App\Http\Controllers\Staff\TaskController::class, 'index'])->name('staff.tasks.index');
    Route::put('/my-tasks/{task}', [\App\Http\Controllers\Staff\TaskController::class, 'update'])->name('staff.tasks.update');

    Route::resource('test-cases', \App\Http\Controllers\Staff\TestCaseController::class, ['as' => 'staff']);
    Route::resource('bugs', \App\Http\Controllers\Staff\BugController::class, ['as' => 'staff']);
    Route::post('time-tracker/start', [App\Http\Controllers\Staff\TimeTrackerController::class, 'start'])->name('time-tracker.start');
    Route::post('time-tracker/stop', [App\Http\Controllers\Staff\TimeTrackerController::class, 'stop'])->name('time-tracker.stop');

    Route::resource('daily-status-reports', \App\Http\Controllers\Staff\DailyStatusReportController::class, ['as' => 'staff']);

    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::resource('staff', \App\Http\Controllers\Admin\StaffController::class);
        Route::get('settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');
        Route::resource('departments', \App\Http\Controllers\Admin\DepartmentController::class);
        Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class);
        Route::resource('attendances', \App\Http\Controllers\Admin\AttendanceController::class)->only(['index', 'edit', 'update']);
        Route::resource('projects', \App\Http\Controllers\Admin\ProjectController::class);
        Route::resource('tasks', \App\Http\Controllers\Admin\TaskController::class);
        Route::get('kanban', [\App\Http\Controllers\Staff\KanbanController::class, 'index'])->name('kanban.index');
        Route::post('kanban/update-status/{id}', [\App\Http\Controllers\Staff\KanbanController::class, 'updateStatus'])->name('kanban.update-status');
        Route::get('api/roles', [\App\Http\Controllers\Admin\RoleController::class, 'getRolesByDepartment'])->name('api.roles');
        Route::get('api/projects/{project}/members', [\App\Http\Controllers\Admin\TaskController::class, 'getProjectMembers'])->name('api.project.members');
    });
});

require __DIR__.'/auth.php';
