# Earning Types & Deduction Types (Admin) — Short Design

## Intent
Admins need to manage the payroll `earning_types` and `deduction_types` lookup
tables from the admin area. Today these tables are seeded/populated indirectly
and there is no UI to add, edit, or deactivate entries.

## Why
Payroll and deduction templates already read from `earning_types` and
`deduction_types`. Giving admins a first-class management screen keeps those
lookups accurate without touching the database directly.

## Goals
- Add two menu items under the admin sidebar's **Management** section:
  **Earning Types** and **Deduction Types**.
- Provide full CRUD (list, create, update, delete) for both lookup tables.
- Match the `/admin/tables` page UI/UX (search, sticky-header table, status
  badge, blue Edit / red Delete, Add/Edit modals).
- Follow the existing thin-controller → service → Inertia → Vue pattern.

## Non-goals
- No changes to payroll computation, deduction templates, or exports.
- No bulk import/export, no reordering, no soft deletes beyond hard delete.
- No role/permission redesign beyond existing admin access.

## Constraints
- Schema is fixed: `id, name, description (nullable), status (boolean, default
  true), timestamps`. Models `EarningType` and `DeductionType` already exist.
- Admin area is `Route::prefix('admin')->name('admin.')` inside the existing
  `auth` + `verified` group; admin is `role === 0`.
- New routes must be registered in `database/seeders/RouteSeeder.php` for
  access-control listings, consistent with `admin.tables.*`.

## Approaches considered
1. **Two separate pages + two sidebar items (chosen).** Matches the granular
   Management submenu and keeps each lookup simple and independent.
2. One page with two tabs. Fewer files, but diverges from the existing sidebar
   and page conventions.

Chosen: two separate pages, because the Management submenu is already granular
and each list is small and independent.

## Design (approved)
### Sidebar
`resources/js/Components/NavLinks/AdminNavLinks.vue` — add two `<li>` entries in
the Management `<ul>`:
- Earning Types → `route('admin.earning-types.index')`
- Deduction Types → `route('admin.deduction-types.index')`
Bump the submenu transition `max-h-60` to accommodate the extra rows if needed.

### Backend
- `EarningTypeController` (index/store/update/destroy) delegating to
  `EarningTypeService`.
- `DeductionTypeController` (index/store/update/destroy) delegating to
  `DeductionTypeService`.
- Services validate `name` (required, max 255), `description` (nullable
  string), `status` (boolean), render Inertia pages, and return
  `back()->with('success'|'error')`.

### Routes (`$adminRoutes` in `routes/web.php`)
- `GET/POST /admin/earning-types` → `earning-types.index` / `.store`
- `PUT /admin/earning-types/{earningType}` → `.update`
- `DELETE /admin/earning-types/{earningType}` → `.destroy`
- Same shape for `/admin/deduction-types` → `deduction-types.*`.

### Frontend
- `resources/js/Pages/EarningTypes/Index.vue` and
  `resources/js/Pages/DeductionTypes/Index.vue`, cloned from the Tables page:
  header + Add button, search, table with ID / Name / Description / Status /
  Actions, status badge, Add/Edit modals with Name, Description, Status.
- Composables `useEarningTypesIndex.js` / `useDeductionTypesIndex.js` (or a
  shared `useLookupTypesIndex`) using Inertia `router` to call the endpoints.

### Access seeding
Add entries to `RouteSeeder.php` mirroring `admin.tables.*` for both resources.

## Left to the builder
- Exact icons, wording of empty states, and whether to share a single
  composable between the two pages.
