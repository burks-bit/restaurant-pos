<?php

use App\Http\Controllers\POSController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HeadPricingRuleController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DTRController;
use App\Http\Controllers\PettyCashController;
use App\Http\Controllers\ExpenseController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

// Public welcome page
Route::get('/', function () {
    if (Auth::check()) {
        // If the user is logged in, show the welcome page (or dashboard)
        return Inertia::render('Welcome', [
            'canLogin' => false, // logged-in users don't need login
            'canRegister' => false,
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    } else {
        // If the user is not logged in, redirect to login
        return redirect()->route('login');
    }
});

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// Authenticated user routes
Route::middleware(['auth', 'verified'])->group(function () {

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Cashier routes
    Route::prefix('cashier')->name('cashier.')->group(function () {
        Route::get('/pos/table-sessions', [TableController::class, 'getOpenSessions'])->name('table_sessions');

        Route::get('/pos', [POSController::class, 'index'])->name('pos');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders');
        Route::get('/orders/fetch-filtered-orders', [OrderController::class, 'getFilteredOrders'])->name('orders.fetch-filtered-orders');
        Route::post('/orders/store', [OrderController::class, 'store'])->name('orders.store');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{orderItem}/cancel_item', [OrderController::class, 'cancel_item'])->name('orders.cancel_item');
        Route::get('/orders/{order}/pdf', [OrderController::class, 'pdfReceipt'])->name('orders.pdf');

        Route::get('/pos/verify-voucher/{voucher_code}', [POSController::class, 'verifyVoucher'])->name('pos.verify-voucher');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        Route::get('/reports/sales', [ReportController::class, 'index'])->name('reports.sales');
        Route::get('/reports/sales/pdf', [ReportController::class, 'exportPdf'])->name('reports.sales.pdf');
        Route::get('/reports/sales/excel', [ReportController::class, 'exportExcel'])->name('reports.sales.excel');

        Route::post('/verify-manager-password', [POSController::class, 'verifyManagerPassword'])->name('verify-manager-password');
        // Petty Cash index
        Route::get('/petty-cashes', [PettyCashController::class, 'index'])->name('petty-cashes');
        Route::post('/petty-cashes', [PettyCashController::class, 'store'])->name('petty-cashes.store');
        Route::post('/petty-cashes/{pettyCash}/details', [PettyCashController::class, 'storeDetail'])->name('petty-cashes.details.store');
        
        // Master Reports (Sales)
        Route::get('/reports/sales', [ReportController::class, 'salesReportIndex'])->name('sales.report.index');
        Route::get('/reports/fetch-sales-report', [ReportController::class, 'fetchSalesReport'])->name('sales.fetch-sales-report');
        Route::get('/reports/print-sales-report-pdf', [ReportController::class, 'printSalesReport'])->name('sales.print-sales-report-pdf');
        
        Route::get('myrecords', [EmployeeController::class, 'getMyRecords'])->name('myrecords');
        Route::post('/clock-in', [DTRController::class, 'clockIn'])->name('clockin');
        Route::post('/clock-out', [DTRController::class, 'clockOut'])->name('clockout');
    });

    // Manager routes
    Route::prefix('manager')->name('manager.')->group(function () {
        
        Route::get('/pos', [POSController::class, 'index'])->name('pos');
        Route::get('/orders', [OrderController::class, 'index'])->name('orders');
        Route::get('/orders/fetch-filtered-orders', [OrderController::class, 'getFilteredOrders'])->name('orders.fetch-filtered-orders');
        Route::post('/orders/store', [OrderController::class, 'store'])->name('orders.store');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        
        Route::get('/pos/verify-voucher/{voucher_code}', [POSController::class, 'verifyVoucher'])->name('pos.verify-voucher');

        Route::get('/menus', [MenuController::class, 'index'])->name('menus');
        Route::put('/menus/edit/{id}', [MenuController::class, 'update'])->name('menus.edit');
        Route::post('/menus/store', [MenuController::class, 'store'])->name('menus.store');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        Route::get('/reports/sales', [ReportController::class, 'index'])->name('reports.sales');
        Route::get('/reports/sales/pdf', [ReportController::class, 'exportPdf'])->name('reports.sales.pdf');
        Route::get('/reports/sales/excel', [ReportController::class, 'exportExcel'])->name('reports.sales.excel');

        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');

        // Inventory Items
        Route::get('items', [InventoryController::class, 'items'])->name('inventory.items');
        Route::post('items', [InventoryController::class, 'store'])->name('inventory.items.store');

        // Stock movements
        Route::post('items/{item}/stock-in', [InventoryController::class, 'stockIn'])->name('inventory.stock-in');
        Route::post('items/{item}/stock-out', [InventoryController::class, 'stockOut'])->name('inventory.stock-out');

        Route::get('/orders/{order}/pdf', [OrderController::class, 'pdfReceipt'])->name('orders.pdf');

        Route::get('employees', [EmployeeController::class, 'getAllEmployeeForManager'])->name('employees.index');
        Route::post('/employees/store', [EmployeeController::class, 'store'])->name('employees.store');
        Route::put('/employees/edit/{id}', [EmployeeController::class, 'update'])->name('employees.edit');
        Route::post('/employees/{id}/employment', [EmployeeController::class, 'newEmployment'])->name('employment.edit');
        // Route::get('/employees/schedules', [EmployeeController::class, 'getSchedules'])->name('schedules');
        
        Route::post('/employees/{employee}/schedules', [EmployeeController::class, 'storeSchedule'])->name('employees.schedules.store');
        Route::put('/employees/schedules/{schedule}',[EmployeeController::class, 'updateSchedule'])->name('employees.schedules.update');
        Route::get('/employees/{employee}/schedule',[EmployeeController::class, 'getEmployeeSchedules'])->name('employees.schedule');
        Route::get('/employees/{employee}/schedules/print', [EmployeeController::class, 'printSchedule'])->name('employees.schedules.print');
        Route::get('/employees/schedules/print-all', [EmployeeController::class, 'printAllSchedules'])->name('employees.schedules.printAll');

        Route::post('/verify-manager-password', [POSController::class, 'verifyManagerPassword'])->name('verify-manager-password');
        Route::get('/pos/table-sessions', [TableController::class, 'getOpenSessions'])->name('table_sessions');
        
        Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
        Route::post('/vouchers', [VoucherController::class, 'store'])->name('vouchers.store');
        Route::put('/vouchers/{voucher}', [VoucherController::class, 'update'])->name('vouchers.update');
        Route::delete('/vouchers/{voucher}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');
        Route::get('/vouchers/{voucher}/print', [VoucherController::class, 'print'])->name('vouchers.print');
        Route::get('/vouchers/print-all', [VoucherController::class, 'printAll'])->name('vouchers.printAll');

        // Petty Cash index
        Route::get('/petty-cashes', [PettyCashController::class, 'index'])->name('petty-cashes');
        Route::post('/petty-cashes', [PettyCashController::class, 'store'])->name('petty-cashes.store');
        Route::post('/petty-cashes/{pettyCash}/details', [PettyCashController::class, 'storeDetail'])->name('petty-cashes.details.store');

        Route::get('head-pricing-rules', [HeadPricingRuleController::class, 'index'])->name('head-pricing-rules.index');
        Route::post('head-pricing-rules', [HeadPricingRuleController::class, 'store'])->name('head-pricing-rules.store');
        Route::put('head-pricing-rules/{id}', [HeadPricingRuleController::class, 'update'])->name('head-pricing-rules.update');
        Route::delete('head-pricing-rules/{id}', [HeadPricingRuleController::class, 'destroy'])->name('head-pricing-rules.destroy');
        
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses');
        Route::post('/expenses/fetch-filtered-expenses', [ExpenseController::class, 'getFilteredExpenses'])->name('expenses.fetch-filtered-expenses');
        Route::post('/expenses/store', [ExpenseController::class, 'store'])->name('expenses.store');

        // Master Reports (Inventory)
        Route::get('/reports/inventory', [InventoryController::class, 'inventoryReportIndex'])->name('inventory.report.index');
        Route::get('/reports/fetch-inventory-report', [ReportController::class, 'fetchInventoryReport'])->name('inventory.fetch-inventory-report');
        Route::get('/reports/print-inventory-report-pdf', [ReportController::class, 'printInventoryReport'])->name('inventory.print-inventory-report-pdf');
        
        // Master Reports (Expense)
        Route::get('/reports/expenses', [ReportController::class, 'expenseReportIndex'])->name('expenses.report.index');
        Route::get('/reports/fetch-expense-report', [ReportController::class, 'fetchExpenseReport'])->name('expenses.fetch-expense-report');
        Route::get('/reports/print-expense-report-pdf', [ReportController::class, 'printExpensesReport'])->name('expenses.print-expense-report-pdf');
        
        // Master Reports (Sales)
        Route::get('/reports/sales', [ReportController::class, 'salesReportIndex'])->name('sales.report.index');
        Route::get('/reports/fetch-sales-report', [ReportController::class, 'fetchSalesReport'])->name('sales.fetch-sales-report');
        Route::get('/reports/print-sales-report-pdf', [ReportController::class, 'printSalesReport'])->name('sales.print-sales-report-pdf');
        
        Route::get('myrecords', [EmployeeController::class, 'getMyRecords'])->name('myrecords');
        Route::post('/clock-in', [DTRController::class, 'clockIn'])->name('clockin');
        Route::post('/clock-out', [DTRController::class, 'clockOut'])->name('clockout');

    });

    // Admin routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/orders', [OrderController::class, 'index'])->name('orders');
        Route::get('/orders/fetch-filtered-orders', [OrderController::class, 'getFilteredOrders'])->name('orders.fetch-filtered-orders');
        Route::post('/orders/store', [OrderController::class, 'store'])->name('orders.store');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{orderItem}/cancel_item', [OrderController::class, 'cancel_item'])->name('orders.cancel_item');
        Route::get('/orders/{order}/pdf', [OrderController::class, 'pdfReceipt'])->name('orders.pdf');
        
        // Petty Cash index
        Route::get('/petty-cashes', [PettyCashController::class, 'index'])->name('petty-cashes');
        Route::post('/petty-cashes', [PettyCashController::class, 'store'])->name('petty-cashes.store');
        Route::post('/petty-cashes/{pettyCash}/details', [PettyCashController::class, 'storeDetail'])->name('petty-cashes.details.store');

        Route::get('/pos', [POSController::class, 'index'])->name('pos');

        Route::get('/menus', [MenuController::class, 'index'])->name('menus');
        Route::put('/menus/edit/{id}', [MenuController::class, 'update'])->name('menus.edit');
        Route::post('/menus/store', [MenuController::class, 'store'])->name('store.menus');
    
        Route::get('/users', [UserController::class, 'index'])->name('users');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
        
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        Route::get('/reports/sales', [ReportController::class, 'index'])->name('reports.sales');
        Route::get('/reports/sales/pdf', [ReportController::class, 'exportPdf'])->name('reports.sales.pdf');
        Route::get('/reports/sales/excel', [ReportController::class, 'exportExcel'])->name('reports.sales.excel');

        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');

        // Inventory Items
        Route::get('items', [InventoryController::class, 'items'])->name('inventory.items');
        Route::post('items', [InventoryController::class, 'store'])->name('inventory.items.store');

        // Stock movements
        Route::post('items/{item}/stock-in', [InventoryController::class, 'stockIn'])->name('inventory.stock-in');
        Route::post('items/{item}/stock-out', [InventoryController::class, 'stockOut'])->name('inventory.stock-out');
        
        Route::get('/inventory/categories', [InventoryController::class, 'getCategories'])->name('inventory.categories');
        Route::post('/inventory/categories', [InventoryController::class, 'storeCategory'])->name('inventory.categories.store');
        Route::get('/inventory/general-report', [InventoryController::class, 'printGeneralReport'])->name('inventory.general-report');
        
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses');
        Route::post('/expenses/fetch-filtered-expenses', [ExpenseController::class, 'getFilteredExpenses'])->name('expenses.fetch-filtered-expenses');
        Route::post('/expenses/store', [ExpenseController::class, 'store'])->name('expenses.store');
        
        Route::get('/tables', [TableController::class, 'index'])->name('tables');
        Route::post('/tables/store', [TableController::class, 'store'])->name('tables.store');
        Route::put('/tables/update/{table}', [TableController::class, 'update'])->name('tables.update');
        Route::delete('/tables/destroy/{table}', [TableController::class, 'destroy'])->name('tables.destroy');
        
        Route::post('/verify-manager-password', [POSController::class, 'verifyManagerPassword'])->name('verify-manager-password');
        Route::get('/pos/table-sessions', [TableController::class, 'getOpenSessions'])->name('table_sessions');
        
        Route::get('/branches', [BranchController::class, 'index'])->name('branches');

        Route::get('head-pricing-rules', [HeadPricingRuleController::class, 'index'])->name('head-pricing-rules.index');
        Route::post('head-pricing-rules', [HeadPricingRuleController::class, 'store'])->name('head-pricing-rules.store');
        Route::put('head-pricing-rules/{id}', [HeadPricingRuleController::class, 'update'])->name('head-pricing-rules.update');
        Route::delete('head-pricing-rules/{id}', [HeadPricingRuleController::class, 'destroy'])->name('head-pricing-rules.destroy');

        // Master Reports (Inventory)
        Route::get('/reports/inventory', [InventoryController::class, 'inventoryReportIndex'])->name('inventory.report.index');
        Route::get('/reports/fetch-inventory-report', [ReportController::class, 'fetchInventoryReport'])->name('inventory.fetch-inventory-report');
        Route::get('/reports/print-inventory-report-pdf', [ReportController::class, 'printInventoryReport'])->name('inventory.print-inventory-report-pdf');
        
        // Master Reports (Expense)
        Route::get('/reports/expenses', [ReportController::class, 'expenseReportIndex'])->name('expenses.report.index');
        Route::get('/reports/fetch-expense-report', [ReportController::class, 'fetchExpenseReport'])->name('expenses.fetch-expense-report');
        Route::get('/reports/print-expense-report-pdf', [ReportController::class, 'printExpensesReport'])->name('expenses.print-expense-report-pdf');
        
        // Master Reports (Sales)
        Route::get('/reports/sales', [ReportController::class, 'salesReportIndex'])->name('sales.report.index');
        Route::get('/reports/fetch-sales-report', [ReportController::class, 'fetchSalesReport'])->name('sales.fetch-sales-report');
        Route::get('/reports/print-sales-report-pdf', [ReportController::class, 'printSalesReport'])->name('sales.print-sales-report-pdf');

        Route::get('myrecords', [EmployeeController::class, 'getMyRecords'])->name('myrecords');
        Route::post('/clock-in', [DTRController::class, 'clockIn'])->name('clockin');
        Route::post('/clock-out', [DTRController::class, 'clockOut'])->name('clockout');
    });

    // Frontdoor
    Route::prefix('frontdoor')->name('frontdoor.')->group(function () {
        Route::get('/table_occupancies', [TableController::class, 'get_tables'])->name('table_occupancies');
        Route::post('/table_occupancies/assign/{table}', [TableController::class, 'assignTable'])->name('table_occupancies.assign');
        Route::post('/table_occupancies/{table}', [TableController::class, 'vacantTable'])->name('table_occupancies.update');

        Route::get('myrecords', [EmployeeController::class, 'getMyRecords'])->name('myrecords');
        Route::post('/clock-in', [DTRController::class, 'clockIn'])->name('clockin');
        Route::post('/clock-out', [DTRController::class, 'clockOut'])->name('clockout');
    });

    Route::prefix('purchaser')->name('purchaser.')->group(function () {
        
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');

        // Inventory Items
        Route::get('items', [InventoryController::class, 'items'])->name('inventory.items');
        Route::post('items', [InventoryController::class, 'store'])->name('inventory.items.store');

        // Stock movements
        Route::post('items/{item}/stock-in', [InventoryController::class, 'stockIn'])->name('inventory.stock-in');
        Route::post('items/{item}/stock-out', [InventoryController::class, 'stockOut'])->name('inventory.stock-out');
        
        Route::get('/inventory/categories', [InventoryController::class, 'getCategories'])->name('inventory.categories');
        Route::post('/inventory/categories', [InventoryController::class, 'storeCategory'])->name('inventory.categories.store');
        Route::get('/inventory/general-report', [InventoryController::class, 'printGeneralReport'])->name('inventory.general-report');
        
        Route::get('myrecords', [EmployeeController::class, 'getMyRecords'])->name('myrecords');
        Route::post('/clock-in', [DTRController::class, 'clockIn'])->name('clockin');
        Route::post('/clock-out', [DTRController::class, 'clockOut'])->name('clockout');
    });

    Route::prefix('kitchen')->name('kitchen.')->group(function () {
        
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');

        // Inventory Items
        Route::get('items', [InventoryController::class, 'items'])->name('inventory.items');
        Route::post('items', [InventoryController::class, 'store'])->name('inventory.items.store');

        // Stock movements
        Route::post('items/{item}/stock-in', [InventoryController::class, 'stockIn'])->name('inventory.stock-in');
        Route::post('items/{item}/stock-out', [InventoryController::class, 'stockOut'])->name('inventory.stock-out');
        
        Route::get('myrecords', [EmployeeController::class, 'getMyRecords'])->name('myrecords');
        Route::post('/clock-in', [DTRController::class, 'clockIn'])->name('clockin');
        Route::post('/clock-out', [DTRController::class, 'clockOut'])->name('clockout');
    });

    // HR
    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::post('/employees/store', [EmployeeController::class, 'store'])->name('employees.store');
        Route::put('/employees/edit/{id}', [EmployeeController::class, 'update'])->name('employees.edit');
        Route::post('/employees/{id}/employment', [EmployeeController::class, 'newEmployment'])->name('employment.edit');

        Route::post('/employees/{employee}/schedules', [EmployeeController::class, 'storeSchedule'])->name('employees.schedules.store');
        Route::put('/employees/schedules/{schedule}',[EmployeeController::class, 'updateSchedule'])->name('employees.schedules.update');
        Route::get('/employees/{employee}/schedule',[EmployeeController::class, 'getEmployeeSchedules'])->name('employees.schedule');
        Route::get('/employees/{employee}/schedules/print', [EmployeeController::class, 'printSchedule'])->name('employees.schedules.print');
        Route::get('/employees/schedules/print-all', [EmployeeController::class, 'printAllSchedules'])->name('employees.schedules.printAll');

        Route::put('/employees/{employee}/employment/{employment}/update',[EmployeeController::class, 'updateEmployment'])->name('employment.update');    
        Route::post('/employees/{employee}/employment/{employment}/documents/upload', [EmployeeController::class, 'uploadEmploymentDocument'])->name('employment.documents.upload');

        // routes/web.php
        Route::get('/employees/{employee}/dtr', [EmployeeController::class, 'showDTR'])->name('employees.dtr');
        Route::post('/employees/{employee}/dtr', [EmployeeController::class, 'updateDTR'])->name('employees.dtr.update');

        Route::get('/employees/{employee}/payroll/attendance-pdf', [EmployeeController::class, 'generateEmployeePayrollPdf'])->name('employees.payroll.attendance.pdf');
        
        Route::get('/users', [UserController::class, 'index'])->name('users');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
        
        Route::get('/dtr', [DTRController::class, 'DTRPage'])->name('dtr');
        // Payroll Summary Page (Vue page load)
        Route::get('/payroll/summary', [DTRController::class, 'summary'])->name('payroll.summary');

        // Print Payroll Summary
        Route::get('/payroll/summary/print', [DTRController::class, 'printPayrollSummary'])->name('payroll.summary.print');

        Route::get('myrecords', [EmployeeController::class, 'getMyRecords'])->name('myrecords');
        Route::post('/clock-in', [DTRController::class, 'clockIn'])->name('clockin');
        Route::post('/clock-out', [DTRController::class, 'clockOut'])->name('clockout');
    });

    Route::prefix('employee')->name('employee.')->group(function () {
        Route::get('myrecords', [EmployeeController::class, 'getMyRecords'])->name('myrecords');
        Route::post('/clock-in', [DTRController::class, 'clockIn'])->name('clockin');
        Route::post('/clock-out', [DTRController::class, 'clockOut'])->name('clockout');
    });

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::post('/employees/store', [EmployeeController::class, 'store'])->name('employees.store');
        Route::put('/employees/edit/{id}', [EmployeeController::class, 'update'])->name('employees.edit');
        Route::post('/employees/{id}/employment', [EmployeeController::class, 'newEmployment'])->name('employment.edit');

        Route::post('/employees/{employee}/schedules', [EmployeeController::class, 'storeSchedule'])->name('employees.schedules.store');
        Route::put('/employees/schedules/{schedule}',[EmployeeController::class, 'updateSchedule'])->name('employees.schedules.update');
        Route::get('/employees/{employee}/schedule',[EmployeeController::class, 'getEmployeeSchedules'])->name('employees.schedule');
        Route::get('/employees/{employee}/schedules/print', [EmployeeController::class, 'printSchedule'])->name('employees.schedules.print');
        Route::get('/employees/schedules/print-all', [EmployeeController::class, 'printAllSchedules'])->name('employees.schedules.printAll');

        Route::put('/employees/{employee}/employment/{employment}/update',[EmployeeController::class, 'updateEmployment'])->name('employment.update');    
        Route::post('/employees/{employee}/employment/{employment}/documents/upload', [EmployeeController::class, 'uploadEmploymentDocument'])->name('employment.documents.upload');

        // routes/web.php
        Route::get('/employees/{employee}/dtr', [EmployeeController::class, 'showDTR'])->name('employees.dtr');
        Route::post('/employees/{employee}/dtr', [EmployeeController::class, 'updateDTR'])->name('employees.dtr.update');

        Route::get('/employees/{employee}/payroll/attendance-pdf', [EmployeeController::class, 'generateEmployeePayrollPdf'])->name('employees.payroll.attendance.pdf');
        Route::get('/dtr', [DTRController::class, 'DTRPage'])->name('dtr');
        // Payroll Summary Page (Vue page load)
        Route::get('/payroll/summary', [DTRController::class, 'summary'])->name('payroll.summary');

        // Print Payroll Summary
        Route::get('/payroll/summary/print', [DTRController::class, 'printPayrollSummary'])->name('payroll.summary.print');
        
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');

        // Inventory Items
        Route::get('items', [InventoryController::class, 'items'])->name('inventory.items');
        Route::post('items', [InventoryController::class, 'store'])->name('inventory.items.store');

        // Stock movements
        Route::post('items/{item}/stock-in', [InventoryController::class, 'stockIn'])->name('inventory.stock-in');
        Route::post('items/{item}/stock-out', [InventoryController::class, 'stockOut'])->name('inventory.stock-out');
        
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses');
        Route::post('/expenses/store', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::post('/expenses/fetch-filtered-expenses', [ExpenseController::class, 'getFilteredExpenses'])->name('expenses.fetch-filtered-expenses');
        
        // Master Reports (Inventory)
        Route::get('/reports/inventory', [InventoryController::class, 'inventoryReportIndex'])->name('inventory.report.index');
        Route::get('/reports/fetch-inventory-report', [ReportController::class, 'fetchInventoryReport'])->name('inventory.fetch-inventory-report');
        Route::get('/reports/print-inventory-report-pdf', [ReportController::class, 'printInventoryReport'])->name('inventory.print-inventory-report-pdf');
        
        // Master Reports (Expense)
        Route::get('/reports/expenses', [ReportController::class, 'expenseReportIndex'])->name('expenses.report.index');
        Route::get('/reports/fetch-expense-report', [ReportController::class, 'fetchExpenseReport'])->name('expenses.fetch-expense-report');
        Route::get('/reports/print-expense-report-pdf', [ReportController::class, 'printExpensesReport'])->name('expenses.print-expense-report-pdf');
        
        // Master Reports (Sales)
        Route::get('/reports/sales', [ReportController::class, 'salesReportIndex'])->name('sales.report.index');
        Route::get('/reports/fetch-sales-report', [ReportController::class, 'fetchSalesReport'])->name('sales.fetch-sales-report');
        Route::get('/reports/print-sales-report-pdf', [ReportController::class, 'printSalesReport'])->name('sales.print-sales-report-pdf');
        
        Route::get('myrecords', [EmployeeController::class, 'getMyRecords'])->name('myrecords');
        Route::post('/clock-in', [DTRController::class, 'clockIn'])->name('clockin');
        Route::post('/clock-out', [DTRController::class, 'clockOut'])->name('clockout');
    });

    
});

require __DIR__.'/auth.php';
