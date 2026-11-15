<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50 max-h-screen">

        <!-- Page Title -->
        <h1 class="text-2xl font-semibold mb-4 text-gray-900">
          Packages
        </h1>

        <!-- Search + Add -->
        <div class="flex justify-between mb-4 flex-wrap gap-2">
          <input
            v-model="search"
            type="text"
            placeholder="Search packages…"
            class="border rounded px-3 py-1 text-sm w-64 focus:outline-none focus:ring-2 focus:ring-blue-600"
          />

          <button
            class="flex items-center gap-1 text-sm bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700"
            @click="openCreate"
          >
            <span class="fa fa-plus"></span>
            New Package
          </button>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow overflow-auto max-h-[60vh]">
          <table class="min-w-full table-auto border-collapse text-sm">
            <thead class="sticky top-0 bg-gray-100 z-10">
              <tr class="text-left text-gray-900">
                <th class="border px-2 py-1 w-12">ID</th>
                <th class="border px-2 py-1">Name</th>
                <th class="border px-2 py-1">Choices</th>
                <th class="border px-2 py-1">Description</th>
                <th class="border px-2 py-1">Prices</th>
                <th class="border px-2 py-1 w-24">Status</th>
                <th class="border px-2 py-1 w-40 whitespace-nowrap">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="pkg in filteredPackages"
                :key="pkg.id"
                class="hover:bg-gray-50 align-top"
              >
                <td class="border px-2 py-1">{{ pkg.id }}</td>
                <td class="border px-2 py-1 font-medium">{{ pkg.name }}</td>
                <td class="border px-2 py-1 max-w-xs">{{ pkg.choices || '—' }}</td>
                <td class="border px-2 py-1 max-w-xs">{{ pkg.description || '—' }}</td>
                <td class="border px-2 py-1">
                  <div v-if="pkg.prices?.length" class="flex flex-wrap gap-1">
                    <span
                      v-for="price in pkg.prices"
                      :key="price.id"
                      class="px-2 py-0.5 text-xs bg-blue-100 text-blue-700 rounded-full whitespace-nowrap"
                    >
                      {{ price.pax }} pax: {{ formatCurrency(price.price) }}
                    </span>
                  </div>
                  <span v-else class="text-gray-400">No prices</span>
                </td>
                <td class="border px-2 py-1">
                  <span
                    class="px-2 py-0.5 text-xs rounded-full"
                    :class="pkg.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600'"
                  >
                    {{ pkg.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="border px-2 py-1 whitespace-nowrap">
                  <div class="inline-flex gap-2">
                    <button
                      class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
                      @click="openEdit(pkg)"
                    >
                      <span class="fa fa-edit"></span> Edit
                    </button>
                    <button
                      class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700"
                      @click="deletePackage(pkg)"
                    >
                      <span class="fa fa-trash"></span> Delete
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="filteredPackages.length === 0">
                <td colspan="7" class="text-center py-6 text-gray-400">
                  No packages found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ================= PACKAGE MODAL ================= -->
        <div
          v-if="showModal"
          class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
          <div class="bg-white rounded-lg w-full max-w-2xl p-6">
            <h2 class="text-xl font-semibold mb-4">
              {{ isEditing ? 'Edit Package' : 'New Package' }}
            </h2>

            <!-- Name -->
            <div class="mb-3">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Name
              </label>
              <input
                v-model="form.name"
                type="text"
                placeholder="e.g. Barkada"
                class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
              />
            </div>

            <!-- Choices -->
            <div class="mb-3">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Choices
              </label>
              <input
                v-model="form.choices"
                type="text"
                placeholder="e.g. 1 pork / 1 veggies or pasta / chicken"
                class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
              />
            </div>

            <!-- Description -->
            <div class="mb-3">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Description
              </label>
              <textarea
                v-model="form.description"
                rows="2"
                class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
              ></textarea>
            </div>

            <!-- Status -->
            <div class="mb-4">
              <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input v-model="form.is_active" type="checkbox" class="rounded" />
                Active
              </label>
            </div>

            <!-- Prices -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-700">
                  Prices (per pax)
                </label>

                <button
                  type="button"
                  class="flex items-center gap-1 text-xs bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700"
                  @click="addPriceRow"
                >
                  <span class="fa fa-plus"></span>
                  Add Row
                </button>
              </div>

              <div class="space-y-2 max-h-56 overflow-y-auto">
                <div
                  v-for="(row, index) in form.prices"
                  :key="index"
                  class="flex items-center gap-2"
                >
                  <input
                    v-model="row.pax"
                    type="number"
                    min="1"
                    step="1"
                    placeholder="Pax"
                    class="w-32 border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
                    :class="{ 'border-red-500': isPaxSelected(row.pax, index) }"
                  />

                  <input
                    v-model="row.price"
                    type="number"
                    min="0"
                    step="0.01"
                    placeholder="Price"
                    class="flex-1 border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
                  />

                  <button
                    type="button"
                    class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700"
                    @click="removePriceRow(index)"
                  >
                    <span class="fa fa-minus"></span>
                  </button>
                </div>

                <p v-if="form.prices.length === 0" class="text-sm text-gray-400">
                  No price rows yet. Click "Add Row" to start.
                </p>
              </div>

              <p v-if="hasDuplicatePax" class="text-xs text-red-600 mt-1">
                Each pax count can only appear once per package.
              </p>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-2 mt-6">
              <button
                type="button"
                class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
                @click="closeModal"
              >
                <span class="fa fa-close"></span> Cancel
              </button>
              <button
                type="button"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                @click="savePackage"
              >
                <span class="fa fa-save"></span> Save
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
import { usePackages } from '@/Composables/packages/usePackages'

const {
  search,
  filteredPackages,
  showModal,
  isEditing,
  form,
  openCreate,
  openEdit,
  closeModal,
  addPriceRow,
  removePriceRow,
  isPaxSelected,
  hasDuplicatePax,
  savePackage,
  deletePackage,
  formatCurrency,
} = usePackages()
</script>
