# Earning Types & Deduction Types (Admin) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add admin CRUD screens and sidebar menu items for payroll earning types and deduction types.

**Architecture:** Thin `EarningTypeController`/`DeductionTypeController` delegate to `EarningTypeService`/`DeductionTypeService`, which validate input and render Inertia pages. Two Vue pages clone the `/admin/tables` UI and share one parameterized composable. Routes are added to the existing `$adminRoutes` closure.

**Tech Stack:** Laravel 11, Inertia 2, Vue 3 (`<script setup>`), Tailwind, FontAwesome, PHPUnit 11.

**Spec:** `docs/superpowers/specs/2026-11-21-earning-deduction-types-design.md`

## Global Constraints

- Schema is fixed: `earning_types` and `deduction_types` each have `id, name (string), description (nullable text), status (boolean, default true), timestamps`. Models `App\Models\EarningType` and `App\Models\DeductionType` already exist with `name, description, status` fillable.
- Admin area is `Route::prefix('admin')->name('admin.')` inside the existing `auth` + `verified` group. Do not add role middleware.
- Follow existing conventions: controller try/catch + `Log::error`, service holds logic and returns `Inertia::render(...)` / `back()->with(...)`.
- Inertia prop keys: `earning_types`, `deduction_types` (snake_case).
- **Do not commit.** Leave all changes in the working tree; the session instruction forbids commits unless explicitly requested.
- Tests use `DatabaseTransactions` (never `RefreshDatabase`) because `phpunit.xml` points at the live MySQL dev DB.
- Page component paths must be `EarningTypes/Index` and `DeductionTypes/Index` (Inertia resolves under `resources/js/Pages`).

## Review Focus

- **Blank/whitespace `name` on create or update** — validation rejects it and no row is written. (Task 1 test)
- **Name longer than 255 chars** — validation rejects it. (covered by `max:255`, no separate test)
- **`status` omitted on create** — defaults to active (`true`). (Task 1 code)
- **`description` empty string** — stored as `null`, not `''`. (Task 1 code)
- **Deleting a deduction type used by a template** — DB cascades template details; deletion succeeds. (Task 2 code comment; no failure path)

## File Structure

- Create `app/Services/EarningTypeService.php` — earning type CRUD + Inertia render.
- Create `app/Services/DeductionTypeService.php` — deduction type CRUD + Inertia render.
- Create `app/Http/Controllers/EarningTypeController.php` — thin delegation.
- Create `app/Http/Controllers/DeductionTypeController.php` — thin delegation.
- Modify `routes/web.php` — imports + 8 routes in `$adminRoutes`.
- Create `resources/js/Composables/lookupTypes/useLookupTypesIndex.js` — shared list/form state.
- Create `resources/js/Pages/EarningTypes/Index.vue` — earning types screen.
- Create `resources/js/Pages/DeductionTypes/Index.vue` — deduction types screen.
- Modify `resources/js/Components/NavLinks/AdminNavLinks.vue` — two Management entries + transition height.
- Modify `database/seeders/RouteSeeder.php` — access-control entries.
- Create `tests/Feature/EarningTypeManagementTest.php`, `tests/Feature/DeductionTypeManagementTest.php`.

---

### Task 1: Earning Types backend (service, controller, routes, tests)

**Files:**
- Create: `app/Services/EarningTypeService.php`
- Create: `app/Http/Controllers/EarningTypeController.php`
- Modify: `routes/web.php` (imports near top; routes inside `$adminRoutes` after the tables routes at ~line 391)
- Test: `tests/Feature/EarningTypeManagementTest.php`

