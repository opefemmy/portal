<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Executive\DashboardController;
use App\Http\Controllers\Executive\DashboardConfigController as ExecutiveDashboardConfigController;
use App\Http\Controllers\Executive\ReportController;
use App\Http\Controllers\Auditor\DashboardConfigController as AuditorDashboardConfigController;

// Rector / Executive Dashboard
Route::prefix('executive')->name('executive.')->middleware(['auth', 'role:rector,super_admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Per-user dashboard widget configurator
    Route::get('/dashboard-config/{user}', [ExecutiveDashboardConfigController::class, 'edit'])
        ->name('dashboard-config.edit');
    Route::put('/dashboard-config/{user}', [ExecutiveDashboardConfigController::class, 'update'])
        ->name('dashboard-config.update');

    // Executive Reports
    Route::get('/reports/students', [ReportController::class, 'studentReport'])->name('reports.students');
    Route::get('/reports/financial', [ReportController::class, 'financialReport'])->name('reports.financial');
    Route::get('/reports/hospital', [ReportController::class, 'hospitalReport'])->name('reports.hospital');
    Route::get('/reports/staff', [ReportController::class, 'staffReport'])->name('reports.staff');
});

// Auditor Dashboard (Read-only). The three audit roles seeded by
// ERPRolesSeeder (auditor, internal_auditor, external_auditor) all
// share the same screens — permissions on specific actions (e.g.
// "external auditor gets full audit access") are enforced inside the
// controllers where they matter.
Route::prefix('auditor')->name('auditor.')->middleware(['auth', 'role:auditor,internal_auditor,external_auditor,super_admin,admin'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Auditor\DashboardController::class, 'index'])->name('dashboard');

    // Per-user dashboard widget configurator
    Route::get('/dashboard-config/{user}', [AuditorDashboardConfigController::class, 'edit'])
        ->name('dashboard-config.edit');
    Route::put('/dashboard-config/{user}', [AuditorDashboardConfigController::class, 'update'])
        ->name('dashboard-config.update');
    Route::get('/reports', [\App\Http\Controllers\Auditor\ReportController::class, 'index'])->name('reports');
    Route::get('/audit-logs', [\App\Http\Controllers\Auditor\AuditLogController::class, 'index'])->name('audit-logs');
    Route::get('/deleted-records', [\App\Http\Controllers\Auditor\DeletedRecordController::class, 'index'])->name('deleted');
});