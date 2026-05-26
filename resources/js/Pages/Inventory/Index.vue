<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50">

        <h1 class="text-2xl font-semibold mb-4 text-gray-900">
          Inventory Summary
        </h1>

        <div class="flex flex-wrap gap-4 mb-4 text-sm font-medium text-gray-800">
          <div class="bg-white shadow rounded px-4 py-2 flex-1 text-center">
            Total Items
            <div class="text-xl font-bold">{{ totalItems }}</div>
          </div>

          <div class="bg-white shadow rounded px-4 py-2 flex-1 text-center">
            Total Stock Quantity
            <div class="text-xl font-bold">{{ totalStockQuantity }}</div>
          </div>

          <div class="bg-white shadow rounded px-4 py-2 flex-1 text-center">
            Low Stock Items
            <div class="text-xl font-bold text-red-600">{{ lowStockItems }}</div>
          </div>
        </div>

        <div class="flex flex-wrap gap-3 mb-4 items-end">
          <input
            v-model="search"
            type="text"
            placeholder="Search items..."
            class="border rounded px-3 py-2 text-sm w-64"
          />

          <div class="flex flex-col">
            <label class="text-sm">Category</label>
            <select
              v-model="selectedCategory"
              class="border rounded px-3 py-2 text-sm w-44"
            >
              <option value="">All Categories</option>
              <option
                v-for="cat in categories"
                :key="cat.id"
                :value="cat.id"
              >
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
            <label class="text-sm text-gray-600 mb-1 w-48">
              Report Type
            </label>
            <select
              v-model="InventoryRpType"
              class="border rounded px-3 py-2 text-sm w-44"
            >
              <option value="">Select Report Type</option>
              <option value="stockin">Stock In</option>
              <option value="stockout">Stock Out</option>
              <option value="all">All</option>
            </select>
          </div>

          <button
            @click="generatePdfReport"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm flex items-center gap-2"
          >
            <span class="fa fa-print"></span>
            Generate PDF Report
          </button>
        </div>

        <div class="bg-white rounded-lg shadow overflow-auto max-h-[45vh]">
          <table class="min-w-full table-auto border-collapse">
            <thead class="sticky top-0 bg-gray-100 z-10">
              <tr class="text-left text-sm font-semibold">
                <th class="px-2 py-2 border">ID</th>
                <th class="px-2 py-2 border">Category</th>
                <th class="px-2 py-2 border">Item</th>
                <th class="px-2 py-2 border">Unit</th>
                <th class="px-2 py-2 border">Current Stock</th>
                <th class="px-2 py-2 border">Stock In (Range)</th>
                <th class="px-2 py-2 border">Stock Out (Range)</th>
                <th class="px-2 py-2 border">Total Used Cost</th>
                <th class="px-2 py-2 border">By</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="movement in filteredInventoryMovements"
                :key="movement.id"
                class="hover:bg-gray-50 text-sm"
              >
                <td class="px-2 py-2 border">{{ movement.id }}</td>

                <td class="px-2 py-2 border">
                  {{ movement.item?.category?.name }}
                </td>

                <td class="px-2 py-2 border font-medium">
                  {{ movement.item?.name }}
                </td>

                <td class="px-2 py-2 border">
                  {{ movement.item?.unit }}
                </td>

                <td
                  class="px-2 py-2 border font-semibold"
                  :class="Number(movement.item?.current_quantity) <= 5 ? 'text-red-600' : 'text-green-600'"
                >
                  {{ movement.item?.current_quantity }}
                </td>

                <td class="px-2 py-2 border text-green-600 font-semibold">
                  {{ movement.type === 'stockin' ? movement.quantity : 0 }}
                </td>

                <td class="px-2 py-2 border text-red-600 font-semibold">
                  {{ movement.type === 'stockout' ? movement.quantity : 0 }}
                </td>

                <td class="px-2 py-2 border">
                  ₱{{ (Number(movement.quantity) * Number(movement.unit_price)).toFixed(2) }}
                </td>

                <td class="px-2 py-2 border">
                  {{ movement.creator?.name }}
                </td>
              </tr>

              <tr v-if="filteredInventoryMovements.length === 0">
                <td colspan="9" class="text-center py-6 text-gray-400">
                  No records found
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
import { useInventorySummary } from '@/Composables/inventory/useInventorySummary'

const {
  categories,
  selectedCategory,
  InventoryRpType,
  search,
  startDate,
  endDate,
  filteredInventoryMovements,
  totalItems,
  totalStockQuantity,
  lowStockItems,
  generatePdfReport,
} = useInventorySummary()
</script>