<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50 max-h-screen">

        <!-- Page Title -->
        <h1 class="text-2xl font-semibold mb-4 text-gray-900">
          Deduction Templates
        </h1>

        <!-- Search + Add -->
        <div class="flex justify-between mb-4 flex-wrap gap-2">
          <input
            v-model="search"
            type="text"
            placeholder="Search employees…"
            class="border rounded px-3 py-1 text-sm w-64 focus:outline-none focus:ring-2 focus:ring-blue-600"
          />

          <button
            class="flex items-center gap-1 text-sm bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700"
            @click="openCreate"
          >
            <span class="fa fa-plus"></span>
            New Deduction Template
          </button>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow overflow-auto max-h-[60vh]">
          <table class="min-w-full table-auto border-collapse text-sm">
            <thead class="sticky top-0 bg-gray-100 z-10">
              <tr class="text-left text-gray-900">
                <th class="border px-2 py-1 w-12">ID</th>
                <th class="border px-2 py-1">Employee</th>
                <th class="border px-2 py-1">Code</th>
                <th class="border px-2 py-1">Deduction Types</th>
                <th class="border px-2 py-1 w-40 whitespace-nowrap">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="template in filteredTemplates"
                :key="template.id"
                class="hover:bg-gray-50"
              >
                <td class="border px-2 py-1">{{ template.id }}</td>
                <td class="border px-2 py-1">
                  {{ employeeName(template.employee) }}
                </td>
                <td class="border px-2 py-1">
                  {{ template.employee?.employee_code }}
                </td>
                <td class="border px-2 py-1">
                  <div
                    v-if="template.details?.length"
                    class="space-y-1"
                  >
                    <div
                      v-for="group in groupedDetails(template.details)"
                      :key="group.value"
                    >
                      <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">
                        {{ group.label }} ({{ group.period }})
                      </div>
                      <div class="flex flex-wrap gap-1">
                        <span
                          v-for="detail in group.items"
                          :key="detail.id"
                          class="px-2 py-0.5 text-xs bg-blue-100 text-blue-700 rounded-full"
                        >
                          {{ detail.deduction_type?.name }}: {{ detail.default_value }}
                        </span>
                      </div>
                    </div>
                  </div>
                  <span v-else class="text-gray-400">No deductions</span>
                </td>
                <td class="border px-2 py-1 whitespace-nowrap">
                  <div class="inline-flex gap-2">
                    <button
                      class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
                      @click="openEdit(template)"
                    >
                      <span class="fa fa-edit"></span> Edit
                    </button>
                    <button
                      class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700"
                      @click="deleteTemplate(template)"
                    >
                      <span class="fa fa-trash"></span> Delete
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="filteredTemplates.length === 0">
                <td colspan="5" class="text-center py-6 text-gray-400">
                  No deduction templates found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ================= DEDUCTION TEMPLATE MODAL ================= -->
        <div
          v-if="showModal"
          class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
          <div class="bg-white rounded-lg w-full max-w-2xl p-6">
            <h2 class="text-xl font-semibold mb-4">
              {{ isEditing ? 'Edit Deduction Template' : 'New Deduction Template' }}
            </h2>

            <!-- Employee Selection -->
            <div class="mb-4 relative">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Employee
              </label>

              <input
                v-model="employeeSearch"
                type="text"
                placeholder="Search employee…"
                class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
                @focus="showEmployeeDropdown = true"
                @input="showEmployeeDropdown = true"
              />

              <div
                v-if="showEmployeeDropdown && filteredEmployees.length"
                class="absolute z-20 mt-1 w-full max-h-48 overflow-auto bg-white border rounded shadow-lg"
              >
                <button
                  v-for="employee in filteredEmployees"
                  :key="employee.id"
                  type="button"
                  class="block w-full text-left px-3 py-2 text-sm hover:bg-blue-50"
                  @mousedown.prevent="selectEmployee(employee)"
                >
                  {{ employeeName(employee) }}
                  <span class="text-gray-400">({{ employee.employee_code }})</span>
                </button>
              </div>
            </div>

            <!-- Deduction Rows -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-700">
                  Deductions
                </label>

                <button
                  type="button"
                  class="flex items-center gap-1 text-xs bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700"
                  @click="addDetailRow()"
                >
                  <span class="fa fa-plus"></span>
                  Add Row
                </button>
              </div>

              <p class="text-xs text-gray-500 mb-2">
                15th Cutoff covers the 26th–10th · 30th Cutoff covers the 11th–25th.
              </p>

              <div class="space-y-2 max-h-64 overflow-y-auto">
                <div
                  v-for="(detail, index) in details"
                  :key="index"
                  class="flex items-center gap-2"
                >
                  <select
                    v-model="detail.cutoff"
                    class="w-44 border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
                  >
                    <option
                      v-for="cutoff in CUTOFFS"
                      :key="cutoff.value"
                      :value="cutoff.value"
                    >
                      {{ cutoff.label }} ({{ cutoff.period }})
                    </option>
                  </select>

                  <select
                    v-model="detail.deduction_type_id"
                    class="flex-1 border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
                  >
                    <option disabled value="">Select deduction type</option>
                    <option
                      v-for="type in deductionTypes"
                      :key="type.id"
                      :value="type.id"
                      :disabled="isDeductionTypeSelected(type.id, index)"
                    >
                      {{ type.name }}
                    </option>
                  </select>

                  <input
                    v-model="detail.default_value"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="Default value"
                    class="w-40 border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
                  />

                  <button
                    type="button"
                    class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700"
                    @click="removeDetailRow(index)"
                  >
                    <span class="fa fa-minus"></span>
                  </button>
                </div>

                <p v-if="details.length === 0" class="text-sm text-gray-400">
                  No deduction rows yet. Click "Add Row" to start.
                </p>
              </div>
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
                @click="saveTemplate"
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
import { useDeductionTemplates } from '@/Composables/deductionTemplates/useDeductionTemplates'

const {
  search,
  filteredTemplates,
  showModal,
  isEditing,
  employeeSearch,
  showEmployeeDropdown,
  filteredEmployees,
  deductionTypes,
  details,
  CUTOFFS,
  cutoffLabel,
  groupedDetails,
  employeeName,
  selectEmployee,
  addDetailRow,
  removeDetailRow,
  isDeductionTypeSelected,
  openCreate,
  openEdit,
  closeModal,
  saveTemplate,
  deleteTemplate,
} = useDeductionTemplates()
</script>
