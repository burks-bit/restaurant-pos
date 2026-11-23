<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50 min-h-screen">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900">Deduction Types</h1>
            <p class="text-sm text-gray-500">
              Manage payroll deduction types
            </p>
          </div>

          <button
            class="flex items-center gap-2 text-sm bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700"
            @click="createItem"
          >
            <i class="fa fa-plus"></i>
            Add Deduction Type
          </button>
        </div>

        <div v-if="flash.success" class="mb-3 px-3 py-2 rounded-lg bg-green-100 text-green-700 text-sm">
          {{ flash.success }}
        </div>
        <div v-if="flash.error" class="mb-3 px-3 py-2 rounded-lg bg-red-100 text-red-700 text-sm">
          {{ flash.error }}
        </div>

        <div class="flex justify-between mb-4 flex-wrap gap-2">
          <div>
            <input
              v-model="search"
              type="text"
              placeholder="Search deduction type..."
              class="border rounded-lg px-3 py-2 text-sm w-72 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600"
            />
          </div>
        </div>

        <div class="bg-white rounded-xl shadow overflow-hidden">
          <div class="overflow-auto max-h-[70vh]">
            <table class="min-w-full table-auto border-collapse">
              <thead class="sticky top-0 bg-gray-100 z-10">
                <tr class="text-gray-900 text-left">
                  <th class="px-3 py-2 border">ID</th>
                  <th class="px-3 py-2 border">Name</th>
                  <th class="px-3 py-2 border">Description</th>
                  <th class="px-3 py-2 border">Status</th>
                  <th class="px-3 py-2 border">Actions</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="item in filteredItems"
                  :key="item.id"
                  class="hover:bg-gray-50 bg-white"
                >
                  <td class="px-3 py-2 border">{{ item.id }}</td>

                  <td class="px-3 py-2 border">
                    <div class="font-medium text-gray-900">{{ item.name }}</div>
                  </td>

                  <td class="px-3 py-2 border text-sm text-gray-700">
                    {{ item.description || '—' }}
                  </td>

                  <td class="px-3 py-2 border">
                    <span
                      :class="item.status
                        ? 'bg-green-100 text-green-700'
                        : 'bg-gray-100 text-gray-600'"
                      class="px-2 py-1 rounded-full text-xs font-medium"
                    >
                      {{ item.status ? 'Active' : 'Inactive' }}
                    </span>
                  </td>

                  <td class="px-3 py-2 border">
                    <div class="flex flex-wrap gap-2">
                      <button
                        class="flex items-center gap-1 px-2 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                        @click="editItem(item)"
                      >
                        <i class="fa fa-edit"></i>
                        Edit
                      </button>

                      <!-- <button
                        class="flex items-center gap-1 px-2 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700"
                        @click="deleteItem(item)"
                      >
                        <i class="fa fa-trash"></i>
                        Delete
                      </button> -->
                    </div>
                  </td>
                </tr>

                <tr v-if="filteredItems.length === 0">
                  <td colspan="5" class="text-center py-6 text-gray-400">
                    No deduction types found
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Add Modal -->
        <div
          v-if="showAddModal"
          class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 px-4"
        >
          <div class="bg-white rounded-xl w-full max-w-md p-6 shadow-xl">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <i class="fa fa-plus"></i>
              Add New Deduction Type
            </h2>

            <form class="space-y-4" @submit.prevent="saveItem">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input
                  v-model="newForm.name"
                  type="text"
                  required
                  class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600"
                />
                <p v-if="errors.name" class="text-xs text-red-600 mt-1">{{ errors.name }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea
                  v-model="newForm.description"
                  rows="3"
                  class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600"
                ></textarea>
                <p v-if="errors.description" class="text-xs text-red-600 mt-1">{{ errors.description }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select
                  v-model="newForm.status"
                  class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600"
                >
                  <option :value="1">Active</option>
                  <option :value="0">Inactive</option>
                </select>
              </div>

              <div class="flex justify-end gap-2 mt-4">
                <button
                  type="button"
                  class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300"
                  @click="closeAddModal"
                >
                  Cancel
                </button>

                <button
                  type="submit"
                  class="flex items-center gap-1 px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700"
                >
                  <i class="fa fa-save"></i>
                  Save
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Edit Modal -->
        <div
          v-if="showEditModal"
          class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 px-4"
        >
          <div class="bg-white rounded-xl w-full max-w-md p-6 shadow-xl">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <i class="fa fa-edit"></i>
              Edit Deduction Type
            </h2>

            <form class="space-y-4" @submit.prevent="updateItem">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input
                  v-model="editForm.name"
                  type="text"
                  required
                  class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                />
                <p v-if="errors.name" class="text-xs text-red-600 mt-1">{{ errors.name }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea
                  v-model="editForm.description"
                  rows="3"
                  class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                ></textarea>
                <p v-if="errors.description" class="text-xs text-red-600 mt-1">{{ errors.description }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select
                  v-model="editForm.status"
                  class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                >
                  <option :value="1">Active</option>
                  <option :value="0">Inactive</option>
                </select>
              </div>

              <div class="flex justify-end gap-2 mt-4">
                <button
                  type="button"
                  class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300"
                  @click="closeEditModal"
                >
                  Cancel
                </button>

                <button
                  type="submit"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
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
import { useLookupTypesIndex } from '@/Composables/lookupTypes/useLookupTypesIndex'

const {
  search,
  showAddModal,
  showEditModal,
  newForm,
  editForm,
  filteredItems,
  flash,
  errors,
  createItem,
  closeAddModal,
  editItem,
  closeEditModal,
  saveItem,
  updateItem,
  deleteItem,
} = useLookupTypesIndex({ pageProp: 'deduction_types', basePath: '/admin/deduction-types' })
</script>

<style scoped>
body {
  font-family: 'Poppins', sans-serif;
}
</style>
