<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50 min-h-screen">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900">Tables Management</h1>
            <p class="text-sm text-gray-500">
              Manage parent tables and their child slots (A–D)
            </p>
          </div>

          <button
            class="flex items-center gap-2 text-sm bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700"
            @click="createTable"
          >
            <i class="fa fa-plus"></i>
            Add Table
          </button>
        </div>

        <div class="flex justify-between mb-4 flex-wrap gap-2">
          <div>
            <input
              v-model="search"
              type="text"
              placeholder="Search parent table or child table..."
              class="border rounded-lg px-3 py-2 text-sm w-72 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600"
            />
          </div>
        </div>

        <div class="bg-white rounded-xl shadow overflow-hidden">
          <div class="overflow-auto max-h-[70vh]">
            <table class="min-w-full table-auto border-collapse">
              <thead class="sticky top-0 bg-gray-100 z-10">
                <tr class="text-gray-900 text-left">
                  <th class="px-3 py-2 border w-12"></th>
                  <th class="px-3 py-2 border">ID</th>
                  <th class="px-3 py-2 border">Parent Table</th>
                  <th class="px-3 py-2 border">Capacity</th>
                  <th class="px-3 py-2 border">Slots</th>
                  <th class="px-3 py-2 border">Occupancy</th>
                  <th class="px-3 py-2 border">Status</th>
                  <th class="px-3 py-2 border">Actions</th>
                </tr>
              </thead>

              <tbody>
                <template
                  v-for="table in filteredTables"
                  :key="table.id"
                >
                  <!-- Parent row -->
                  <tr class="hover:bg-gray-50 bg-white">
                    <td class="px-3 py-2 border text-center">
                      <button
                        v-if="table.children && table.children.length"
                        class="w-7 h-7 rounded hover:bg-gray-200 text-gray-700"
                        @click="toggleExpand(table.id)"
                      >
                        <i
                          class="fa"
                          :class="expandedRows.includes(table.id) ? 'fa-chevron-down' : 'fa-chevron-right'"
                        ></i>
                      </button>
                    </td>

                    <td class="px-3 py-2 border">{{ table.id }}</td>

                    <td class="px-3 py-2 border">
                      <div class="font-medium text-gray-900">{{ table.name }}</div>
                      <div class="text-xs text-gray-500">
                        {{ table.children?.length ? 'Parent table with child slots' : 'Standalone table' }}
                      </div>
                    </td>

                    <td class="px-3 py-2 border">{{ table.capacity }}</td>

                    <td class="px-3 py-2 border">
                      <span class="text-sm text-gray-700">
                        {{ occupiedChildrenCount(table) }} / {{ totalChildrenCount(table) || table.capacity }}
                      </span>
                    </td>

                    <td class="px-3 py-2 border">
                      <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-900">
                          {{ occupiedChildrenCount(table) }} occupied
                        </span>
                        <span class="text-xs text-gray-500">
                          {{ availableChildrenCount(table) }} available
                        </span>
                      </div>
                    </td>

                    <td class="px-3 py-2 border">
                      <span
                        :class="getParentStatusClass(table)"
                        class="px-2 py-1 rounded-full text-xs font-medium"
                      >
                        {{ getParentStatus(table) }}
                      </span>
                    </td>

                    <td class="px-3 py-2 border">
                      <div class="flex flex-wrap gap-2">
                        <button
                          class="flex items-center gap-1 px-2 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                          @click="editTable(table)"
                        >
                          <i class="fa fa-edit"></i>
                          Edit
                        </button>

                        <button
                          class="flex items-center gap-1 px-2 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700"
                          @click="deleteTable(table)"
                        >
                          <i class="fa fa-trash"></i>
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>

                  <!-- Child rows -->
                  <tr
                    v-if="expandedRows.includes(table.id) && table.children?.length"
                    class="bg-gray-50"
                  >
                    <td colspan="8" class="border px-3 py-3">
                      <div class="rounded-lg border bg-white overflow-hidden">
                        <table class="min-w-full table-auto border-collapse">
                          <thead class="bg-gray-50">
                            <tr class="text-left text-sm text-gray-700">
                              <th class="px-3 py-2 border">Child ID</th>
                              <th class="px-3 py-2 border">Slot Name</th>
                              <th class="px-3 py-2 border">Capacity</th>
                              <th class="px-3 py-2 border">Status</th>
                              <th class="px-3 py-2 border">Guest Count</th>
                              <th class="px-3 py-2 border">Actions</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr
                              v-for="child in table.children"
                              :key="child.id"
                              class="hover:bg-gray-50"
                            >
                              <td class="px-3 py-2 border">{{ child.id }}</td>
                              <td class="px-3 py-2 border font-medium text-gray-800">{{ child.name }}</td>
                              <td class="px-3 py-2 border">{{ child.capacity }}</td>
                              <td class="px-3 py-2 border">
                                <span
                                  :class="child.status === 'vacant'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-red-100 text-red-700'"
                                  class="px-2 py-1 rounded-full text-xs font-medium"
                                >
                                  {{ child.status }}
                                </span>
                              </td>
                              <td class="px-3 py-2 border">{{ child.guest_count ?? 0 }}</td>
                              <td class="px-3 py-2 border">
                                <div class="flex flex-wrap gap-2">
                                  <button
                                    class="flex items-center gap-1 px-2 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                                    @click="editTable(child)"
                                  >
                                    <i class="fa fa-edit"></i>
                                    Edit
                                  </button>

                                  <button
                                    class="flex items-center gap-1 px-2 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700"
                                    @click="deleteTable(child)"
                                  >
                                    <i class="fa fa-trash"></i>
                                    Delete
                                  </button>
                                </div>
                              </td>
                            </tr>

                            <tr v-if="!table.children.length">
                              <td colspan="6" class="text-center py-4 text-gray-400">
                                No child tables found
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </td>
                  </tr>
                </template>

                <tr v-if="filteredTables.length === 0">
                  <td colspan="8" class="text-center py-6 text-gray-400">
                    No tables found
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
              Add New Table
            </h2>

            <form class="space-y-4" @submit.prevent="saveTable">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input
                  v-model="newForm.name"
                  type="text"
                  required
                  class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                <input
                  v-model="newForm.capacity"
                  type="number"
                  min="1"
                  required
                  class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Parent Table</label>
                <select
                  v-model="newForm.parent_id"
                  class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600"
                >
                  <option :value="null">None (Main Table)</option>
                  <option
                    v-for="parent in parentTableOptions"
                    :key="parent.id"
                    :value="parent.id"
                  >
                    {{ parent.name }}
                  </option>
                </select>
                <p class="text-xs text-gray-500 mt-1">
                  Select a parent if this is a child slot like Table 1-A.
                </p>
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
              Edit Table
            </h2>

            <form class="space-y-4" @submit.prevent="updateTable">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input
                  v-model="editForm.name"
                  type="text"
                  required
                  class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                <input
                  v-model="editForm.capacity"
                  type="number"
                  min="1"
                  required
                  class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Parent Table</label>
                <select
                  v-model="editForm.parent_id"
                  class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                >
                  <option :value="null">None (Main Table)</option>
                  <option
                    v-for="parent in editableParentOptions"
                    :key="parent.id"
                    :value="parent.id"
                  >
                    {{ parent.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select
                  v-model="editForm.status"
                  class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                >
                  <option value="vacant">Vacant</option>
                  <option value="occupied">Occupied</option>
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
import { useTablesIndex } from '@/Composables/tables/useTablesIndex'

const {
  search,
  showAddModal,
  showEditModal,
  expandedRows,
  newForm,
  editForm,
  filteredTables,
  parentTableOptions,
  editableParentOptions,
  createTable,
  closeAddModal,
  editTable,
  closeEditModal,
  saveTable,
  updateTable,
  deleteTable,
  toggleExpand,
  totalChildrenCount,
  occupiedChildrenCount,
  availableChildrenCount,
  getParentStatus,
  getParentStatusClass,
} = useTablesIndex()
</script>

<style scoped>
body {
  font-family: 'Poppins', sans-serif;
}
</style>