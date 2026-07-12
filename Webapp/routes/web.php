<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BatchInternController;
use App\Http\Controllers\BatchReportFormatController;
use App\Http\Controllers\InternshipBatchController;
use App\Http\Controllers\ReportGenerationResetController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('attendances', [AttendanceController::class, 'index'])
        ->middleware('role:admin,hr,manager,center_director,supervisor')
        ->name('attendances.index');
    Route::post('attendances/import', [AttendanceController::class, 'import'])
        ->middleware('role:admin')
        ->name('attendances.import');

    Route::middleware('role:admin')->group(function () {
        Route::patch('batches/{batch}/close', [InternshipBatchController::class, 'close'])->name('batches.close');
        Route::patch('batches/{batch}/report-format', [BatchReportFormatController::class, 'update'])->name('batches.report-format.update');
        Route::patch('batches/{batch}/report-generation-reset', [ReportGenerationResetController::class, 'resetBatch'])
            ->name('batches.report-generation-reset.update');
        Route::post('batches/{batch}/interns', [BatchInternController::class, 'store'])->name('batches.interns.store');
        Route::post('batches/{batch}/interns/{intern}/certificate', [BatchInternController::class, 'storeCertificate'])
            ->name('batches.interns.certificate.store');
        Route::patch('batches/{batch}/interns/{intern}/report-generation-reset', [ReportGenerationResetController::class, 'update'])
            ->name('batches.interns.report-generation-reset.update');
        Route::resource('batches', InternshipBatchController::class)->except(['index', 'show']);
    });

    Route::middleware('role:admin,hr')->group(function () {
        Route::get('batches', [InternshipBatchController::class, 'index'])->name('batches.index');
        Route::get('batches/{batch}', [InternshipBatchController::class, 'show'])->name('batches.show');
        Route::patch('batches/{batch}/interns/{intern}/password', [BatchInternController::class, 'resetPassword'])
            ->name('batches.interns.password.reset');
    });
});

require __DIR__.'/settings.php';
