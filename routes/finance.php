<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Finance\DashboardController;
use App\Http\Controllers\Finance\InvoiceController;
use App\Http\Controllers\Finance\ReceiptController;
use App\Http\Controllers\Finance\TransactionController;
use App\Http\Controllers\Finance\BudgetController;
use App\Http\Controllers\Finance\PayrollController;
use App\Http\Controllers\Finance\VendorController;

// Finance Module - Protected by roles
Route::prefix('finance')->name('finance.')->middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Invoices
    Route::resource('invoices', InvoiceController::class);

    // Receipts
    Route::resource('receipts', ReceiptController::class);

    // Transactions / General Ledger
    Route::resource('transactions', TransactionController::class);

    // Budgets
    Route::resource('budgets', BudgetController::class);

    // Payroll
    Route::resource('payroll', PayrollController::class);

    // Vendors
    Route::resource('vendors', VendorController::class);

    // Reports
    Route::get('/reports', [DashboardController::class, 'reports'])->name('reports');
    Route::get('/reports/daily', [DashboardController::class, 'dailyReport'])->name('reports.daily');
    Route::get('/reports/monthly', [DashboardController::class, 'monthlyReport'])->name('reports.monthly');
    Route::get('/reports/income-expenditure', [DashboardController::class, 'incomeExpenditure'])->name('reports.ie');
});