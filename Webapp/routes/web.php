<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\InternshipBatchController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('attendances', [AttendanceController::class, 'index'])
        ->middleware('role:admin,hr,manager,center_director,supervisor')
        ->name('attendances.index');

    Route::middleware('role:admin')->group(function () {
        Route::patch('batches/{batch}/close', [InternshipBatchController::class, 'close'])->name('batches.close');
        Route::resource('batches', InternshipBatchController::class);
    });
});

require __DIR__.'/settings.php';
