<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <!-- <div class="p-4 bg-gray-50 min-h-screen"> -->
      <div class="p-4 bg-gray-50">

        <!-- Page Title -->
        <h1 class="text-2xl font-semibold mb-4 text-gray-900">
          Inventory Management
        </h1>

        <!-- Inventory Summary (1 line) -->
        <div class="flex flex-wrap gap-4 mb-6 text-sm font-medium text-gray-800">
          <div class="bg-white shadow rounded px-4 py-2 flex-1 text-center">
            Total Ingredients
            <div class="text-xl font-bold">{{ totalIngredients }}</div>
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

        <!-- Search -->
        <div class="flex justify-between mb-4 flex-wrap gap-2">
          <input
            v-model="search"
            type="text"
            placeholder="Search ingredients…"
            class="border rounded px-3 py-1 text-sm w-64
                   focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
          />
        </div>

        <!-- Table Container -->
        <div class="bg-white rounded-lg shadow overflow-auto max-h-[50vh]">
          <table class="min-w-full table-auto border-collapse">
            <thead class="sticky top-0 bg-gray-100 z-10">
              <tr class="text-gray-900 text-left">
                <th class="px-2 py-1 border">ID</th>
                <th class="px-2 py-1 border">Name</th>
                <th class="px-2 py-1 border">Unit</th>
                <th class="px-2 py-1 border">Stock</th>
                <th class="px-2 py-1 border">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in filteredIngredients"
                :key="item.id"
                class="hover:bg-gray-50"
              >
                <td class="px-2 py-1 border">{{ item.id }}</td>
                <td class="px-2 py-1 border">{{ item.name }}</td>
                <td class="px-2 py-1 border">{{ item.unit }}</td>
                <td
                  class="px-2 py-1 border font-semibold"
                  :class="item.quantity <= 5 ? 'text-red-600' : 'text-green-600'"
                >
                  {{ item.quantity }}
                </td>
                <td class="px-2 py-1 border flex gap-2">
                  <button
                    class="flex items-center gap-1 px-1.5 py-0.5 text-sm
                           bg-green-600 text-white rounded hover:bg-green-700"
                    @click="openStockIn(item)"
                  >
                    <i class="fa fa-arrow-down"></i> Stock In
                  </button>
                  <button
                    class="flex items-center gap-1 px-1.5 py-0.5 text-sm
                           bg-red-600 text-white rounded hover:bg-red-700"
                    @click="openStockOut(item)"
                  >
                    <i class="fa fa-arrow-up"></i> Stock Out
                  </button>
                </td>
              </tr>

              <tr v-if="filteredIngredients.length === 0">
                <td colspan="5" class="text-center py-6 text-gray-400">
                  No ingredients found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Stock Modal -->
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
                  class="w-full border-gray-300 rounded px-3 py-2
                         focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 text-sm"
                />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Note</label>
                <input
                  type="text"
                  v-model="form.note"
                  class="w-full border-gray-300 rounded px-3 py-2
                         focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 text-sm"
                  placeholder="Optional"
                />
              </div>

              <div class="flex justify-end gap-2 mt-4">
                <button
                  type="button"
                  class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
                  @click="showModal = false"
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

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Inertia } from '@inertiajs/inertia'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const page = usePage()

const ingredients = ref(page.props.ingredients ?? [])
const search = ref('')

const showModal = ref(false)
const mode = ref('in')
const selected = ref({})
const form = ref({ quantity: '', note: '' })

// --- Filter ingredients locally ---
const filteredIngredients = computed(() => {
  if (!search.value) return ingredients.value
  return ingredients.value.filter(i =>
    i.name.toLowerCase().includes(search.value.toLowerCase())
  )
})

// --- Inventory summary ---
const totalIngredients = computed(() => ingredients.value.length)
const totalStockQuantity = computed(() =>
  ingredients.value.reduce((sum, item) => sum + Number(item.quantity), 0)
)
const lowStockItems = computed(() =>
  ingredients.value.filter(i => Number(i.quantity) <= 5).length
)

// --- Stock Modal ---
const openStockIn = (item) => {
  selected.value = item
  mode.value = 'in'
  form.value = { quantity: '', note: '' }
  showModal.value = true
}

const openStockOut = (item) => {
  selected.value = item
  mode.value = 'out'
  form.value = { quantity: '', note: '' }
  showModal.value = true
}

const submitStock = () => {
  const routeName = mode.value === 'in'
    ? 'admin.inventory.stock-in'
    : 'admin.inventory.stock-out'

  Inertia.post(
    route(routeName, selected.value.id),
    form.value,
    { onSuccess: () => showModal.value = false }
  )
}
</script>

<style scoped>
body {
  font-family: 'Poppins', sans-serif;
}
</style>