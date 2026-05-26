<template>
  <AuthenticatedLayout>
    <!-- ================= FULL SCREEN LOADER ================= -->
    <div
      v-if="isLoading"
      class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-[9999]"
    >
      <div class="bg-white p-8 rounded-xl shadow-xl flex flex-col items-center">
        <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
        <p class="text-lg font-semibold text-gray-700">Posting sales. Please wait...</p>
      </div>
    </div>

    <div class="p-4 bg-gray-50 min-h-screen">

      <!-- ── Header ─────────────────────────────────────────── -->
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-gray-800">
          <span class="fa fa-cash-register mr-2 text-green-600"></span>
          Post Sales
        </h1>
      </div>

      <!-- ── Current Shift Banner ────────────────────────────── -->
      <div
        v-if="currentShift"
        class="mb-4 flex items-center gap-3 bg-blue-50 border border-blue-200 rounded-lg px-4 py-3"
      >
        <span class="fa fa-clock text-blue-500 text-lg"></span>
        <div class="flex-1">
          <p class="text-sm font-semibold text-blue-800">
            Current Shift: {{ currentShift.name }}
          </p>
          <p class="text-xs text-blue-600">
            {{ formatTime(shiftStart) }} – {{ formatTime(shiftEnd) }}
          </p>
        </div>
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-200 text-blue-800">
          <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
          Active
        </span>
      </div>

      <div
        v-else
        class="mb-4 flex items-center gap-3 bg-gray-100 border border-gray-200 rounded-lg px-4 py-3"
      >
        <span class="fa fa-clock text-gray-400 text-lg"></span>
        <p class="text-sm text-gray-500">No active shift at this time.</p>
      </div>

      <!-- ── Sales Summary Cards ────────────────────────────── -->
      <!-- FIX #6: adaptive grid instead of hardcoded sm:grid-cols-3 -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">

        <!-- FIX #3: paymentSummary now comes from composable -->
        <div
          v-for="pm in paymentSummary"
          :key="pm.id"
          class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm flex items-center gap-4"
        >
          <div
            class="w-10 h-10 rounded-full flex items-center justify-center shrink-0"
            :class="pmCardColor(pm.id).bg"
          >
            <span class="fa" :class="pmCardColor(pm.id).icon"></span>
          </div>
          <div>
            <p class="text-xs text-gray-500 font-medium">{{ pm.name }}</p>
            <p class="text-xl font-bold" :class="pmCardColor(pm.id).text">
              ₱{{ Number(pm.total).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
            </p>
          </div>
        </div>

        <!-- Cashier Expenses (always shown) -->
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm flex items-center gap-4">
          <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
            <span class="fa fa-receipt text-red-500"></span>
          </div>
          <div>
            <p class="text-xs text-gray-500 font-medium">Cashier Expenses</p>
            <p class="text-xl font-bold text-red-600">
              ₱{{ Number(cashierExpenses).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
            </p>
          </div>
        </div>

      </div>

      <!-- ── Filters ─────────────────────────────────────────── -->
      <div class="mb-4 flex flex-wrap gap-4 items-center">
        <div class="flex items-center gap-2">
          <label class="text-sm font-medium text-gray-700">Date:</label>
          <input
            type="date"
            v-model="selectedDate"
            @change="filterByDate"
            class="border rounded px-2 py-1 text-sm"
          />
        </div>

        <input
          v-model="search"
          type="text"
          placeholder="Search cashier or shift…"
          class="border rounded px-3 py-1 text-sm w-64"
        />

        <button
          @click="openNewSaleModal"
          class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-sm"
        >
          <span class="fa fa-plus mr-1"></span>
          Post Sales
        </button>
      </div>

      <!-- ── Table ───────────────────────────────────────────── -->
      <div class="bg-white rounded shadow overflow-hidden">
        <div class="max-h-[60vh] overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-100 sticky top-0 z-10">
              <tr class="text-left">
                <th class="px-4 py-2">#</th>
                <th class="px-4 py-2">Cash Sales</th>
                <th class="px-4 py-2">GCash Sales</th>
                <th class="px-4 py-2">Expenses</th>
                <th class="px-4 py-2">Expected Cash</th>
                <th class="px-4 py-2">Cash On Hand</th>
                <th class="px-4 py-2">Over / Short</th>
                <th class="px-4 py-2">Posted By</th>
                <th class="px-4 py-2 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <!-- FIX #1: colspan corrected from 8 to 9 -->
              <tr v-if="filteredSales.length === 0">
                <td colspan="9" class="text-center py-6 text-gray-400">
                  No cash register records found
                </td>
              </tr>

              <tr
                v-for="(sale, index) in filteredSales"
                :key="sale.id"
                class="border-t hover:bg-gray-50"
              >
                <td class="px-4 py-2 font-medium">{{ index + 1 }}</td>
                <td class="px-4 py-2 text-green-700">
                  ₱{{ Number(sale.cash_sales).toFixed(2) }}
                </td>
                <td class="px-4 py-2 text-blue-700">
                  ₱{{ Number(sale.gcash_sales).toFixed(2) }}
                </td>
                <td class="px-4 py-2 text-red-600">
                  ₱{{ Number(sale.total_expenses).toFixed(2) }}
                </td>
                <td class="px-4 py-2 font-semibold text-gray-800">
                  ₱{{ Number(sale.expected_cash_on_hand).toFixed(2) }}
                </td>
                <td class="px-4 py-2">
                  ₱{{ Number(sale.cash_on_hand).toFixed(2) }}
                </td>
                <td class="px-4 py-2 font-bold" :class="overShortClass(sale)">
                  {{ overShortLabel(sale) }}
                  <span class="text-xs font-normal ml-1">
                    ₱{{ Math.abs(Number(sale.cash_on_hand) - Number(sale.expected_cash_on_hand)).toFixed(2) }}
                  </span>
                </td>
                <!-- FIX #2: Posted By td was missing -->
                <td class="px-4 py-2 text-gray-700">
                  {{ sale.cashier?.name ?? '—' }}
                </td>
                <td class="px-4 py-2 text-right">
                  <div class="flex justify-end gap-1">
                    <button
                      @click="openViewModal(sale)"
                      class="px-2 py-1 text-xs bg-gray-200 rounded hover:bg-gray-300"
                    >
                      <span class="fa fa-eye mr-1"></span>View
                    </button>
                    <button
                      @click="openEditModal(sale)"
                      class="px-2 py-1 text-xs bg-yellow-400 text-white rounded hover:bg-yellow-500"
                    >
                      <span class="fa fa-pencil mr-1"></span>Edit
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ======================== POST NET SALES MODAL ======================== -->
      <div
        v-if="showNewModal"
        class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center"
      >
        <div class="bg-white rounded shadow-lg w-[640px] p-5 max-h-[90vh] overflow-y-auto">
          <h2 class="text-lg font-bold mb-4 text-gray-800">
            <span class="fa fa-cash-register mr-2 text-green-600"></span>
            Post Net Sales (End of Shift)
          </h2>

          <div class="flex flex-col gap-3">

            <!-- ── Shift + Sales Reference Panel ─────────────────────── -->
            <div class="rounded-lg border border-blue-100 bg-blue-50 p-3">

              <!-- Shift row -->
              <div class="flex items-center justify-between mb-2 pb-2 border-b border-blue-100">
                <div class="flex items-center gap-2">
                  <span class="fa fa-clock text-blue-400 text-xs"></span>
                  <span class="text-xs font-semibold text-blue-800">
                    {{ currentShift?.name ?? 'No active shift' }}
                  </span>
                  <span class="text-xs text-blue-500">
                    {{ formatTime(shiftStart) }} – {{ formatTime(shiftEnd) }}
                  </span>
                </div>
                <span
                  v-if="currentShift"
                  class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-700"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                  Active
                </span>
              </div>

              <!-- FIX #5: adaptive grid for payment mini-cards -->
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">

                <div
                  v-for="pm in paymentSummary"
                  :key="pm.id"
                  class="bg-white rounded border border-blue-100 px-3 py-2"
                >
                  <p class="text-xs text-gray-400 mb-0.5">
                    <span
                      class="fa mr-1"
                      :class="pmCardColor(pm.id).icon"
                      :style="{ color: pmCardColor(pm.id).rawColor }"
                    ></span>
                    {{ pm.name }}
                  </p>
                  <p class="text-sm font-bold" :class="pmCardColor(pm.id).text">
                    ₱{{ Number(pm.total).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                  </p>
                </div>

                <!-- Expenses mini-card -->
                <div class="bg-white rounded border border-blue-100 px-3 py-2">
                  <p class="text-xs text-gray-400 mb-0.5">
                    <span class="fa fa-receipt mr-1 text-red-400"></span>Expenses
                  </p>
                  <p class="text-sm font-bold text-red-600">
                    ₱{{ Number(cashierExpenses).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                  </p>
                </div>

              </div>

              <!-- FIX #7: updated label from "cash + gcash" to "all payments" -->
              <div class="flex items-center justify-between mt-2 pt-2 border-t border-blue-100">
                <span class="text-xs text-blue-600 font-medium">
                  Expected cash on hand
                  <span class="text-blue-400 font-normal">(all payments – expenses)</span>
                </span>
                <span class="text-sm font-bold text-blue-800">
                  ₱{{ expectedCashOnHand.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                </span>
              </div>
            </div>
            <!-- ── End Reference Panel ────────────────────────────────── -->

            <!-- Date & Shift -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="text-xs font-medium text-gray-600 mb-1 block">Date</label>
                <input
                  type="date"
                  v-model="newSale.date"
                  class="border rounded px-2 py-1 text-sm w-full"
                />
              </div>
              <div>
                <label class="text-xs font-medium text-gray-600 mb-1 block">Shift</label>
                <select v-model.number="newSale.shift_id" class="border rounded px-2 py-1 text-sm w-full">
                  <option :value="null" disabled>Select shift</option>
                  <option v-for="shift in shifts" :key="shift.id" :value="shift.id">
                    {{ shift.name }}
                    ({{ new Date(`1970-01-01T${shift.start_time}`).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', hour12: true }) }} –
                    {{ new Date(`1970-01-01T${shift.end_time}`).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', hour12: true }) }})
                  </option>
                </select>
              </div>
            </div>

            <!-- Cash On Hand Denomination -->
            <div class="border rounded p-3 bg-gray-50">
              <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-semibold text-gray-700">
                  <span class="fa fa-coins mr-1"></span>
                  Cash On Hand (Denomination Count)
                </p>
                <button
                  type="button"
                  @click="addDenomRow"
                  class="px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600"
                >
                  <span class="fa fa-plus mr-1"></span> Add Row
                </button>
              </div>

              <div v-if="denomRows.length === 0" class="text-xs text-gray-400">
                No rows yet. Click <b>Add Row</b>.
              </div>

              <div v-for="(row, idx) in denomRows" :key="idx" class="grid grid-cols-12 gap-2 mb-2">
                <div class="col-span-6">
                  <select v-model.number="row.denom" class="border rounded px-2 py-1 text-sm w-full">
                    <option :value="null" disabled>Select denomination</option>
                    <option v-for="d in DENOMS" :key="d" :value="d">₱{{ d }}</option>
                  </select>
                </div>
                <div class="col-span-4">
                  <input
                    type="number"
                    min="0"
                    v-model.number="row.qty"
                    placeholder="Qty"
                    class="border rounded px-2 py-1 text-sm w-full"
                  />
                </div>
                <div class="col-span-2 flex justify-end">
                  <button
                    type="button"
                    @click="removeDenomRow(idx)"
                    class="px-2 py-1 text-xs bg-red-100 text-red-600 rounded hover:bg-red-200"
                  >
                    <span class="fa fa-trash"></span>
                  </button>
                </div>
                <div class="col-span-12 text-xs text-gray-500 -mt-1">
                  Subtotal: <b>₱{{ (Number(row.denom || 0) * Number(row.qty || 0)).toFixed(2) }}</b>
                </div>
              </div>

              <!-- Total + comparison hint -->
              <div class="pt-2 border-t mt-2 space-y-1">
                <div class="flex justify-between">
                  <span class="text-sm font-medium">Cash On Hand Total:</span>
                  <span class="text-sm font-bold text-blue-700">
                    ₱{{ cashOnHandTotal.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                  </span>
                </div>
                <div class="flex justify-between text-xs">
                  <span class="text-gray-400">vs. expected cash on hand</span>
                  <span
                    :class="{
                      'text-green-600 font-semibold': cashOnHandTotal >= expectedCashOnHand,
                      'text-red-500 font-semibold':   cashOnHandTotal <  expectedCashOnHand,
                    }"
                  >
                    {{ cashOnHandTotal >= expectedCashOnHand ? '+' : '–' }}₱{{
                      Math.abs(cashOnHandTotal - expectedCashOnHand)
                        .toLocaleString('en-PH', { minimumFractionDigits: 2 })
                    }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Over / Short -->
            <div
              class="flex justify-between items-center rounded px-3 py-2 border"
              :class="overShortNewClass"
            >
              <span class="text-sm font-semibold">{{ overShortNewLabel }}:</span>
              <span class="text-lg font-bold">
                ₱{{ Math.abs(overShortNewValue).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
              </span>
            </div>

            <!-- Notes -->
            <div>
              <label class="text-xs font-medium text-gray-600 mb-1 block">Notes (optional)</label>
              <input
                type="text"
                v-model="newSale.notes"
                placeholder="e.g. Machine breakdown during shift"
                class="border rounded px-2 py-1 text-sm w-full"
              />
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-2 mt-2">
              <button @click="closeNewModal" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 text-sm">
                <span class="fa fa-times mr-1"></span> Cancel
              </button>
              <button @click="postNetSales" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                <span class="fa fa-save mr-1"></span> Post Net Sales
              </button>
            </div>

          </div>
        </div>
      </div>

      <!-- ======================== VIEW MODAL ======================== -->
      <div
        v-if="showViewModal && selectedSale"
        class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center"
      >
        <div class="bg-white rounded shadow-lg w-[700px] p-6 max-h-[90vh] overflow-y-auto">
          <h2 class="text-lg font-bold mb-1 text-gray-800">
            <span class="fa fa-cash-register mr-2 text-green-600"></span>
            Cash Register Detail
          </h2>
          <p class="text-xs text-gray-400 mb-4">
            {{ selectedSale.created_at }} · {{ selectedSale.shift?.name }} · Posted by {{ selectedSale.cashier?.name }}
          </p>

          <!-- ── Sales Breakdown ─────────────────────────────────── -->
          <div class="mb-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Sales Summary</h3>
            <div class="bg-gray-50 border rounded p-3 text-sm space-y-2">

              <!-- Cash Sales -->
              <div class="flex justify-between">
                <span class="text-gray-500 flex items-center gap-1">
                  <span class="fa fa-money-bill-wave text-green-400 text-xs"></span>
                  Cash Sales
                </span>
                <span class="font-semibold text-green-700">
                  ₱{{ Number(selectedSale.cash_sales ?? 0).toFixed(2) }}
                </span>
              </div>

              <!-- GCash Sales -->
              <div class="flex justify-between">
                <span class="text-gray-500 flex items-center gap-1">
                  <span class="fa fa-mobile-alt text-blue-400 text-xs"></span>
                  GCash Sales
                </span>
                <span class="font-semibold text-blue-700">
                  ₱{{ Number(selectedSale.gcash_sales ?? 0).toFixed(2) }}
                </span>
              </div>

              <!-- FIX #8: only show Other Sales if non-zero -->
              <div v-if="Number(selectedSale.other_sales) > 0" class="flex justify-between">
                <span class="text-gray-500 flex items-center gap-1">
                  <span class="fa fa-credit-card text-purple-400 text-xs"></span>
                  Other Sales
                </span>
                <span class="font-semibold text-purple-700">
                  ₱{{ Number(selectedSale.other_sales ?? 0).toFixed(2) }}
                </span>
              </div>

              <!-- Expenses -->
              <div class="flex justify-between">
                <span class="text-gray-500 flex items-center gap-1">
                  <span class="fa fa-receipt text-red-400 text-xs"></span>
                  Expenses
                </span>
                <span class="font-semibold text-red-600">
                  – ₱{{ Number(selectedSale.total_expenses ?? 0).toFixed(2) }}
                </span>
              </div>

              <!-- Divider -->
              <div class="border-t pt-2 space-y-2">

                <!-- FIX #7: updated label -->
                <div class="flex justify-between">
                  <span class="text-gray-500">
                    Expected Cash On Hand
                    <span class="text-gray-400 text-xs">(all payments – expenses)</span>
                  </span>
                  <span class="font-semibold">
                    ₱{{ Number(selectedSale.expected_cash_on_hand ?? 0).toFixed(2) }}
                  </span>
                </div>

                <!-- Actual Cash On Hand (Counted) -->
                <div class="flex justify-between">
                  <span class="text-gray-500">Cash On Hand (Counted)</span>
                  <span class="font-semibold">
                    ₱{{ Number(selectedSale.cash_on_hand ?? 0).toFixed(2) }}
                  </span>
                </div>

              </div>

              <!-- Over / Short -->
              <div class="flex justify-between border-t pt-2 mt-1">
                <span class="font-semibold">{{ overShortLabel(selectedSale) }}</span>
                <span class="font-bold" :class="overShortClass(selectedSale)">
                  ₱{{ Math.abs(
                        Number(selectedSale.cash_on_hand ?? 0) - Number(selectedSale.expected_cash_on_hand ?? 0)
                      ).toFixed(2) }}
                </span>
              </div>

            </div>
          </div>

          <!-- ── Denomination Breakdown ──────────────────────────── -->
          <div v-if="selectedSale.denomination?.length" class="mb-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">
              <span class="fa fa-coins mr-1 text-yellow-500"></span>
              Cash On Hand Denomination
            </h3>
            <table class="w-full text-xs border rounded">
              <thead class="bg-gray-100">
                <tr>
                  <th class="px-3 py-2 text-left">Denomination</th>
                  <th class="px-3 py-2 text-right">Qty</th>
                  <th class="px-3 py-2 text-right">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="d in selectedSale.denomination" :key="d.denom" class="border-t">
                  <td class="px-3 py-2 font-semibold">₱{{ d.denom }}</td>
                  <td class="px-3 py-2 text-right">{{ d.qty }}</td>
                  <td class="px-3 py-2 text-right font-semibold text-blue-700">
                    ₱{{ (Number(d.denom) * Number(d.qty)).toFixed(2) }}
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-gray-50 border-t">
                <tr>
                  <td colspan="2" class="px-3 py-2 font-bold">Total Cash On Hand</td>
                  <td class="px-3 py-2 text-right font-bold text-blue-700">
                    ₱{{ Number(selectedSale.cash_on_hand ?? 0).toFixed(2) }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- ── Notes ──────────────────────────────────────────── -->
          <div
            v-if="selectedSale.notes"
            class="mb-4 text-sm text-gray-600 bg-gray-50 border rounded px-3 py-2"
          >
            <span class="fa fa-sticky-note mr-2 text-gray-400"></span>
            {{ selectedSale.notes }}
          </div>

          <div class="flex justify-end">
            <button @click="closeViewModal" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 text-sm">
              <span class="fa fa-times mr-1"></span> Close
            </button>
          </div>
        </div>
      </div>

      <!-- ======================== EDIT MODAL ======================== -->
      <div
        v-if="showEditModal && editSale.id"
        class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center"
      >
        <div class="bg-white rounded shadow-lg w-[600px] p-5 max-h-[90vh] overflow-y-auto">
          <h2 class="text-lg font-bold mb-4 text-gray-800">
            <span class="fa fa-pencil mr-2 text-yellow-500"></span>
            Edit Cash Register Record
          </h2>

          <div class="flex flex-col gap-3">

            <!-- Shift -->
            <div>
              <label class="text-xs font-medium text-gray-600 mb-1 block">Shift</label>
              <select v-model.number="editSale.shift_id" class="border rounded px-2 py-1 text-sm w-full">
                <option :value="null" disabled>Select shift</option>
                <option v-for="shift in shifts" :key="shift.id" :value="shift.id">
                  {{ shift.name }} ({{ shift.start_time.slice(0, 5) }} – {{ shift.end_time.slice(0, 5) }})
                </option>
              </select>
            </div>

            <!-- FIX #4: removed dead "Net Sales" input — backend computes it -->

            <!-- Cash On Hand Denomination -->
            <div class="border rounded p-3 bg-gray-50">
              <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-semibold text-gray-700">
                  <span class="fa fa-coins mr-1"></span>
                  Cash On Hand (Denomination Count)
                </p>
                <button
                  type="button"
                  @click="addEditDenomRow"
                  class="px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600"
                >
                  <span class="fa fa-plus mr-1"></span> Add Row
                </button>
              </div>

              <div v-if="editDenomRows.length === 0" class="text-xs text-gray-400">
                No rows yet. Click <b>Add Row</b>.
              </div>

              <div v-for="(row, idx) in editDenomRows" :key="idx" class="grid grid-cols-12 gap-2 mb-2">
                <div class="col-span-6">
                  <select v-model.number="row.denom" class="border rounded px-2 py-1 text-sm w-full">
                    <option :value="null" disabled>Select denomination</option>
                    <option v-for="d in DENOMS" :key="d" :value="d">₱{{ d }}</option>
                  </select>
                </div>
                <div class="col-span-4">
                  <input
                    type="number"
                    min="0"
                    v-model.number="row.qty"
                    placeholder="Qty"
                    class="border rounded px-2 py-1 text-sm w-full"
                  />
                </div>
                <div class="col-span-2 flex justify-end">
                  <button
                    type="button"
                    @click="removeEditDenomRow(idx)"
                    class="px-2 py-1 text-xs bg-red-100 text-red-600 rounded hover:bg-red-200"
                  >
                    <span class="fa fa-trash"></span>
                  </button>
                </div>
                <div class="col-span-12 text-xs text-gray-500 -mt-1">
                  Subtotal: <b>₱{{ (Number(row.denom || 0) * Number(row.qty || 0)).toFixed(2) }}</b>
                </div>
              </div>

              <div class="flex justify-between pt-2 border-t mt-2">
                <span class="text-sm font-medium">Cash On Hand Total:</span>
                <span class="text-sm font-bold text-blue-700">
                  ₱{{ editCashOnHandTotal.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </span>
              </div>
            </div>

            <!-- Over / Short -->
            <div
              class="flex justify-between items-center rounded px-3 py-2 border"
              :class="editOverShortClass"
            >
              <span class="text-sm font-semibold">{{ editOverShortLabel }}:</span>
              <span class="text-lg font-bold">
                ₱{{ Math.abs(editOverShortValue).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
              </span>
            </div>

            <!-- Notes -->
            <div>
              <label class="text-xs font-medium text-gray-600 mb-1 block">Notes (optional)</label>
              <input
                type="text"
                v-model="editSale.notes"
                placeholder="e.g. Correction — mistyped amount"
                class="border rounded px-2 py-1 text-sm w-full"
              />
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-2 mt-2">
              <button @click="closeEditModal" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 text-sm">
                <span class="fa fa-times mr-1"></span> Cancel
              </button>
              <button @click="updateNetSales" class="px-3 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600 text-sm">
                <span class="fa fa-save mr-1"></span> Save Changes
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useCashRegisterIndex } from '@/Composables/cash_register/useCashRegisterIndex'

const props = defineProps({
  shifts:         { type: Array,           default: () => [] },
  cashRegisters:  { type: Array,           default: () => [] },
  totalCashSales: { type: [Number, String], default: 0 },
  totalGcashSales:{ type: [Number, String], default: 0 },
  cashierExpenses:{ type: [Number, String], default: 0 },
  currentShift:   { type: Object,          default: null },
  shiftStart:     { type: String,          default: '' },
  shiftEnd:       { type: String,          default: '' },
  paymentSummary: { type: Array,           default: () => [] },
})

function formatTime(datetimeStr) {
  if (!datetimeStr) return '—'
  const timePart = datetimeStr.includes(' ') ? datetimeStr.split(' ')[1] : datetimeStr
  return new Date(`1970-01-01T${timePart}`).toLocaleTimeString('en-PH', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
  })
}

// ── Payment method color/icon palette ─────────────────────────────────────────
// Add more entries here to match your payment_methods table IDs
const PM_COLORS = {
  1:  { bg: 'bg-green-100',  icon: 'fa-money-bill-wave text-green-600',  text: 'text-green-700',  rawColor: '#16a34a' }, // Cash
  2:  { bg: 'bg-blue-100',   icon: 'fa-mobile-alt text-blue-600',        text: 'text-blue-700',   rawColor: '#2563eb' }, // GCash
  3:  { bg: 'bg-purple-100', icon: 'fa-credit-card text-purple-600',     text: 'text-purple-700', rawColor: '#9333ea' }, // Card
  13: { bg: 'bg-yellow-100', icon: 'fa-bookmark text-yellow-600',        text: 'text-yellow-700', rawColor: '#ca8a04' }, // Reservation
}
const PM_DEFAULT = { bg: 'bg-gray-100', icon: 'fa-coins text-gray-500', text: 'text-gray-700', rawColor: '#6b7280' }

function pmCardColor(id) {
  return PM_COLORS[id] ?? PM_DEFAULT
}

const {
  shifts,
  DENOMS,
  showNewModal,
  showViewModal,
  selectedSale,
  selectedDate,
  search,
  filteredSales,
  newSale,
  deductionRows,
  denomRows,
  totalDeductions,
  computedNetSales,
  cashOnHandTotal,
  overShortNewValue,
  overShortNewLabel,
  overShortNewClass,
  overShortLabel,
  overShortClass,
  addDeductionRow,
  removeDeductionRow,
  addDenomRow,
  removeDenomRow,
  openNewSaleModal,
  closeNewModal,
  openViewModal,
  closeViewModal,
  postNetSales,
  filterByDate,
  isLoading,
  showEditModal,
  editSale,
  editDenomRows,
  editCashOnHandTotal,
  editOverShortValue,
  editOverShortLabel,
  editOverShortClass,
  openEditModal,
  closeEditModal,
  addEditDenomRow,
  removeEditDenomRow,
  updateNetSales,
  totalCashSales,
  totalGcashSales,
  cashierExpenses,
  currentShift,
  shiftStart,
  shiftEnd,
  expectedCashOnHand,
  paymentSummary,       // FIX #3: now destructured from composable
} = useCashRegisterIndex(props)
</script>