1. Sidebar (/admin)
- resources/js/Components/NavLinks/AdminNavLinks.vue
- Bagong grouping: POS, Frontdoor, Management, Configurations, HR, Finance, Reports, My Biometrix.
- Inalis ang standalone na Reservations, Packages, Inventory, Expenses, Payroll, Employee.
- Idinagdag sa Finance: Inv. Categories, Inv. Items, Inv. Summary (direkta, walang nested Inventory).
- ⚠️ Kasama na rin dito ang prior changes (Earning/Deduction Types menu at max-h-72).

2. Delete button (Earning/Deduction Types)
- resources/js/Pages/EarningTypes/Index.vue — naka-comment ang Delete button.
- resources/js/Pages/DeductionTypes/Index.vue — naka-comment ang Delete button.
- ⚠️ Bagong file ito mula sa dating feature; buong file ang ipapatch.

3. Cashier Expenses (/cashier/cashier_expenses)
- resources/js/Pages/Expenses/CashierExpensePage.vue
- Add & Edit modal: Category dropdown, Requesting Name, Description, Amount, Account Title (ganitong order).
- ⚠️ May prior uncommitted changes na rin ito bago pa ang session.
- app/Models/Expense.php — idinagdag sa $fillable.
- app/Services/ExpenseService.php — validation/persist ng bagong fields + categories prop.
- database/migrations/2026_11_22_000001_add_requesting_name_and_account_title_to_expenses_table.php — bagong migration.

Deploy notes (production)
1. Patch ang mga file sa itaas.
2. Patakbuhin ang migration:
php artisan migrate --path=database/migrations/2026_11_22_000001_add_requesting_name_and_account_title_to_expenses_table.php --force
3. I-rebuild ang assets:
npm run build
Hindi ko ginalaw sa session na ito (pre-existing / prior session)
routes/web.php, database/seeders/RouteSeeder.php, FinanceNavLinks.vue, at ang Earning/Deduction Types controllers/services/tests/docs — huwag isama sa patch kung hindi kailangan


resources/js/Components/NavLinks/AdminNavLinks.vue - DONE
resources/js/Pages/EarningTypes/Index.vue - DONE
resources/js/Pages/DeductionTypes/Index.vue - DONE
resources/js/Pages/Expenses/CashierExpensePage.vue - DONE
app/Models/Expense.php - DONE
app/Services/ExpenseService.php - DONE
- add_requesting_name_and_account_title_to_expenses_table.php - DONE
app/Exports/ExpensesExport.php - DONE
app/Services/ExpenseService.php - DONE
app/Http/Controllers/ExpenseController.php - DONE
routes/web.php - DONE
resources/js/Composables/expenses/useExpensesIndex.js - DONE
resources/js/Pages/Expenses/Index.vue - DONE