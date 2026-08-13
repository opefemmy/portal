<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Finance\DashboardController;
use App\Http\Controllers\Finance\DashboardConfigController as FinanceDashboardConfigController;
use App\Http\Controllers\Finance\InvoiceController;
use App\Http\Controllers\Finance\ReceiptController;
use App\Http\Controllers\Finance\TransactionController;
use App\Http\Controllers\Finance\BudgetController;
use App\Http\Controllers\Finance\PayrollController;
use App\Http\Controllers\Finance\VendorController;

// Finance Module - Protected by roles. Cashier, the bursary staff
// (bursary_officer, fees_officer, payment_officer), the hospital
// accountant, and the ICT admin all need to be able to open finance
// screens for read-only reconciliation work.
//
// Slice 8f: every route below also carries a `permission:slug`
// middleware alongside the role chain. The slug is copied verbatim
// from the controller method's `$this->requirePermission('slug')` —
// the route middleware is defence-in-depth, not the only gate. The
// existing `auth + role:` chain is preserved.
Route::prefix('finance')->name('finance.')->middleware(['auth', 'role:super_admin,admin,finance,finance_officer,accountant,account_officer,auditor,cashier,hospital_accountant,bursary_officer,fees_officer,payment_officer,ict_admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:finance.dashboard.view')
        ->name('dashboard');

    // Per-user dashboard widget configurator
    Route::get('/dashboard-config/{user}', [FinanceDashboardConfigController::class, 'edit'])
        ->middleware('permission:finance.dashboard.configure')
        ->name('dashboard-config.edit');
    Route::put('/dashboard-config/{user}', [FinanceDashboardConfigController::class, 'update'])
        ->middleware('permission:finance.dashboard.configure')
        ->name('dashboard-config.update');

    // Invoices — declared explicitly so each verb can carry its own slug
    Route::get('/invoices', [InvoiceController::class, 'index'])
        ->middleware('permission:finance.invoices.view')
        ->name('invoices.index');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])
        ->middleware('permission:finance.invoices.create')
        ->name('invoices.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])
        ->middleware('permission:finance.invoices.create')
        ->name('invoices.store');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])
        ->middleware('permission:finance.invoices.view')
        ->name('invoices.show');
    Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])
        ->middleware('permission:finance.invoices.edit')
        ->name('invoices.edit');
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])
        ->middleware('permission:finance.invoices.edit')
        ->name('invoices.update');
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])
        ->middleware('permission:finance.invoices.delete')
        ->name('invoices.destroy');

    // Receipts — same pattern
    Route::get('/receipts', [ReceiptController::class, 'index'])
        ->middleware('permission:finance.receipts.view')
        ->name('receipts.index');
    Route::get('/receipts/create', [ReceiptController::class, 'create'])
        ->middleware('permission:finance.receipts.create')
        ->name('receipts.create');
    Route::post('/receipts', [ReceiptController::class, 'store'])
        ->middleware('permission:finance.receipts.create')
        ->name('receipts.store');
    Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])
        ->middleware('permission:finance.receipts.view')
        ->name('receipts.show');
    Route::get('/receipts/{receipt}/edit', [ReceiptController::class, 'edit'])
        ->middleware('permission:finance.receipts.edit')
        ->name('receipts.edit');
    Route::put('/receipts/{receipt}', [ReceiptController::class, 'update'])
        ->middleware('permission:finance.receipts.edit')
        ->name('receipts.update');
    Route::delete('/receipts/{receipt}', [ReceiptController::class, 'destroy'])
        ->middleware('permission:finance.receipts.edit')
        ->name('receipts.destroy');

    // Transactions / General Ledger
    Route::get('/transactions', [TransactionController::class, 'index'])
        ->middleware('permission:finance.transactions.view')
        ->name('transactions.index');
    Route::get('/transactions/create', [TransactionController::class, 'create'])
        ->middleware('permission:finance.transactions.create')
        ->name('transactions.create');
    Route::post('/transactions', [TransactionController::class, 'store'])
        ->middleware('permission:finance.transactions.create')
        ->name('transactions.store');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])
        ->middleware('permission:finance.transactions.view')
        ->name('transactions.show');
    Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])
        ->middleware('permission:finance.transactions.edit')
        ->name('transactions.edit');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])
        ->middleware('permission:finance.transactions.edit')
        ->name('transactions.update');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])
        ->middleware('permission:finance.transactions.edit')
        ->name('transactions.destroy');

    // Budgets
    Route::get('/budgets', [BudgetController::class, 'index'])
        ->middleware('permission:finance.budgets.view')
        ->name('budgets.index');
    Route::get('/budgets/create', [BudgetController::class, 'create'])
        ->middleware('permission:finance.budgets.create')
        ->name('budgets.create');
    Route::post('/budgets', [BudgetController::class, 'store'])
        ->middleware('permission:finance.budgets.create')
        ->name('budgets.store');
    Route::get('/budgets/{budget}', [BudgetController::class, 'show'])
        ->middleware('permission:finance.budgets.view')
        ->name('budgets.show');
    Route::get('/budgets/{budget}/edit', [BudgetController::class, 'edit'])
        ->middleware('permission:finance.budgets.edit')
        ->name('budgets.edit');
    Route::put('/budgets/{budget}', [BudgetController::class, 'update'])
        ->middleware('permission:finance.budgets.edit')
        ->name('budgets.update');
    Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy'])
        ->middleware('permission:finance.budgets.edit')
        ->name('budgets.destroy');

    // Payroll
    Route::get('/payroll', [PayrollController::class, 'index'])
        ->middleware('permission:finance.payroll.view')
        ->name('payroll.index');
    Route::get('/payroll/create', [PayrollController::class, 'create'])
        ->middleware('permission:finance.payroll.create')
        ->name('payroll.create');
    Route::post('/payroll', [PayrollController::class, 'store'])
        ->middleware('permission:finance.payroll.create')
        ->name('payroll.store');
    Route::get('/payroll/{payroll}', [PayrollController::class, 'show'])
        ->middleware('permission:finance.payroll.view')
        ->name('payroll.show');
    Route::get('/payroll/{payroll}/edit', [PayrollController::class, 'edit'])
        ->middleware('permission:finance.payroll.edit')
        ->name('payroll.edit');
    Route::put('/payroll/{payroll}', [PayrollController::class, 'update'])
        ->middleware('permission:finance.payroll.edit')
        ->name('payroll.update');
    Route::delete('/payroll/{payroll}', [PayrollController::class, 'destroy'])
        ->middleware('permission:finance.payroll.edit')
        ->name('payroll.destroy');

    // Vendors
    Route::get('/vendors', [VendorController::class, 'index'])
        ->middleware('permission:finance.vendors.view')
        ->name('vendors.index');
    Route::get('/vendors/create', [VendorController::class, 'create'])
        ->middleware('permission:finance.vendors.create')
        ->name('vendors.create');
    Route::post('/vendors', [VendorController::class, 'store'])
        ->middleware('permission:finance.vendors.create')
        ->name('vendors.store');
    Route::get('/vendors/{vendor}', [VendorController::class, 'show'])
        ->middleware('permission:finance.vendors.view')
        ->name('vendors.show');
    Route::get('/vendors/{vendor}/edit', [VendorController::class, 'edit'])
        ->middleware('permission:finance.vendors.edit')
        ->name('vendors.edit');
    Route::put('/vendors/{vendor}', [VendorController::class, 'update'])
        ->middleware('permission:finance.vendors.edit')
        ->name('vendors.update');
    Route::delete('/vendors/{vendor}', [VendorController::class, 'destroy'])
        ->middleware('permission:finance.vendors.edit')
        ->name('vendors.destroy');

    // Reports
    Route::get('/reports', [DashboardController::class, 'reports'])
        ->middleware('permission:finance.dashboard.view')
        ->name('reports');
    Route::get('/reports/daily', [DashboardController::class, 'dailyReport'])
        ->middleware('permission:finance.receipts.view')
        ->name('reports.daily');
    Route::get('/reports/monthly', [DashboardController::class, 'monthlyReport'])
        ->middleware('permission:finance.receipts.view')
        ->name('reports.monthly');
    Route::get('/reports/income-expenditure', [DashboardController::class, 'incomeExpenditure'])
        ->middleware('permission:finance.transactions.view')
        ->name('reports.ie');
});
