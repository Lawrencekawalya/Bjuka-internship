<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InternProgramController;
use App\Http\Controllers\Api\InternReportController;
use App\Http\Controllers\Api\InternWorkingHoursController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/password/change', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/attendance/today', [AttendanceController::class, 'today']);
    Route::get('/attendance/history', [AttendanceController::class, 'history']);
    Route::get('/intern/program', [InternProgramController::class, 'show']);
    Route::get('/intern/working-hours', [InternWorkingHoursController::class, 'show']);
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);
    Route::get('/intern/report/status', [InternReportController::class, 'status']);
    Route::post('/intern/report/generate', [InternReportController::class, 'generate']);
    Route::post('/intern/report/request-reset', [InternReportController::class, 'requestReset']);
});
