<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BatchApprovedNetworkController;
use App\Http\Controllers\BatchExportController;
use App\Http\Controllers\BatchInternController;
use App\Http\Controllers\BatchProgramWeekController;
use App\Http\Controllers\BatchReportFormatController;
use App\Http\Controllers\BatchWorkingHourController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\InternshipBatchController;
use App\Http\Controllers\ReportGenerationResetController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('documentation', DocumentationController::class)->name('documentation');

    Route::get('attendances', [AttendanceController::class, 'index'])
        ->middleware('role:admin,hr,manager,center_director,supervisor')
        ->name('attendances.index');
    Route::post('attendances/import', [AttendanceController::class, 'import'])
        ->middleware('role:admin')
        ->name('attendances.import');

    Route::middleware('role:admin')->group(function () {
        Route::patch('batches/{batch}/close', [InternshipBatchController::class, 'close'])->name('batches.close');
        Route::get('batches/{batch}/report', [BatchExportController::class, 'generateReport'])
            ->name('batches.report');
        Route::get('batches/{batch}/interns/export', [BatchExportController::class, 'exportInterns'])
            ->name('batches.interns.export');
        Route::patch('batches/{batch}/report-format', [BatchReportFormatController::class, 'update'])->name('batches.report-format.update');
        Route::post('batches/{batch}/program-weeks', [BatchProgramWeekController::class, 'store'])
            ->name('batches.program-weeks.store');
        Route::patch('batches/{batch}/program-weeks/{programWeek}', [BatchProgramWeekController::class, 'update'])
            ->name('batches.program-weeks.update');
        Route::delete('batches/{batch}/program-weeks/{programWeek}', [BatchProgramWeekController::class, 'destroy'])
            ->name('batches.program-weeks.destroy');
        Route::patch('batches/{batch}/working-hours', [BatchWorkingHourController::class, 'update'])
            ->name('batches.working-hours.update');
        Route::patch('batches/{batch}/report-generation-reset', [ReportGenerationResetController::class, 'resetBatch'])
            ->name('batches.report-generation-reset.update');
        Route::post('batches/{batch}/approved-networks', [BatchApprovedNetworkController::class, 'store'])
            ->name('batches.approved-networks.store');
        Route::patch('batches/{batch}/approved-networks/{network}', [BatchApprovedNetworkController::class, 'update'])
            ->name('batches.approved-networks.update');
        Route::post('batches/{batch}/interns', [BatchInternController::class, 'store'])->name('batches.interns.store');
        Route::post('batches/{batch}/interns/{intern}/certificate', [BatchInternController::class, 'storeCertificate'])
            ->name('batches.interns.certificate.store');
        Route::patch('batches/{batch}/interns/{intern}/supervisors', [BatchInternController::class, 'assignSupervisors'])
            ->name('batches.interns.supervisors.update');
        Route::patch('batches/{batch}/interns/{intern}/report-generation-reset', [ReportGenerationResetController::class, 'update'])
            ->name('batches.interns.report-generation-reset.update');
        Route::resource('users', UserManagementController::class)
            ->only(['index', 'store', 'update', 'destroy']);
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
