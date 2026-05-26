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
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PettyCashController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\configurations;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (Auth::check()) {
        return Inertia::render('Welcome', [
            'canLogin'       => false,
            'canRegister'    => false,
            'laravelVersion' => Application::VERSION,
            'phpVersion'     => PHP_VERSION,
        ]);
    }

    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Shared Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Reusable Shared Route Groups
    |--------------------------------------------------------------------------
    */

    // Shared attendance / my records routes
    $attendanceRoutes = function () {
        Route::get('myrecords', [EmployeeController::class, 'getMyRecords'])->name('myrecords');
        Route::post('/clock-in', [DTRController::class, 'clockIn'])->name('clockin');
        Route::post('/clock-out', [DTRController::class, 'clockOut'])->name('clockout');
        Route::post('/overtimes', [DTRController::class, 'saveOvertime'])->name('overtimes.store');
    };

    // Shared petty cash routes
    $pettyCashRoutes = function () {
        Route::get('/petty-cashes', [PettyCashController::class, 'index'])->name('petty-cashes');
        Route::post('/petty-cashes', [PettyCashController::class, 'store'])->name('petty-cashes.store');
        Route::post('/petty-cashes/{pettyCash}/details', [PettyCashController::class, 'storeDetail'])->name('petty-cashes.details.store');
    };

    // Shared inventory routes
    $inventoryRoutes = function ($withCategories = false, $withGeneralReport = false) {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');

        Route::get('items', [InventoryController::class, 'items'])->name('inventory.items');
        Route::post('items', [InventoryController::class, 'store'])->name('inventory.items.store');

        Route::post('items/{item}/stock-in', [InventoryController::class, 'stockIn'])->name('inventory.stock-in');
        Route::post('items/{item}/stock-out', [InventoryController::class, 'stockOut'])->name('inventory.stock-out');

        if ($withCategories) {
            Route::get('/inventory/categories', [InventoryController::class, 'getCategories'])->name('inventory.categories');
            Route::post('/inventory/categories', [InventoryController::class, 'storeCategory'])->name('inventory.categories.store');
        }

        if ($withGeneralReport) {
            Route::get('/inventory/general-report', [InventoryController::class, 'printGeneralReport'])->name('inventory.general-report');
        }
    };

    // Shared employee routes
    $employeeRoutes = function ($employeeIndexMethod = 'index') {
        Route::get('employees', [EmployeeController::class, $employeeIndexMethod])->name('employees.index');
        Route::post('/employees/store', [EmployeeController::class, 'store'])->name('employees.store');
        Route::put('/employees/edit/{id}', [EmployeeController::class, 'update'])->name('employees.edit');
        Route::post('/employees/{id}/employment', [EmployeeController::class, 'newEmployment'])->name('employment.edit');

        Route::post('/employees/{employee}/schedules', [EmployeeController::class, 'storeSchedule'])->name('employees.schedules.store');
        Route::post('/employees/{employee}/schedules/batch-store', [EmployeeController::class, 'storeBatchSchedule'])->name('employees.schedules.batchStore');
        Route::put('/employees/schedules/{schedule}', [EmployeeController::class, 'updateSchedule'])->name('employees.schedules.update');
        Route::get('/employees/{employee}/schedule', [EmployeeController::class, 'getEmployeeSchedules'])->name('employees.schedule');
        Route::get('/employees/{employee}/schedules/print', [EmployeeController::class, 'printSchedule'])->name('employees.schedules.print');
        Route::get('/employees/schedules/print-all', [EmployeeController::class, 'printAllSchedules'])->name('employees.schedules.printAll');

        Route::put('/employees/{employee}/employment/{employment}/update', [EmployeeController::class, 'updateEmployment'])->name('employment.update');
        Route::post('/employees/{employee}/employment/{employment}/documents/upload', [EmployeeController::class, 'uploadEmploymentDocument'])->name('employment.documents.upload');

        Route::get('/employees/{employee}/dtr', [EmployeeController::class, 'showDTR'])->name('employees.dtr');
        Route::post('/employees/{employee}/dtr', [EmployeeController::class, 'updateDTR'])->name('employees.dtr.update');

        Route::get('/employees/{employee}/payroll/attendance-pdf', [EmployeeController::class, 'generateEmployeePayrollPdf'])->name('employees.payroll.attendance.pdf');
    };

    // Shared payroll routes
    $payrollRoutes = function () {
        Route::get('/generate-payroll', [DTRController::class, 'generatePayroll'])->name('generate-payroll');
        Route::get('/payroll/summary', [DTRController::class, 'summary'])->name('payroll.summary');
        Route::post('/payroll/post_payroll', [PayrollController::class, 'postPayroll'])->name('payroll.post');
        Route::get('/payroll/list', [PayrollController::class, 'payrollIndex'])->name('payroll.index');
        Route::get('/payroll/{payroll}', [PayrollController::class, 'viewPayrollDetails'])->name('payroll.view-payroll-details');
        Route::post('/payroll/{payroll}/deductions', [PayrollController::class, 'postEmployeeDeduction'])->name('payroll.deduction.post');

        Route::get('/payroll/{payroll}/payslip/{payrollItem}/print', [PayrollController::class, 'printPayslipSingle'])->name('payroll.payslip.print.single');
        Route::get('/payroll/{payroll}/payslip/print-selected', [PayrollController::class, 'printPayslipSelected'])->name('payroll.payslip.print.selected');
        Route::get('/payroll/summary/print', [DTRController::class, 'printPayrollSummary'])->name('payroll.summary.print');
    };

    // Shared overtime management routes
    $overtimeApprovalRoutes = function () {
        Route::get('/employee-overtimes', [DTRController::class, 'getAllEmployeeOvertime'])->name('employees.overtimes');
        Route::get('/overtimes/filter', [DTRController::class, 'filterAllEmployeeOvertime'])->name('overtimes.filter');
        Route::put('/overtimes/{id}/update-status', [DTRController::class, 'updateEmployeeOTStatus'])->name('overtimes.update-status');
    };

    // Shared voucher routes
    $voucherRoutes = function () {
        Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
        Route::post('/vouchers', [VoucherController::class, 'store'])->name('vouchers.store');
        Route::put('/vouchers/{voucher}', [VoucherController::class, 'update'])->name('vouchers.update');
        Route::delete('/vouchers/{voucher}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');
        Route::get('/vouchers/{voucher}/print', [VoucherController::class, 'print'])->name('vouchers.print');
        Route::get('/vouchers/print-all', [VoucherController::class, 'printAll'])->name('vouchers.printAll');
    };

    // Shared sales report routes
    $salesReportRoutes = function () {
        Route::get('/reports/sales', [ReportController::class, 'salesReportIndex'])->name('sales.report.index');
        Route::get('/reports/fetch-sales-report', [ReportController::class, 'fetchSalesReport'])->name('sales.fetch-sales-report');
        Route::get('/reports/print-sales-report-pdf', [ReportController::class, 'printSalesReport'])->name('sales.print-sales-report-pdf');
    };

    // Shared inventory report routes
    $inventoryReportRoutes = function () {
        Route::get('/reports/inventory', [InventoryController::class, 'inventoryReportIndex'])->name('inventory.report.index');
        Route::get('/reports/fetch-inventory-report', [ReportController::class, 'fetchInventoryReport'])->name('inventory.fetch-inventory-report');
        Route::get('/reports/print-inventory-report-pdf', [ReportController::class, 'printInventoryReport'])->name('inventory.print-inventory-report-pdf');
    };

    // Shared expense report routes
    $expenseReportRoutes = function () {
        Route::get('/reports/expenses', [ReportController::class, 'expenseReportIndex'])->name('expenses.report.index');
        Route::get('/reports/fetch-expense-report', [ReportController::class, 'fetchExpenseReport'])->name('expenses.fetch-expense-report');
        Route::get('/reports/print-expense-report-pdf', [ReportController::class, 'printExpensesReport'])->name('expenses.print-expense-report-pdf');
    };

    /*
    |--------------------------------------------------------------------------
    | Cashier Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('cashier')->name('cashier.')->group(function () use ($attendanceRoutes, $pettyCashRoutes, $salesReportRoutes) {
        Route::get('/pos/table-sessions', [TableController::class, 'getOpenSessions'])->name('table_sessions');

        Route::get('/pos', [POSController::class, 'index'])->name('pos');
        Route::get('/pos-items', [POSController::class, 'indexPosItems'])->name('pos-items');
        Route::get('/pos/verify-voucher/{voucher_code}', [POSController::class, 'verifyVoucher'])->name('pos.verify-voucher');
        Route::post('/verify-manager-password', [POSController::class, 'verifyManagerPassword'])->name('verify-manager-password');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders');
        Route::get('/orders/fetch-filtered-orders', [OrderController::class, 'getFilteredOrders'])->name('orders.fetch-filtered-orders');
        Route::post('/orders/store', [OrderController::class, 'store'])->name('orders.store');
        Route::post('/orders/store/items', [OrderController::class, 'storeOrderedItems'])->name('orders.store-items');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{orderItem}/cancel_item', [OrderController::class, 'cancel_item'])->name('orders.cancel_item');
        Route::get('/orders/{order}/pdf', [OrderController::class, 'pdfReceipt'])->name('orders.pdf');
        Route::get('/orders/refresh-computation', [OrderController::class, 'refreshComputation'])->name('orders.refresh-computation');
        Route::post('/orders/store-leftover', [OrderController::class, 'storeLeftover'])->name('orders.store-leftover');
        Route::post('/orders/table-session-addons/store', [OrderController::class, 'storeTableSessionAddons']);
        Route::post('/orders/table-session-addons/{addon}/void', [OrderController::class, 'voidTableSessionAddon'])->name('orders.table_session_addons.void');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports');

        $pettyCashRoutes();
        $salesReportRoutes();
        $attendanceRoutes();
    });

    /*
    |--------------------------------------------------------------------------
    | Manager Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('manager')->name('manager.')->group(function () use (
        $attendanceRoutes,
        $pettyCashRoutes,
        $inventoryRoutes,
        $employeeRoutes,
        $voucherRoutes,
        $salesReportRoutes,
        $inventoryReportRoutes,
        $expenseReportRoutes,
        $overtimeApprovalRoutes
    ) {
        Route::get('/pos', [POSController::class, 'index'])->name('pos');
        Route::get('/pos/table-sessions', [TableController::class, 'getOpenSessions'])->name('table_sessions');
        Route::get('/pos/verify-voucher/{voucher_code}', [POSController::class, 'verifyVoucher'])->name('pos.verify-voucher');
        Route::post('/verify-manager-password', [POSController::class, 'verifyManagerPassword'])->name('verify-manager-password');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders');
        Route::get('/orders/fetch-filtered-orders', [OrderController::class, 'getFilteredOrders'])->name('orders.fetch-filtered-orders');
        Route::post('/orders/store', [OrderController::class, 'store'])->name('orders.store');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::get('/orders/{order}/pdf', [OrderController::class, 'pdfReceipt'])->name('orders.pdf');

        Route::get('/menus', [MenuController::class, 'index'])->name('menus');
        Route::put('/menus/edit/{id}', [MenuController::class, 'update'])->name('menus.edit');
        Route::post('/menus/store', [MenuController::class, 'store'])->name('menus.store');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports');

        $inventoryRoutes();
        $employeeRoutes('getAllEmployeeForManager');
        $voucherRoutes();
        $pettyCashRoutes();

        Route::get('head-pricing-rules', [HeadPricingRuleController::class, 'index'])->name('head-pricing-rules.index');
        Route::post('head-pricing-rules', [HeadPricingRuleController::class, 'store'])->name('head-pricing-rules.store');
        Route::put('head-pricing-rules/{id}', [HeadPricingRuleController::class, 'update'])->name('head-pricing-rules.update');
        Route::delete('head-pricing-rules/{id}', [HeadPricingRuleController::class, 'destroy'])->name('head-pricing-rules.destroy');

        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses');
        Route::post('/expenses/fetch-filtered-expenses', [ExpenseController::class, 'getFilteredExpenses'])->name('expenses.fetch-filtered-expenses');
        Route::post('/expenses/store', [ExpenseController::class, 'store'])->name('expenses.store');

        $inventoryReportRoutes();
        $expenseReportRoutes();
        $salesReportRoutes();
        $attendanceRoutes();
        $overtimeApprovalRoutes();
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('admin')->name('admin.')->group(function () use (
        $attendanceRoutes,
        $pettyCashRoutes,
        $inventoryRoutes,
        $employeeRoutes,
        $payrollRoutes,
        $voucherRoutes,
        $salesReportRoutes,
        $inventoryReportRoutes,
        $expenseReportRoutes
    ) {
        Route::get('/pos', [POSController::class, 'index'])->name('pos');
        Route::get('/pos/table-sessions', [TableController::class, 'getOpenSessions'])->name('table_sessions');
        Route::post('/verify-manager-password', [POSController::class, 'verifyManagerPassword'])->name('verify-manager-password');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders');
        Route::get('/orders/fetch-filtered-orders', [OrderController::class, 'getFilteredOrders'])->name('orders.fetch-filtered-orders');
        Route::post('/orders/store', [OrderController::class, 'store'])->name('orders.store');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{orderItem}/cancel_item', [OrderController::class, 'cancel_item'])->name('orders.cancel_item');
        Route::get('/orders/{order}/pdf', [OrderController::class, 'pdfReceipt'])->name('orders.pdf');

        Route::get('/menus', [MenuController::class, 'index'])->name('menus');
        Route::put('/menus/edit/{id}', [MenuController::class, 'update'])->name('menus.edit');
        Route::post('/menus/store', [MenuController::class, 'store'])->name('menus.store');

        Route::get('/users', [UserController::class, 'index'])->name('users');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports');

        $inventoryRoutes(true, true);
        $pettyCashRoutes();

        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses');
        Route::post('/expenses/fetch-filtered-expenses', [ExpenseController::class, 'getFilteredExpenses'])->name('expenses.fetch-filtered-expenses');
        Route::post('/expenses/store', [ExpenseController::class, 'store'])->name('expenses.store');

        Route::get('/tables', [TableController::class, 'index'])->name('tables');
        Route::post('/tables/store', [TableController::class, 'store'])->name('tables.store');
        Route::put('/tables/update/{table}', [TableController::class, 'update'])->name('tables.update');
        Route::delete('/tables/destroy/{table}', [TableController::class, 'destroy'])->name('tables.destroy');

        Route::get('/branches', [BranchController::class, 'index'])->name('branches');

        Route::get('head-pricing-rules', [HeadPricingRuleController::class, 'index'])->name('head-pricing-rules.index');
        Route::post('head-pricing-rules', [HeadPricingRuleController::class, 'store'])->name('head-pricing-rules.store');
        Route::put('head-pricing-rules/{id}', [HeadPricingRuleController::class, 'update'])->name('head-pricing-rules.update');
        Route::delete('head-pricing-rules/{id}', [HeadPricingRuleController::class, 'destroy'])->name('head-pricing-rules.destroy');

        $employeeRoutes();
        $payrollRoutes();
        $voucherRoutes();
        $inventoryReportRoutes();
        $expenseReportRoutes();
        $salesReportRoutes();
        $attendanceRoutes();

        Route::get('configurations', [ConfigurationController::class, 'index'])->name('configurations');
        Route::post('/configurations', [ConfigurationController::class, 'store'])->name('configurations.store');
        Route::put('/configurations/{configuration}', [ConfigurationController::class, 'update'])->name('configurations.update');

        
        Route::get('/head-pricing-rules', [HeadPricingRuleController::class, 'index'])->name('head-pricing-rules.index');

        Route::post('/pricing-schemes', [HeadPricingRuleController::class, 'storePricingScheme'])->name('pricing-schemes.store');
        Route::put('/pricing-schemes/{pricingScheme}', [HeadPricingRuleController::class, 'updatePricingScheme'])->name('pricing-schemes.update');

        Route::post('/head-pricing-rules', [HeadPricingRuleController::class, 'store'])->name('head-pricing-rules.store');
        Route::put('/head-pricing-rules/{headPricingRule}', [HeadPricingRuleController::class, 'update'])->name('head-pricing-rules.update');
        Route::delete('/head-pricing-rules/{headPricingRule}', [HeadPricingRuleController::class, 'destroy'])->name('head-pricing-rules.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Frontdoor Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('frontdoor')->name('frontdoor.')->group(function () use ($attendanceRoutes) {
        Route::get('/table_occupancies', [TableController::class, 'get_tables'])->name('table_occupancies');
        Route::get('/table_admissions', [TableController::class, 'get_tables_for_admission'])->name('table_admission');
        Route::post('/table_occupancies/assign/{table}', [TableController::class, 'assignTable'])->name('table_occupancies.assign');
        Route::post('/table_occupancies/{table}', [TableController::class, 'vacantTable'])->name('table_occupancies.update');
        Route::post('/table-occupancies/{table}/cancel', [TableController::class, 'cancelQueuedTable'])->name('table_occupancies.cancel');
        Route::post('/frontdoor/table-admission/assign', [TableController::class, 'admitTable'])->name('table_admission.assign');
        // Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations');
        Route::resource('reservations', ReservationController::class);
        Route::get('/pos', [POSController::class, 'index'])->name('pos');

        $attendanceRoutes();
    });

    /*
    |--------------------------------------------------------------------------
    | Purchaser Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('purchaser')->name('purchaser.')->group(function () use ($attendanceRoutes, $inventoryRoutes) {
        $inventoryRoutes(true, true);
        $attendanceRoutes();
    });

    /*
    |--------------------------------------------------------------------------
    | Kitchen Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('kitchen')->name('kitchen.')->group(function () use ($attendanceRoutes, $inventoryRoutes) {
        $inventoryRoutes();
        $attendanceRoutes();
    });

    /*
    |--------------------------------------------------------------------------
    | HR Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('hr')->name('hr.')->group(function () use (
        $attendanceRoutes,
        $employeeRoutes,
        $payrollRoutes,
        $overtimeApprovalRoutes
    ) {
        $employeeRoutes();

        Route::get('/users', [UserController::class, 'index'])->name('users');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');

        $payrollRoutes();
        $attendanceRoutes();
        $overtimeApprovalRoutes();
    });

    /*
    |--------------------------------------------------------------------------
    | Finance Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('finance')->name('finance.')->group(function () use (
        $attendanceRoutes,
        $employeeRoutes,
        $payrollRoutes,
        $inventoryRoutes,
        $inventoryReportRoutes,
        $expenseReportRoutes,
        $salesReportRoutes
    ) {
        $employeeRoutes();
        $payrollRoutes();
        $inventoryRoutes();

        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses');
        Route::post('/expenses/store', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::post('/expenses/fetch-filtered-expenses', [ExpenseController::class, 'getFilteredExpenses'])->name('expenses.fetch-filtered-expenses');

        $inventoryReportRoutes();
        $expenseReportRoutes();
        $salesReportRoutes();
        $attendanceRoutes();
    });
});

require __DIR__ . '/auth.php';