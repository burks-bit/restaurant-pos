<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50 max-h-screen">
        <h1 class="text-2xl font-semibold mb-4 text-gray-900">
          Branches Management
        </h1>

        <div class="flex justify-between mb-4 flex-wrap gap-2">
          <input
            v-model="search"
            type="text"
            placeholder="Search branches…"
            class="border rounded px-3 py-1 text-sm w-64 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
          />

          <button
            class="flex items-center gap-1 text-sm bg-blue-600 text-white px-2.5 py-1.5 rounded hover:bg-blue-700"
            @click="createBranch"
          >
            <i class="fa fa-plus"></i>
            Add Branch
          </button>
        </div>

        <div class="bg-white rounded-lg shadow overflow-auto max-h-[60vh]">
          <table class="min-w-full table-auto border-collapse">
            <thead class="sticky top-0 bg-gray-100 z-10">
              <tr class="text-gray-900 text-left">
                <th class="px-2 py-1 border">ID</th>
                <th class="px-2 py-1 border">Name</th>
                <th class="px-2 py-1 border">Code</th>
                <th class="px-2 py-1 border">Address</th>
                <th class="px-2 py-1 border">Contact</th>
                <th class="px-2 py-1 border">Main</th>
                <th class="px-2 py-1 border">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="branch in filteredBranches"
                :key="branch.id"
                class="hover:bg-gray-50"
              >
                <td class="px-2 py-1 border">{{ branch.id }}</td>
                <td class="px-2 py-1 border">{{ branch.name }}</td>
                <td class="px-2 py-1 border">{{ branch.code }}</td>
                <td class="px-2 py-1 border">{{ branch.address }}</td>
                <td class="px-2 py-1 border">{{ branch.contact }}</td>
                <td class="px-2 py-1 border">{{ branch.main ? 'Yes' : 'No' }}</td>
                <td class="px-2 py-1 border flex gap-2">
                  <button
                    class="flex items-center gap-1 px-1.5 py-0.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                    @click="editBranch(branch)"
                  >
                    <i class="fa fa-edit"></i>
                    Edit
                  </button>

                  <button
                    class="flex items-center gap-1 px-1.5 py-0.5 text-sm bg-red-600 text-white rounded hover:bg-red-700"
                    @click="deleteBranch(branch)"
                  >
                    <i class="fa fa-trash"></i>
                    Delete
                  </button>
                </td>
              </tr>

              <tr v-if="filteredBranches.length === 0">
                <td colspan="7" class="text-center py-6 text-gray-400">
                  No branches found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Add Branch Modal -->
        <div
          v-if="showAddModal"
          class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
          <div class="bg-white rounded-lg w-full max-w-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <i class="fa fa-plus"></i>
              Add New Branch
            </h2>

            <form class="space-y-4" @submit.prevent="saveBranch">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input
                  type="text"
                  v-model="newForm.name"
                  class="w-full border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 text-sm"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
                <input
                  type="text"
                  v-model="newForm.code"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <input
                  type="text"
                  v-model="newForm.address"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact</label>
                <input
                  type="text"
                  v-model="newForm.contact"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Main Branch</label>
                <select
                  v-model="newForm.main"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                >
                  <option :value="1">Yes</option>
                  <option :value="0">No</option>
                </select>
              </div>

              <div class="flex justify-end gap-2 mt-4">
                <button
                  type="button"
                  class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
                  @click="closeAddModal"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="flex items-center gap-1 px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                  <i class="fa fa-save"></i>
                  Save
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Edit Branch Modal -->
        <div
          v-if="showEditModal"
          class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
          <div class="bg-white rounded-lg w-full max-w-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <i class="fa fa-edit"></i>
              Edit Branch
            </h2>

            <form class="space-y-4" @submit.prevent="updateBranch">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input
                  type="text"
                  v-model="editForm.name"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
                <input
                  type="text"
                  v-model="editForm.code"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <input
                  type="text"
                  v-model="editForm.address"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact</label>
                <input
                  type="text"
                  v-model="editForm.contact"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Main Branch</label>
                <select
                  v-model="editForm.main"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                >
                  <option :value="1">Yes</option>
                  <option :value="0">No</option>
                </select>
              </div>

              <div class="flex justify-end gap-2 mt-4">
                <button
                  type="button"
                  class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
                  @click="closeEditModal"
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
import { useBranches } from '@/Composables/branches/useBranches'

const {
  search,
  showAddModal,
  showEditModal,
  newForm,
  editForm,
  filteredBranches,
  createBranch,
  closeAddModal,
  editBranch,
  closeEditModal,
  saveBranch,
  updateBranch,
  deleteBranch,
} = useBranches()
</script>

<style scoped>
body {
  font-family: 'Poppins', sans-serif;
}
</style>