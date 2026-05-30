<template>
  <AuthenticatedLayout>

    <!-- Loading Overlay -->
    <div
      v-if="isLoading"
      class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50"
    >
      <div class="bg-white p-8 rounded-xl shadow-xl flex flex-col items-center">
        <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
        <p class="text-lg font-semibold text-gray-700">
          Generating Report. Please wait...
        </p>
      </div>
    </div>

    <div class="">
      <div class="p-4 bg-gray-50">
        <h1 class="text-2xl font-semibold mb-4 text-gray-900">
          Inventory Report
        </h1>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3 mb-4 items-end">
          <div class="flex flex-col">
            <label class="text-sm text-gray-600">Category</label>
            <select
              v-model="selectedCategory"
              class="border rounded px-3 py-2 text-sm w-48"
            >
              <option value="">All Categories</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>

          <div class="flex flex-col">
            <label class="text-sm text-gray-600">Start Date</label>
            <input
              type="date"
              v-model="startDate"
              class="border rounded px-3 py-2 text-sm w-40"
            />
          </div>

          <div class="flex flex-col">
            <label class="text-sm text-gray-600">End Date</label>
            <input
              type="date"
              v-model="endDate"
              class="border rounded px-3 py-2 text-sm w-40"
            />
          </div>

          <div class="flex flex-col">
            <label class="text-sm text-gray-600 mb-1">Report Type</label>
            <select
              v-model="InventoryRpType"
              class="border rounded px-3 py-2 text-sm w-48"
            >
              <option value="">Select Report Type</option>
              <option value="stockin">Stock In</option>
              <option value="stockout">Stock Out</option>
              <option value="all">All</option>
            </select>
          </div>

          <div class="flex flex-col">
            <label class="text-sm text-gray-600 mb-1">Item Type</label>
            <select
              v-model="InventoryItemType"
              class="border rounded px-3 py-2 text-sm w-40"
            >
              <option value="">Select Item Type</option>
              <option value="0">Wet Ingredients</option>
              <option value="1">Dry Ingredients</option>
              <option value="all">All</option>
            </select>
          </div>

          <button
            @click="fetchInventoryMovements"
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm flex items-center gap-2"
          >
            <span class="fa fa-database"></span>
            Fetch Data
          </button>

          <button
            @click="generatePdfReport"
            :disabled="inventoryMovements.length === 0"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm flex items-center gap-2 disabled:opacity-50"
          >
            <span class="fa fa-print"></span>
            PDF
          </button>

          <button
            @click="generateExcelReport"
            :disabled="inventoryMovements.length === 0"
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm flex items-center gap-2 disabled:opacity-50"
          >
            <span class="fa fa-file-excel"></span>
            Excel
          </button>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow overflow-auto max-h-[55vh]">
          <table class="min-w-full table-auto border-collapse text-sm">

            <!-- ✅ WET INGREDIENTS HEADER -->
            <thead v-if="isWet()" class="sticky top-0 bg-gray-100 z-10">
              <tr class="text-left font-semibold">
                <th class="px-2 py-2 border">#</th>
                <th class="px-2 py-2 border">Category</th>
                <th class="px-2 py-2 border">Item Name</th>
                <th class="px-2 py-2 border">Unit</th>
                <th class="px-2 py-2 border text-green-700">Quantity</th>
                <th class="px-2 py-2 border">Remarks</th>
              </tr>
            </thead>

            <!-- ✅ DRY INGREDIENTS HEADER -->
            <thead v-else-if="isDry()" class="sticky top-0 bg-gray-100 z-10">
              <tr class="text-left font-semibold">
                <th class="px-2 py-2 border">#</th>
                <th class="px-2 py-2 border">Category</th>
                <th class="px-2 py-2 border">Item Name</th>
                <th class="px-2 py-2 border">Unit</th>
                <th class="px-2 py-2 border text-yellow-600">Actual Count</th>
                <th class="px-2 py-2 border text-green-600">Stock In</th>
                <th class="px-2 py-2 border text-red-600">Stock Out</th>
                <th class="px-2 py-2 border text-blue-600">Final Count</th>
                <th class="px-2 py-2 border">Remarks</th>
              </tr>
            </thead>

            <!-- ✅ ALL / DEFAULT HEADER -->
            <thead v-else class="sticky top-0 bg-gray-100 z-10">
              <tr class="text-left font-semibold">
                <th class="px-2 py-2 border">#</th>
                <th class="px-2 py-2 border">Category</th>
                <th class="px-2 py-2 border">Item Name</th>
                <th class="px-2 py-2 border">Unit</th>
                <th class="px-2 py-2 border">Current Stock</th>
                <th class="px-2 py-2 border text-green-600">Stock In</th>
                <th class="px-2 py-2 border text-red-600">Stock Out</th>
                <th class="px-2 py-2 border">Total Used Cost</th>
              </tr>
            </thead>

            <tbody>

              <!-- ✅ WET INGREDIENTS ROWS -->
              <template v-if="isWet()">
                <tr
                  v-for="(movement, index) in inventoryMovements"
                  :key="movement.id"
                  class="hover:bg-gray-50"
                >
                  <td class="px-2 py-2 border">{{ index + 1 }}</td>
                  <td class="px-2 py-2 border">{{ movement.category }}</td>
                  <td class="px-2 py-2 border font-medium">{{ movement.name }}</td>
                  <td class="px-2 py-2 border">{{ movement.unit }}</td>
                  <td class="px-2 py-2 border text-green-600 font-semibold">
                    {{ movement.stockInQty ?? movement.current_quantity }}
                  </td>
                  <td class="px-2 py-2 border text-gray-500 italic">
                    {{ movement.remarks ?? '—' }}
                  </td>
                </tr>
              </template>

              <!-- ✅ DRY INGREDIENTS ROWS -->
              <template v-else-if="isDry()">
                <tr
                  v-for="(movement, index) in inventoryMovements"
                  :key="movement.id"
                  class="hover:bg-gray-50"
                >
                  <td class="px-2 py-2 border">{{ index + 1 }}</td>
                  <td class="px-2 py-2 border">{{ movement.category }}</td>
                  <td class="px-2 py-2 border font-medium">{{ movement.name }}</td>
                  <td class="px-2 py-2 border">{{ movement.unit }}</td>

                  <!-- Actual Count from physical_count adjustment -->
                  <td class="px-2 py-2 border text-yellow-600 font-semibold">
                    {{ movement.actualCount ?? 0 }}
                  </td>

                  <!-- Stock In -->
                  <td class="px-2 py-2 border text-green-600 font-semibold">
                    {{ movement.stockInQty ?? 0 }}
                  </td>

                  <!-- Stock Out -->
                  <td class="px-2 py-2 border text-red-600 font-semibold">
                    {{ movement.stockOutQty ?? 0 }}
                  </td>

                  <!-- Final Count = actualCount + stockIn - stockOut -->
                  <td class="px-2 py-2 border text-blue-600 font-semibold">
                    {{ (Number(movement.actualCount ?? 0) + Number(movement.stockInQty ?? 0) - Number(movement.stockOutQty ?? 0)) }}
                  </td>

                  <td class="px-2 py-2 border text-gray-500 italic">
                    {{ movement.remarks ?? '—' }}
                  </td>
                </tr>
              </template>

              <!-- ✅ ALL / DEFAULT ROWS -->
              <template v-else>
                <tr
                  v-for="(movement, index) in inventoryMovements"
                  :key="movement.id"
                  class="hover:bg-gray-50"
                >
                  <td class="px-2 py-2 border">{{ index + 1 }}</td>
                  <td class="px-2 py-2 border">{{ movement.category }}</td>
                  <td class="px-2 py-2 border font-medium">{{ movement.name }}</td>
                  <td class="px-2 py-2 border">{{ movement.unit }}</td>
                  <td
                    class="px-2 py-2 border font-semibold"
                    :class="movement.current_quantity <= 5 ? 'text-red-600' : 'text-green-600'"
                  >
                    {{ movement.current_quantity }}
                  </td>
                  <td class="px-2 py-2 border text-green-600 font-semibold">
                    {{ movement.stockInQty ?? 0 }}
                  </td>
                  <td class="px-2 py-2 border text-red-600 font-semibold">
                    {{ movement.stockOutQty ?? 0 }}
                  </td>
                  <td class="px-2 py-2 border">
                    ₱{{ Number(movement.total_cost ?? 0).toFixed(2) }}
                  </td>
                </tr>
              </template>

              <!-- Empty State -->
              <tr v-if="inventoryMovements.length === 0">
                <td colspan="9" class="text-center py-6 text-gray-400">
                  No records found. Please fetch data first.
                </td>
              </tr>

            </tbody>
          </table>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useInventoryReport } from '@/Composables/reports/useInventoryReport'

const {
  categories,
  selectedCategory,
  InventoryRpType,
  InventoryItemType,
  startDate,
  endDate,
  isLoading,
  inventoryMovements,
  fetchInventoryMovements,
  generatePdfReport,
  generateExcelReport,
  isDry,
  isWet,
} = useInventoryReport()
</script>