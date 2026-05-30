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
use App\Http\Controllers\CashRegisterController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
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
    Route::get('/user-accesses', [UserController::class, 'getUserAccesses'])->middleware('auth');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Reusable Shared Route Groups
    |--------------------------------------------------------------------------
    */

    $attendanceRoutes = function () {
        Route::get('/myrecords', [EmployeeController::class, 'getMyRecords'])->name('myrecords');
        Route::post('/clock-in', [DTRController::class, 'clockIn'])->name('clockin');
        Route::post('/clock-out', [DTRController::class, 'clockOut'])->name('clockout');
        Route::post('/overtimes', [DTRController::class, 'saveOvertime'])->name('overtimes.store');
    };

    $pettyCashRoutes = function () {
        Route::get('/petty-cashes', [PettyCashController::class, 'index'])->name('petty-cashes');
        Route::post('/petty-cashes', [PettyCashController::class, 'store'])->name('petty-cashes.store');
        Route::post('/petty-cashes/{pettyCash}/details', [PettyCashController::class, 'storeDetail'])->name('petty-cashes.details.store');
    };

    $cashRegisterRoutes = function () {
        Route::get('/cash-registers', [CashRegisterController::class, 'index'])->name('cash-registers');
        Route::get('/cash-registers/fetch', [CashRegisterController::class, 'fetchCashRegistered'])->name('fetch_registered_cashes');
        Route::post('/cash-registers', [CashRegisterController::class, 'store'])->name('cash-registers.store');
        Route::put('cash-registers/{id}', [CashRegisterController::class, 'update'])->name('cash-registers.udpate');
    };

    $inventoryRoutes = function ($withCategories = false, $withGeneralReport = false) {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');

        Route::get('/items', [InventoryController::class, 'items'])->name('inventory.items');
        Route::post('/items', [InventoryController::class, 'store'])->name('inventory.items.store');

        Route::post('/items/{item}/stock-in', [InventoryController::class, 'stockIn'])->name('inventory.stock-in');
        Route::post('/items/{item}/stock-out', [InventoryController::class, 'stockOut'])->name('inventory.stock-out');

        Route::post('/inventory/{item}/physical-count', [InventoryController::class, 'physicalCount'])->name('inventory.physical-count');

        if ($withCategories) {
            Route::get('/inventory/categories', [InventoryController::class, 'getCategories'])->name('inventory.categories');
            Route::post('/inventory/categories', [InventoryController::class, 'storeCategory'])->name('inventory.categories.store');
        }

        if ($withGeneralReport) {
            Route::get('/inventory/general-report', [InventoryController::class, 'printGeneralReport'])->name('inventory.general-report');
        }
    };

    $employeeRoutes = function ($employeeIndexMethod = 'index') {
        Route::get('/employees', [EmployeeController::class, $employeeIndexMethod])->name('employees.index');
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
        Route::delete('/employees/documents/{doc}/delete', [EmployeeController::class, 'deleteEmploymentDocument'])->name('employment.documents.delete');

        Route::get('/employees/{employee}/dtr', [EmployeeController::class, 'showDTR'])->name('employees.dtr');
        Route::post('/employees/{employee}/dtr', [EmployeeController::class, 'updateDTR'])->name('employees.dtr.update');

        Route::get('/employees/{employee}/payroll/attendance-pdf', [EmployeeController::class, 'generateEmployeePayrollPdf'])->name('employees.payroll.attendance.pdf');
    };

    $payrollRoutes = function () {
        Route::get('/generate-payroll', [DTRController::class, 'generatePayroll'])->name('generate-payroll');
        Route::get('/payroll/summary', [DTRController::class, 'summary'])->name('payroll.summary');
        Route::post('/payroll/post_payroll', [PayrollController::class, 'postPayroll'])->name('payroll.post');
        Route::get('/payroll/list', [PayrollController::class, 'payrollIndex'])->name('payroll.index');
        Route::get('/payroll/{payroll}', [PayrollController::class, 'viewPayrollDetails'])->name('payroll.view-payroll-details');
        Route::post('/payroll/{payroll}/deductions', [PayrollController::class, 'postEmployeeDeduction'])->name('payroll.deduction.post');
        Route::post('/payroll/{payroll}/earnings', [PayrollController::class, 'postEmployeeEarnings'])->name('payroll.earnings.post');

        Route::get('/payroll/{payroll}/payslip/{payrollItem}/print', [PayrollController::class, 'printPayslipSingle'])->name('payroll.payslip.print.single');
        Route::get('/payroll/{payroll}/payslip/print-selected', [PayrollController::class, 'printPayslipSelected'])->name('payroll.payslip.print.selected');
        Route::get('/payroll/summary/print', [DTRController::class, 'printPayrollSummary'])->name('payroll.summary.print');
    };

    $overtimeApprovalRoutes = function () {
        Route::get('/employee-overtimes', [DTRController::class, 'getAllEmployeeOvertime'])->name('employees.overtimes');
        Route::get('/overtimes/filter', [DTRController::class, 'filterAllEmployeeOvertime'])->name('overtimes.filter');
        Route::put('/overtimes/{id}/update-status', [DTRController::class, 'updateEmployeeOTStatus'])->name('overtimes.update-status');
    };

    $voucherRoutes = function () {
        Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
        Route::post('/vouchers', [VoucherController::class, 'store'])->name('vouchers.store');
        Route::put('/vouchers/{voucher}', [VoucherController::class, 'update'])->name('vouchers.update');
        Route::delete('/vouchers/{voucher}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');
        Route::get('/vouchers/{voucher}/print', [VoucherController::class, 'print'])->name('vouchers.print');
        Route::get('/vouchers/print-all', [VoucherController::class, 'printAll'])->name('vouchers.printAll');
    };

    $salesReportRoutes = function () {
        Route::get('/reports/sales', [ReportController::class, 'salesReportIndex'])->name('sales.report.index');
        Route::get('/reports/fetch-sales-report', [ReportController::class, 'fetchSalesReport'])->name('sales.fetch-sales-report');
        Route::get('/reports/print-sales-report-pdf', [ReportController::class, 'printSalesReport'])->name('sales.print-sales-report-pdf');
        Route::get('/reports/print-sales-report-summary-pdf', [ReportController::class, 'printSalesSummaryReport'])->name('sales.print-sales-report-summary-pdf');
        Route::get('/reports/export-sales-report-excel', [ReportController::class, 'exportSalesReportExcel'])->name('sales.export-sales-report-excel');
    };

    $inventoryReportRoutes = function () {
        Route::get('/reports/inventory', [InventoryController::class, 'inventoryReportIndex'])->name('inventory.report.index');
        Route::get('/reports/fetch-inventory-report', [ReportController::class, 'fetchInventoryReport'])->name('inventory.fetch-inventory-report');
        Route::get('/reports/print-inventory-report-pdf', [ReportController::class, 'printInventoryReport'])->name('inventory.print-inventory-report-pdf');
        Route::get('/reports/inventory/export-excel', [ReportController::class, 'exportInventoryReportExcel'])->name('inventory.export-inventory-report-excel');
    };

    $expenseReportRoutes = function () {
        Route::get('/reports/expenses', [ReportController::class, 'expenseReportIndex'])->name('expenses.report.index');
        Route::get('/reports/fetch-expense-report', [ReportController::class, 'fetchExpenseReport'])->name('expenses.fetch-expense-report');
        Route::get('/reports/print-expense-report-pdf', [ReportController::class, 'printExpensesReport'])->name('expenses.print-expense-report-pdf');
    };

    $posRoutes = function () {
        Route::get('/pos', [POSController::class, 'index'])->name('pos');
        Route::get('/pos/table-sessions', [TableController::class, 'getOpenSessions'])->name('table_sessions');
        Route::get('/pos-items', [POSController::class, 'indexPosItems'])->name('pos-items');
        Route::get('/pos/verify-voucher/{voucher_code}', [POSController::class, 'verifyVoucher'])->name('pos.verify-voucher');
        Route::post('/verify-manager-password', [POSController::class, 'verifyManagerPassword'])->name('verify-manager-password');
    };

    $orderRoutes = function (
        $withStoreItems = false,
        $withCancelItem = false,
        $withRefreshComputation = false,
        $withLeftover = false,
        $withTableSessionAddons = false
    ) {
        Route::get('/orders', [OrderController::class, 'index'])->name('orders');
        Route::get('/orders/fetch-filtered-orders', [OrderController::class, 'getFilteredOrders'])->name('orders.fetch-filtered-orders');
        Route::post('/orders/store', [OrderController::class, 'store'])->name('orders.store');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::get('/orders/{order}/pdf', [OrderController::class, 'pdfReceipt'])->name('orders.pdf');

        if ($withStoreItems) {
            Route::post('/orders/store/items', [OrderController::class, 'storeOrderedItems'])->name('orders.store-items');
        }

        if ($withCancelItem) {
            Route::post('/orders/{orderItem}/cancel_item', [OrderController::class, 'cancel_item'])->name('orders.cancel_item');
        }

        if ($withRefreshComputation) {
            Route::get('/orders/refresh-computation', [OrderController::class, 'refreshComputation'])->name('orders.refresh-computation');
        }

        if ($withLeftover) {
            Route::post('/orders/store-leftover', [OrderController::class, 'storeLeftover'])->name('orders.store-leftover');
        }

        if ($withTableSessionAddons) {
            Route::post('/orders/table-session-addons/store', [OrderController::class, 'storeTableSessionAddons'])->name('orders.table_session_addons.store');
            Route::post('/orders/table-session-addons/{addon}/void', [OrderController::class, 'voidTableSessionAddon'])->name('orders.table_session_addons.void');
        }
    };

    $menuRoutes = function () {
        Route::get('/menus', [MenuController::class, 'index'])->name('menus');
        Route::put('/menus/edit/{id}', [MenuController::class, 'update'])->name('menus.edit');
        Route::post('/menus/store', [MenuController::class, 'store'])->name('menus.store');
    };

    $expenseRoutes = function () {
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses');
        Route::post('/expenses/fetch-filtered-expenses', [ExpenseController::class, 'getFilteredExpenses'])->name('expenses.fetch-filtered-expenses');
        Route::post('/expenses/store', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/cashier_expenses', [ExpenseController::class, 'cashierExpenseIndex'])->name('cashier_expenses');
        Route::get('/cashier_expenses/fetch', [ExpenseController::class, 'fetchCashierExpenses'])->name('cashier_expenses.fetch');
        Route::put('/cashier_expenses/{id}', [ExpenseController::class, 'updateCashierExpenses'])->name('cashier_expenses.update');
    };

    $reportIndexRoutes = function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    };

    $frontdoorTableRoutes = function () {
        Route::get('/table_occupancies', [TableController::class, 'get_tables'])->name('table_occupancies');
        Route::get('/table_admissions', [TableController::class, 'get_tables_for_admission'])->name('table_admission');
        Route::post('/table_occupancies/assign/{table}', [TableController::class, 'assignTable'])->name('table_occupancies.assign');
        Route::post('/table_occupancies/{table}', [TableController::class, 'vacantTable'])->name('table_occupancies.update');
        Route::post('/table-occupancies/{table}/cancel', [TableController::class, 'cancelQueuedTable'])->name('table_occupancies.cancel');
        Route::post('/table-admission/assign', [TableController::class, 'admitTable'])->name('table_admission.assign');
        Route::resource('/reservations', ReservationController::class);
    };

    /*
    |--------------------------------------------------------------------------
    | Non-Admin Shared Routes
    |--------------------------------------------------------------------------
    | All non-admin roles can access these routes under their own prefix.
    | Example:
    |   /frontdoor/pos
    |   /cashier/pos
    |   /manager/pos
    |--------------------------------------------------------------------------
    */
    $nonAdminSharedRoutes = function () use (
        $attendanceRoutes,
        $pettyCashRoutes,
        $cashRegisterRoutes,
        $inventoryRoutes,
        $employeeRoutes,
        $payrollRoutes,
        $voucherRoutes,
        $salesReportRoutes,
        $inventoryReportRoutes,
        $expenseReportRoutes,
        $overtimeApprovalRoutes,
        $posRoutes,
        $orderRoutes,
        $menuRoutes,
        $expenseRoutes,
        $reportIndexRoutes,
        $frontdoorTableRoutes
    ) {
        $posRoutes();
        $orderRoutes(true, true, true, true, true);
        $menuRoutes();
        $reportIndexRoutes();
        $expenseRoutes();
        $frontdoorTableRoutes();

        $inventoryRoutes(true, true);
        $employeeRoutes();
        $payrollRoutes();
        $voucherRoutes();
        $pettyCashRoutes();
        $cashRegisterRoutes();

        Route::get('/head-pricing-rules', [HeadPricingRuleController::class, 'index'])->name('head-pricing-rules.index');
        Route::post('/head-pricing-rules', [HeadPricingRuleController::class, 'store'])->name('head-pricing-rules.store');
        Route::put('/head-pricing-rules/{id}', [HeadPricingRuleController::class, 'update'])->name('head-pricing-rules.update');
        Route::delete('/head-pricing-rules/{id}', [HeadPricingRuleController::class, 'destroy'])->name('head-pricing-rules.destroy');

        Route::post('/pricing-schemes', [HeadPricingRuleController::class, 'storePricingScheme'])->name('pricing-schemes.store');
        Route::put('/pricing-schemes/{pricingScheme}', [HeadPricingRuleController::class, 'updatePricingScheme'])->name('pricing-schemes.update');

        $inventoryReportRoutes();
        $expenseReportRoutes();
        $salesReportRoutes();
        $attendanceRoutes();
        $overtimeApprovalRoutes();
    };

    /*
    |--------------------------------------------------------------------------
    | Admin Routes Only
    |--------------------------------------------------------------------------
    */
    $adminRoutes = function () use (
        $attendanceRoutes,
        $pettyCashRoutes,
        $cashRegisterRoutes,
        $inventoryRoutes,
        $employeeRoutes,
        $payrollRoutes,
        $voucherRoutes,
        $salesReportRoutes,
        $inventoryReportRoutes,
        $expenseReportRoutes,
        $posRoutes,
        $orderRoutes,
        $menuRoutes,
        $expenseRoutes,
        $reportIndexRoutes
    ) {
        $posRoutes();
        $orderRoutes(true, true, true, true, true);
        $menuRoutes();
        $reportIndexRoutes();

        Route::get('/users', [UserController::class, 'index'])->name('users');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');

        $inventoryRoutes(true, true);
        $pettyCashRoutes();
        $cashRegisterRoutes();
        $expenseRoutes();

        Route::get('/tables', [TableController::class, 'index'])->name('tables');
        Route::post('/tables/store', [TableController::class, 'store'])->name('tables.store');
        Route::put('/tables/update/{table}', [TableController::class, 'update'])->name('tables.update');
        Route::delete('/tables/destroy/{table}', [TableController::class, 'destroy'])->name('tables.destroy');

        Route::get('/branches', [BranchController::class, 'index'])->name('branches');

        Route::get('/head-pricing-rules', [HeadPricingRuleController::class, 'index'])->name('head-pricing-rules.index');
        Route::post('/head-pricing-rules', [HeadPricingRuleController::class, 'store'])->name('head-pricing-rules.store');
        Route::put('/head-pricing-rules/{headPricingRule}', [HeadPricingRuleController::class, 'update'])->name('head-pricing-rules.update');
        Route::delete('/head-pricing-rules/{headPricingRule}', [HeadPricingRuleController::class, 'destroy'])->name('head-pricing-rules.destroy');

        Route::post('/pricing-schemes', [HeadPricingRuleController::class, 'storePricingScheme'])->name('pricing-schemes.store');
        Route::put('/pricing-schemes/{pricingScheme}', [HeadPricingRuleController::class, 'updatePricingScheme'])->name('pricing-schemes.update');

        $employeeRoutes();
        $payrollRoutes();
        $voucherRoutes();
        $inventoryReportRoutes();
        $expenseReportRoutes();
        $salesReportRoutes();
        $attendanceRoutes();

        Route::get('/configurations', [ConfigurationController::class, 'index'])->name('configurations');
        Route::post('/configurations', [ConfigurationController::class, 'store'])->name('configurations.store');
        Route::put('/configurations/{configuration}', [ConfigurationController::class, 'update'])->name('configurations.update');
    };

    /*
    |--------------------------------------------------------------------------
    | Cashier Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('cashier')->name('cashier.')->group(function () use ($nonAdminSharedRoutes) {
        $nonAdminSharedRoutes();
    });

    /*
    |--------------------------------------------------------------------------
    | Manager Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('manager')->name('manager.')->group(function () use ($nonAdminSharedRoutes) {
        $nonAdminSharedRoutes();
    });

    /*
    |--------------------------------------------------------------------------
    | Frontdoor Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('frontdoor')->name('frontdoor.')->group(function () use ($nonAdminSharedRoutes) {
        $nonAdminSharedRoutes();
    });

    /*
    |--------------------------------------------------------------------------
    | Purchaser Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('purchaser')->name('purchaser.')->group(function () use ($nonAdminSharedRoutes) {
        $nonAdminSharedRoutes();
    });

    /*
    |--------------------------------------------------------------------------
    | Kitchen Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('kitchen')->name('kitchen.')->group(function () use ($nonAdminSharedRoutes) {
        $nonAdminSharedRoutes();
    });

    /*
    |--------------------------------------------------------------------------
    | HR Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('hr')->name('hr.')->group(function () use ($nonAdminSharedRoutes) {
        $nonAdminSharedRoutes();
    });

    /*
    |--------------------------------------------------------------------------
    | Finance Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('finance')->name('finance.')->group(function () use ($nonAdminSharedRoutes) {
        $nonAdminSharedRoutes();
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->group(function () use ($adminRoutes) {
        $adminRoutes();
    });

    /*
    |--------------------------------------------------------------------------
    | Cook Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('cook')->name('cook.')->group(function () use ($nonAdminSharedRoutes) {
        $nonAdminSharedRoutes();
    });

    /*
    |--------------------------------------------------------------------------
    | LineCook Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('linecook')->name('linecook.')->group(function () use ($nonAdminSharedRoutes) {
        $nonAdminSharedRoutes();
    });
    /*
    |--------------------------------------------------------------------------
    | Waiter Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('waiter')->name('waiter.')->group(function () use ($nonAdminSharedRoutes) {
        $nonAdminSharedRoutes();
    });

    /*
    |--------------------------------------------------------------------------
    | Waitress Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('waitress')->name('waitress.')->group(function () use ($nonAdminSharedRoutes) {
        $nonAdminSharedRoutes();
    });

    /*
    |--------------------------------------------------------------------------
    | Dishwasher Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('dishwasher')->name('dishwasher.')->group(function () use ($nonAdminSharedRoutes) {
        $nonAdminSharedRoutes();
    });

    /*
    |--------------------------------------------------------------------------
    | Headwaiter Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('headwaiter')->name('headwaiter.')->group(function () use ($nonAdminSharedRoutes) {
        $nonAdminSharedRoutes();
    });



    
});

require __DIR__ . '/auth.php';