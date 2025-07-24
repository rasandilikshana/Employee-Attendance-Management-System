<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\ReportController;

// Health check endpoint for Docker
Route::get('health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected'
    ]);
});

// Public authentication routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Protected authentication routes
Route::middleware('auth:api')->prefix('auth')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});

// Attendance Management Routes
Route::middleware('auth:api')->prefix('attendance')->group(function () {
    // Employee attendance actions
    Route::post('clock-in', [AttendanceController::class, 'clockIn']);
    Route::post('clock-out', [AttendanceController::class, 'clockOut']);
    Route::get('my-attendance', [AttendanceController::class, 'getMyAttendance']);
    Route::get('today-status', [AttendanceController::class, 'getTodayStatus']);
    Route::put('{id}', [AttendanceController::class, 'updateAttendance']);
});

// User Management Routes (Admin only)
Route::middleware(['auth:api'])->prefix('users')->group(function () {
    Route::get('/', [UserManagementController::class, 'index']);
    Route::post('/', [UserManagementController::class, 'store']);

    Route::get('roles', [UserManagementController::class, 'getRoles']);
    Route::get('pending-approval-list', [UserManagementController::class, 'getPendingApprovals']);
    Route::post('attendance-approval', [UserManagementController::class, 'approveAttendance']);

    Route::get('{id}', [UserManagementController::class, 'show']);
    Route::put('{id}', [UserManagementController::class, 'update']);
    Route::delete('{id}', [UserManagementController::class, 'destroy']);
    Route::get('{id}/attendance', [UserManagementController::class, 'getUserAttendance']);
});

// Reports Routes
Route::middleware('auth:api')->prefix('reports')->group(function () {
    Route::get('attendance-summary', [ReportController::class, 'attendanceSummary']);
    Route::get('monthly-attendance', [ReportController::class, 'monthlyAttendance']);
    Route::get('export-attendance', [ReportController::class, 'exportAttendance']);

    // Admin-only reports
    Route::middleware('role:admin,api')->group(function () {
        Route::get('daily-attendance', [ReportController::class, 'dailyAttendance']);
        Route::get('attendance-trends', [ReportController::class, 'attendanceTrends']);
    });
});

// Legacy route for testing
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');
