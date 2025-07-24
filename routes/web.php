<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Attendance\ClockInOut;
use App\Livewire\Attendance\History;
use App\Livewire\Admin\PendingApprovals;
use App\Livewire\Admin\Reports;
use App\Livewire\Admin\UserManagement;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // Attendance Routes
    Route::get('attendance/clock', ClockInOut::class)->name('attendance.clock');
    Route::get('attendance/history', History::class)->name('attendance.history');

    // Admin Routes
    Route::middleware(['role:admin'])->group(function () {
        Route::get('admin/users', UserManagement::class)->name('admin.users');
        Route::get('admin/approvals', PendingApprovals::class)->name('admin.approvals');
        Route::get('admin/reports', Reports::class)->name('admin.reports');
    });

    // Settings Routes
    Route::redirect('settings', 'settings/profile');
    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

require __DIR__.'/auth.php';