**Interfaces:**
- Consumes: `App\Models\EarningType`, `App\Models\User` (`ROLE_ADMIN = 0`).
- Produces: routes `admin.earning-types.index|store|update|destroy`; service methods `index(): \Inertia\Response`, `store(Request): RedirectResponse`, `update(Request, EarningType): RedirectResponse`, `destroy(EarningType): RedirectResponse`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\EarningType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EarningTypeManagementTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_index_lists_earning_types(): void
    {
        EarningType::create(['name' => 'Overtime Pay', 'status' => true]);

        $this->actingAs($this->admin())
            ->get('/admin/earning-types')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('EarningTypes/Index')
                ->has('earning_types'));
    }

    public function test_earning_type_can_be_created(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/earning-types', [
                'name' => 'Holiday Pay',
                'description' => 'Holiday premium',
                'status' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('earning_types', [
            'name' => 'Holiday Pay',
            'description' => 'Holiday premium',
        ]);
    }

    public function test_earning_type_requires_a_name(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/earning-types', ['name' => '', 'status' => 1])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseMissing('earning_types', ['name' => '']);
    }

    public function test_earning_type_can_be_updated(): void
    {
        $type = EarningType::create(['name' => 'Old', 'status' => true]);

        $this->actingAs($this->admin())
            ->put("/admin/earning-types/{$type->id}", [
                'name' => 'New',
                'description' => null,
                'status' => 0,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('earning_types', [
            'id' => $type->id,
            'name' => 'New',
            'status' => 0,
        ]);
    }

    public function test_earning_type_can_be_deleted(): void
    {
        $type = EarningType::create(['name' => 'Temp', 'status' => true]);

        $this->actingAs($this->admin())
            ->delete("/admin/earning-types/{$type->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('earning_types', ['id' => $type->id]);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php vendor/bin/phpunit tests/Feature/EarningTypeManagementTest.php`
Expected: FAIL — `404` / route not defined (component assertion fails).

- [ ] **Step 3: Create `app/Services/EarningTypeService.php`**

```php
<?php

namespace App\Services;

use App\Models\EarningType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class EarningTypeService
{
    public function index()
    {
        return Inertia::render('EarningTypes/Index', [
            'earning_types' => EarningType::orderBy('name')
                ->get(['id', 'name', 'description', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        EarningType::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? true,
        ]);

        return back()->with('success', 'Earning type created successfully.');
    }

    public function update(Request $request, EarningType $earningType)
    {
        $validated = $this->validateRequest($request);

        $earningType->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? true,
        ]);

        return back()->with('success', 'Earning type updated successfully.');
    }

    public function destroy(EarningType $earningType)
    {
        try {
            $earningType->delete();

            return back()->with('success', 'Earning type deleted successfully.');
        } catch (\Throwable $e) {
            Log::error('EarningTypeService@destroy failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to delete earning type.');
        }
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);
    }
}
```

- [ ] **Step 4: Create `app/Http/Controllers/EarningTypeController.php`**

Mirror `app/Http/Controllers/DeductionTemplateController.php`: constructor injects `EarningTypeService`; `index/store/update/destroy` each wrap the service call in `try { ... } catch (\Throwable $e) { Log::error('EarningTypeController@<method> failed: ' . $e->getMessage()); return back()->with('error', '...'); }`. `update` signature is `update(Request $request, EarningType $earningType)`, `destroy` is `destroy(EarningType $earningType)`.

- [ ] **Step 5: Register routes**

Add near the other imports at the top of `routes/web.php`:

```php
use App\Http\Controllers\EarningTypeController;
```

Inside the `$adminRoutes` closure, directly after the `tables.destroy` route (around line 391), add:

```php
Route::get('/earning-types', [EarningTypeController::class, 'index'])->name('earning-types.index');
Route::post('/earning-types', [EarningTypeController::class, 'store'])->name('earning-types.store');
Route::put('/earning-types/{earningType}', [EarningTypeController::class, 'update'])->name('earning-types.update');
Route::delete('/earning-types/{earningType}', [EarningTypeController::class, 'destroy'])->name('earning-types.destroy');
```

- [ ] **Step 6: Run the tests to verify they pass**

Run: `php vendor/bin/phpunit tests/Feature/EarningTypeManagementTest.php`
Expected: PASS (5 tests). If `assertInertia` errors because the Vue page file does not exist yet, that is fine — Inertia only checks the component name string in tests; re-run after Task 3 if needed.

- [ ] **Step 7: Verify routes are registered**

Run: `php artisan route:list --path=admin/earning-types`
Expected: 4 routes `admin.earning-types.index|store|update|destroy`.

---

### Task 2: Deduction Types backend (service, controller, routes, tests)

**Files:**
- Create: `app/Services/DeductionTypeService.php`
- Create: `app/Http/Controllers/DeductionTypeController.php`
- Modify: `routes/web.php` (import + routes)
- Test: `tests/Feature/DeductionTypeManagementTest.php`

**Interfaces:**
- Consumes: `App\Models\DeductionType`.
- Produces: routes `admin.deduction-types.index|store|update|destroy`; service methods identical in shape to `EarningTypeService` but rendering `DeductionTypes/Index` with prop `deduction_types`.

- [ ] **Step 1: Write the failing test**

Clone `tests/Feature/EarningTypeManagementTest.php` to `tests/Feature/DeductionTypeManagementTest.php`, replacing: class name `DeductionTypeManagementTest`, model import `App\Models\DeductionType`, URLs `/admin/deduction-types`, table `deduction_types`, component `DeductionTypes/Index`, prop `deduction_types`, and test method names with `deduction_type_*`.

- [ ] **Step 2: Run the test to verify it fails**

Run: `php vendor/bin/phpunit tests/Feature/DeductionTypeManagementTest.php`
Expected: FAIL — route not defined.

- [ ] **Step 3: Create `app/Services/DeductionTypeService.php`**

Copy `EarningTypeService` and substitute `EarningType` → `DeductionType`, `earning_types` → `deduction_types`, `EarningTypes/Index` → `DeductionTypes/Index`, and messages `earning type` → `deduction type`. Keep `orderBy('name')` and the same validation. Add a short comment in `destroy` noting that `employee_deduction_template_details.deduction_type_id` cascades on delete.

- [ ] **Step 4: Create `app/Http/Controllers/DeductionTypeController.php`**

Mirror `EarningTypeController`, injecting `DeductionTypeService` and binding `DeductionType $deductionType`.

- [ ] **Step 5: Register routes**

Add `use App\Http\Controllers\DeductionTypeController;` near the top imports, and after the earning-types routes:

```php
Route::get('/deduction-types', [DeductionTypeController::class, 'index'])->name('deduction-types.index');
Route::post('/deduction-types', [DeductionTypeController::class, 'store'])->name('deduction-types.store');
Route::put('/deduction-types/{deductionType}', [DeductionTypeController::class, 'update'])->name('deduction-types.update');
Route::delete('/deduction-types/{deductionType}', [DeductionTypeController::class, 'destroy'])->name('deduction-types.destroy');
```

- [ ] **Step 6: Run the tests to verify they pass**

Run: `php vendor/bin/phpunit tests/Feature/DeductionTypeManagementTest.php`
Expected: PASS (5 tests).

- [ ] **Step 7: Verify routes**

Run: `php artisan route:list --path=admin/deduction-types`
Expected: 4 routes `admin.deduction-types.*`.

---

### Task 3: Shared composable + Earning Types page

**Files:**
- Create: `resources/js/Composables/lookupTypes/useLookupTypesIndex.js`
- Create: `resources/js/Pages/EarningTypes/Index.vue`

**Interfaces:**
- Produces: `useLookupTypesIndex({ pageProp, basePath })` returning `{ search, showAddModal, showEditModal, newForm, editForm, filteredItems, createItem, closeAddModal, editItem, closeEditModal, saveItem, updateItem, deleteItem }`.

- [ ] **Step 1: Create the shared composable**

```js
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

export function useLookupTypesIndex({ pageProp, basePath }) {
  const page = usePage()
  const items = computed(() => page.props[pageProp] ?? [])

  const search = ref('')
  const showAddModal = ref(false)
  const showEditModal = ref(false)

  const emptyForm = () => ({ name: '', description: '', status: 1 })

  const newForm = ref(emptyForm())
  const editForm = ref({ id: null, ...emptyForm() })

  const filteredItems = computed(() => {
    if (!search.value) return items.value
    const keyword = search.value.toLowerCase()
    return items.value.filter((item) =>
      (item.name || '').toLowerCase().includes(keyword) ||
      (item.description || '').toLowerCase().includes(keyword)
    )
  })

  const createItem = () => {
    newForm.value = emptyForm()
    showAddModal.value = true
  }

  const closeAddModal = () => {
    showAddModal.value = false
    newForm.value = emptyForm()
  }

  const editItem = (item) => {
    editForm.value = {
      id: item.id,
      name: item.name ?? '',
      description: item.description ?? '',
      status: item.status ? 1 : 0,
    }
    showEditModal.value = true
  }

  const closeEditModal = () => {
    showEditModal.value = false
    editForm.value = { id: null, ...emptyForm() }
  }

  const saveItem = () => {
    router.post(basePath, newForm.value, {
      onSuccess: () => closeAddModal(),
    })
  }

  const updateItem = () => {
    router.put(`${basePath}/${editForm.value.id}`, editForm.value, {
      onSuccess: () => closeEditModal(),
    })
  }

  const deleteItem = (item) => {
    if (confirm(`Delete ${item.name}?`)) {
      router.delete(`${basePath}/${item.id}`)
    }
  }

  return {
    search,
    showAddModal,
    showEditModal,
    newForm,
    editForm,
    filteredItems,
    createItem,
    closeAddModal,
    editItem,
    closeEditModal,
    saveItem,
    updateItem,
    deleteItem,
  }
}
```

- [ ] **Step 2: Create `resources/js/Pages/EarningTypes/Index.vue`**

Clone the layout of `resources/js/Pages/Tables/Index.vue` (same outer `AuthenticatedLayout` → `container mx-auto py-6` → `p-4 bg-gray-50 min-h-screen` wrapper, header row, white rounded table, sticky `bg-gray-100` thead, and modal markup/classes). Differences:

- Header: `h1` "Earning Types", subtitle "Manage payroll earning types", green `Add Earning Type` button with `fa fa-plus` calling `createItem`.
- Search input bound to `search`, placeholder "Search earning type...".
- Table columns: `ID | Name | Description | Status | Actions`.
- Row: `{{ item.id }}`, `{{ item.name }}`, `{{ item.description || '—' }}`; status badge `Active` (`bg-green-100 text-green-700`) when `item.status` else `Inactive` (`bg-gray-100 text-gray-600`); Edit (blue) + Delete (red) buttons calling `editItem(item)` / `deleteItem(item)`.
- Empty row: colspan 5, "No earning types found".
- Add modal title "Add New Earning Type"; Edit modal title "Edit Earning Type". Both forms have: Name (`text`, required), Description (`textarea`, rows 3, optional), Status (`select`: `Active` value `1`, `Inactive` value `0`). Add binds `newForm` + `saveItem`; Edit binds `editForm` + `updateItem`. Use the green focus ring on Add inputs and blue focus ring on Edit inputs, matching the Tables page.
- `<script setup>`:

```js
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useLookupTypesIndex } from '@/Composables/lookupTypes/useLookupTypesIndex'

const {
  search, showAddModal, showEditModal, newForm, editForm, filteredItems,
  createItem, closeAddModal, editItem, closeEditModal, saveItem, updateItem, deleteItem,
} = useLookupTypesIndex({ pageProp: 'earning_types', basePath: '/admin/earning-types' })
```

- [ ] **Step 3: Build assets to verify the page compiles**

Run: `npm run build`
Expected: build succeeds with no Vue compile errors.

---

### Task 4: Deduction Types page

**Files:**
- Create: `resources/js/Pages/DeductionTypes/Index.vue`

**Interfaces:**
- Consumes: `useLookupTypesIndex({ pageProp: 'deduction_types', basePath: '/admin/deduction-types' })`.

- [ ] **Step 1: Create the page**

Copy `resources/js/Pages/EarningTypes/Index.vue` to `resources/js/Pages/DeductionTypes/Index.vue` and replace all earning wording with deduction wording: title "Deduction Types", subtitle "Manage payroll deduction types", button "Add Deduction Type", search placeholder "Search deduction type...", empty row "No deduction types found", modal titles "Add New Deduction Type" / "Edit Deduction Type", and the composable call with `pageProp: 'deduction_types'`, `basePath: '/admin/deduction-types'`.

- [ ] **Step 2: Build assets**

Run: `npm run build`
Expected: build succeeds.

---

### Task 5: Sidebar menu entries + access seeding

**Files:**
- Modify: `resources/js/Components/NavLinks/AdminNavLinks.vue:112-128`
- Modify: `database/seeders/RouteSeeder.php` (after the `admin.tables.*` entries at ~line 768)

**Interfaces:**
- Consumes: routes `admin.earning-types.index`, `admin.deduction-types.index` from Tasks 1–2.

- [ ] **Step 1: Add sidebar entries**

Inside the Management `<ul>` (after the `admin.tables` `<li>` at line 123), add:

```html
<li><Link :href="route('admin.earning-types.index')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"><i class="fa fa-plus-circle"></i><span>Earning Types</span></Link></li>
<li><Link :href="route('admin.deduction-types.index')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"><i class="fa fa-minus-circle"></i><span>Deduction Types</span></Link></li>
```

- [ ] **Step 2: Raise the submenu transition height**

In the Management `<transition>` block, change both `max-h-60` occurrences (enter-to and leave-from) to `max-h-72` so the taller list animates fully.

- [ ] **Step 3: Add access-control entries**

After the `admin.tables.destroy` entry in `database/seeders/RouteSeeder.php`, add:

```php
['route_name' => 'Admin Earning Types', 'route' => 'admin.earning-types.index'],
['route_name' => 'Admin Earning Types Store', 'route' => 'admin.earning-types.store'],
['route_name' => 'Admin Earning Types Update', 'route' => 'admin.earning-types.update'],
['route_name' => 'Admin Earning Types Destroy', 'route' => 'admin.earning-types.destroy'],
['route_name' => 'Admin Deduction Types', 'route' => 'admin.deduction-types.index'],
['route_name' => 'Admin Deduction Types Store', 'route' => 'admin.deduction-types.store'],
['route_name' => 'Admin Deduction Types Update', 'route' => 'admin.deduction-types.update'],
['route_name' => 'Admin Deduction Types Destroy', 'route' => 'admin.deduction-types.destroy'],
```

- [ ] **Step 4: Build assets**

Run: `npm run build`
Expected: build succeeds.

---

### Task 6: Full verification

**Files:** none (verification only).

- [ ] **Step 1: Run both feature test files**

Run: `php vendor/bin/phpunit tests/Feature/EarningTypeManagementTest.php tests/Feature/DeductionTypeManagementTest.php`
Expected: PASS (10 tests).

- [ ] **Step 2: Confirm all eight routes resolve**

Run: `php artisan route:list --path=admin/earning-types` and `php artisan route:list --path=admin/deduction-types`
Expected: 4 routes each.

- [ ] **Step 3: Confirm production build is clean**

Run: `npm run build`
Expected: exit code 0.

- [ ] **Step 4: Report results**

Summarize the files changed, test output, and note that changes are left uncommitted.
