<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Executive\DashboardController;
use App\Http\Controllers\Executive\ReportController;

// Rector / Executive Dashboard
Route::prefix('executive')->name('executive.')->middleware(['auth', 'role:rector,super_admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

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
    Route::get('/reports', [\App\Http\Controllers\Auditor\ReportController::class, 'index'])->name('reports');
    Route::get('/audit-logs', [\App\Http\Controllers\Auditor\AuditLogController::class, 'index'])->name('audit-logs');
    Route::get('/deleted-records', [\App\Http\Controllers\Auditor\DeletedRecordController::class, 'index'])->name('deleted');
});