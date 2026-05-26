<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50">
        <h1 class="text-2xl font-semibold mb-4 text-gray-900">
          Inventory Categories
        </h1>

        <div class="flex justify-between mb-4 flex-wrap gap-2">
          <input
            v-model="search"
            type="text"
            placeholder="Search categories…"
            class="border rounded px-3 py-1 text-sm w-64 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
          />

          <button
            class="flex items-center gap-1 text-sm bg-blue-600 text-white px-2.5 py-1.5 rounded hover:bg-blue-700"
            @click="createCategory"
          >
            <i class="fa fa-plus"></i>
            Add Category
          </button>
        </div>

        <div class="bg-white rounded-lg shadow overflow-auto max-h-[60vh]">
          <table class="min-w-full table-auto border-collapse">
            <thead class="sticky top-0 bg-gray-100 z-10">
              <tr class="text-gray-900 text-left">
                <th class="px-2 py-1 border">ID</th>
                <th class="px-2 py-1 border">Name</th>
                <th class="px-2 py-1 border">Description</th>
                <th class="px-2 py-1 border">Status</th>
                <th class="px-2 py-1 border">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="category in filteredCategories"
                :key="category.id"
                class="hover:bg-gray-50"
              >
                <td class="px-2 py-1 border">{{ category.id }}</td>
                <td class="px-2 py-1 border">{{ category.name }}</td>
                <td class="px-2 py-1 border">{{ category.description }}</td>
                <td class="px-2 py-1 border">
                  <span :class="category.status ? 'text-green-600' : 'text-red-600'">
                    {{ category.status ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="px-2 py-1 border flex gap-2">
                  <button
                    class="flex items-center gap-1 px-1.5 py-0.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                    @click="editCategory(category)"
                  >
                    <i class="fa fa-edit"></i>
                    Edit
                  </button>

                  <button
                    class="flex items-center gap-1 px-1.5 py-0.5 text-sm bg-red-600 text-white rounded hover:bg-red-700"
                    @click="deleteCategory(category)"
                  >
                    <i class="fa fa-trash"></i>
                    Delete
                  </button>
                </td>
              </tr>

              <tr v-if="filteredCategories.length === 0">
                <td colspan="5" class="text-center py-6 text-gray-400">
                  No categories found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-if="showAddModal"
          class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
          <div class="bg-white rounded-lg w-full max-w-md p-6">
            <h2 class="text-xl font-semibold mb-4">
              <i class="fa fa-plus"></i> Add Category
            </h2>

            <form class="space-y-4" @submit.prevent="saveCategory">
              <div>
                <label class="block text-sm mb-1">Name</label>
                <input
                  v-model="newForm.name"
                  type="text"
                  class="w-full border rounded px-3 py-2 text-sm"
                  required
                />
              </div>

              <div>
                <label class="block text-sm mb-1">Description</label>
                <textarea
                  v-model="newForm.description"
                  class="w-full border rounded px-3 py-2 text-sm"
                />
              </div>

              <div>
                <label class="block text-sm mb-1">Status</label>
                <select
                  v-model="newForm.status"
                  class="w-full border rounded px-3 py-2 text-sm"
                >
                  <option :value="1">Active</option>
                  <option :value="0">Inactive</option>
                </select>
              </div>

              <div class="flex justify-end gap-2">
                <button
                  type="button"
                  @click="closeAddModal"
                  class="px-4 py-2 bg-gray-200 rounded"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="px-4 py-2 bg-blue-600 text-white rounded"
                >
                  Save
                </button>
              </div>
            </form>
          </div>
        </div>

        <div
          v-if="showEditModal"
          class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
          <div class="bg-white rounded-lg w-full max-w-md p-6">
            <h2 class="text-xl font-semibold mb-4">
              <i class="fa fa-edit"></i> Edit Category
            </h2>

            <form class="space-y-4" @submit.prevent="updateCategory">
              <div>
                <label class="block text-sm mb-1">Name</label>
                <input
                  v-model="editForm.name"
                  type="text"
                  class="w-full border rounded px-3 py-2 text-sm"
                  required
                />
              </div>

              <div>
                <label class="block text-sm mb-1">Description</label>
                <textarea
                  v-model="editForm.description"
                  class="w-full border rounded px-3 py-2 text-sm"
                />
              </div>

              <div>
                <label class="block text-sm mb-1">Status</label>
                <select
                  v-model="editForm.status"
                  class="w-full border rounded px-3 py-2 text-sm"
                >
                  <option :value="1">Active</option>
                  <option :value="0">Inactive</option>
                </select>
              </div>

              <div class="flex justify-end gap-2">
                <button
                  type="button"
                  @click="closeEditModal"
                  class="px-4 py-2 bg-gray-200 rounded"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="px-4 py-2 bg-blue-600 text-white rounded"
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
import { useInventoryCategories } from '@/Composables/inventory/useInventoryCategories'

const {
  search,
  showAddModal,
  showEditModal,
  newForm,
  editForm,
  filteredCategories,
  createCategory,
  closeAddModal,
  editCategory,
  closeEditModal,
  saveCategory,
  updateCategory,
  deleteCategory,
} = useInventoryCategories()
</script>