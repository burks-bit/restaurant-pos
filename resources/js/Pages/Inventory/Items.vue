<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50">

        <h1 class="text-2xl font-semibold mb-4 text-gray-900">
          Inventory Items
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

        <div class="flex flex-wrap gap-2 mb-4 items-end justify-between">
          <div class="flex flex-wrap gap-2 items-end">
            <input
              v-model="search"
              type="text"
              placeholder="Search items…"
              class="border rounded px-3 py-2 text-sm w-64 focus:outline-none focus:ring-1 focus:ring-blue-600 focus:border-blue-600"
            />

            <div class="flex flex-col">
              <label class="text-sm text-gray-600 mb-1">Category</label>
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
          </div>

          <button
            class="flex items-center gap-1 text-sm bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700"
            @click="openAddItemModal"
          >
            <i class="fa fa-plus"></i> Add Item
          </button>
        </div>

        <div class="bg-white rounded-lg shadow overflow-auto max-h-[60vh]">
          <table class="min-w-full table-auto border-collapse">
            <thead class="sticky top-0 bg-gray-100 z-10">
              <tr class="text-gray-900 text-left">
                <th class="px-2 py-1 border">ID</th>
                <th class="px-2 py-1 border">Name</th>
                <th class="px-2 py-1 border">Category</th>
                <th class="px-2 py-1 border">Unit</th>
                <th class="px-2 py-1 border">Stock</th>
                <th class="px-2 py-1 border">Used Today</th>
                <th class="px-2 py-1 border">Unit Price</th>
                <th class="px-2 py-1 border">Created By</th>
                <th class="px-2 py-1 border">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in filteredItemsWithUsage"
                :key="item.id"
                class="hover:bg-gray-50"
              >
                <td class="px-2 py-1 border">{{ item.id }}</td>
                <td class="px-2 py-1 border">{{ item.name }}</td>
                <td class="px-2 py-1 border">{{ item.category.name }}</td>
                <td class="px-2 py-1 border">{{ item.unit }}</td>
                <td
                  class="px-2 py-1 border font-semibold"
                  :class="item.current_quantity <= 5 ? 'text-red-600' : 'text-green-600'"
                >
                  {{ item.current_quantity }}
                </td>
                <td class="px-2 py-1 border">{{ item.usedToday || 0 }}</td>
                <td class="px-2 py-1 border">₱{{ item.unit_price }}</td>
                <td class="px-2 py-1 border">{{ item.creator?.name }}</td>
                <td class="px-2 py-1 border flex gap-2">
                  <button
                    class="flex items-center gap-1 px-2 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700"
                    @click="openStockIn(item)"
                  >
                    <i class="fa fa-arrow-down"></i> Stock In
                  </button>
                  <button
                    class="flex items-center gap-1 px-2 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700"
                    @click="openStockOut(item)"
                  >
                    <i class="fa fa-arrow-up"></i> Stock Out
                  </button>
                </td>
              </tr>

              <tr v-if="filteredItemsWithUsage.length === 0">
                <td colspan="9" class="text-center py-6 text-gray-400">
                  No items found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-if="showModal"
          class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
          <div class="bg-white rounded-lg w-full max-w-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
              {{ mode === 'in' ? 'Stock In' : 'Stock Out' }} - {{ selected.name }}
            </h2>

            <form class="space-y-4" @submit.prevent="submitStock">
              <div>
                <label class="block text-sm font-medium mb-1">Quantity</label>
                <input
                  type="number"
                  v-model="form.quantity"
                  min="1"
                  required
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm"
                />
              </div>

              <div v-if="mode === 'in'">
                <label class="block text-sm font-medium mb-1">Unit Price (₱)</label>
                <input
                  type="number"
                  v-model="form.unit_price"
                  min="0"
                  step="0.01"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm"
                  placeholder="Optional"
                />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Note</label>
                <input
                  type="text"
                  v-model="form.note"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm"
                  placeholder="Optional"
                />
              </div>

              <div class="flex justify-end gap-2 mt-4">
                <button
                  type="button"
                  class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
                  @click="closeStockModal"
                >
                  Cancel
                </button>

                <button
                  type="submit"
                  class="px-4 py-2 text-white rounded"
                  :class="mode === 'in'
                    ? 'bg-green-600 hover:bg-green-700'
                    : 'bg-red-600 hover:bg-red-700'"
                >
                  Save
                </button>
              </div>
            </form>
          </div>
        </div>

        <div
          v-if="showAddItemModal"
          class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
          <div class="bg-white rounded-lg w-full max-w-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <i class="fa fa-plus"></i> Add New Inventory Item
            </h2>

            <form class="space-y-4" @submit.prevent="submitAddItem">
              <div>
                <label class="block text-sm font-medium mb-1">Name</label>
                <input
                  type="text"
                  v-model="newItem.name"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Category</label>
                <select
                  v-model="newItem.category_id"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm"
                  required
                >
                  <option value="" disabled>Select Category</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Unit</label>
                <input
                  type="text"
                  v-model="newItem.unit"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Current Quantity</label>
                <input
                  type="number"
                  v-model="newItem.current_quantity"
                  min="0"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Unit Price (₱)</label>
                <input
                  type="number"
                  v-model="newItem.unit_price"
                  min="0"
                  step="0.01"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm"
                />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Make it orderable on POS?</label>
                <select
                  v-model="newItem.orderable"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm"
                  required
                >
                  <option value="" disabled>Select Category</option>
                  <option value="0">NO</option>
                  <option value="1">YES</option>
                </select>
              </div>

              <div class="flex justify-end gap-2 mt-4">
                <button
                  type="button"
                  class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
                  @click="closeAddItemModal"
                >
                  Cancel
                </button>

                <button
                  type="submit"
                  class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                  Save
                </button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useInventoryItems } from '@/Composables/inventory/useInventoryItems'

const {
  categories,
  search,
  selectedCategory,
  showModal,
  mode,
  selected,
  form,
  showAddItemModal,
  newItem,
  filteredItemsWithUsage,
  totalItems,
  totalStockQuantity,
  lowStockItems,
  openStockIn,
  openStockOut,
  closeStockModal,
  submitStock,
  openAddItemModal,
  closeAddItemModal,
  submitAddItem,
} = useInventoryItems()
</script>

<style scoped>
body {
  font-family: 'Poppins', sans-serif;
}
</style>