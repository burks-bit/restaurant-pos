<template>
  <AuthenticatedLayout>

    <div
      v-if="isLoading"
      class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50"
    >
      <div class="bg-white p-8 rounded-xl shadow-xl flex flex-col items-center">
        <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
        <p class="text-lg font-semibold text-gray-700">
          Processing Payroll...
        </p>
      </div>
    </div>

    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50">

        <h1 class="text-2xl font-semibold mb-6 text-gray-900">
          Generate Payroll
        </h1>

        <div class="bg-white p-4 rounded-lg shadow mb-6">
          <div class="flex flex-wrap items-end gap-4">
            <div class="flex flex-col">
              <label class="text-sm text-gray-600 mb-1">Cutoff Start</label>
              <input
                type="date"
                v-model="filters.start_date"
                class="border rounded px-3 py-2 text-sm"
                :disabled="isLoading"
              />
            </div>

            <div class="flex flex-col">
              <label class="text-sm text-gray-600 mb-1">Cutoff End</label>
              <input
                type="date"
                v-model="filters.end_date"
                class="border rounded px-3 py-2 text-sm"
                :disabled="isLoading"
              />
            </div>

            <!-- Replace the select -->
            <select v-model="filters.branch_id" class="border rounded px-4 py-2 text-sm w-64">
              <option value="all">All Branches</option>  <!-- 👈 add this -->
              <option
                v-for="branch in branches"
                :key="branch.id"
                :value="branch.id"
              >
                {{ branch.name }}{{ branch.main == 1 ? ' - Main Branch' : '' }}
              </option>
            </select>

            <div class="flex gap-2">
              <button
                @click="getSchedules"
                :disabled="isLoading"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
              >
                Get Schedules
              </button>

              <!-- Update Post Payroll button disabled condition -->
              <button
                @click="postPayroll"
                :disabled="selectedEmployeeIds.length === 0 || isLoading"
                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
              >
                Post Payroll
              </button>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow mb-6">
          <div class="max-h-[500px] overflow-auto custom-scroll">
            <table class="min-w-full border-collapse text-sm">
              <thead class="bg-gray-100 sticky top-0 z-20">
                <tr>
                  <th class="border px-3 py-2">
                    <input
                      type="checkbox"
                      :checked="allSelected"
                      :disabled="employees.length === 0"
                      @change="toggleAll($event.target.checked)"
                    />
                  </th>
                  <th class="border px-3 py-2">Employee Name</th>
                  <th class="border px-3 py-2 text-center">Days</th>
                  <th class="border px-3 py-2 text-center">Hours</th>
                  <th class="border px-3 py-2 text-center">Late</th>
                  <th class="border px-3 py-2 text-center">Undertime</th>
                  <th class="border px-3 py-2 text-center">Overtime</th>
                  <th class="border px-3 py-2 text-center">Gross Pay</th>
                </tr>
              </thead>

              <tbody>
                <tr v-if="employees.length === 0">
                  <td colspan="7" class="text-center py-6 text-gray-400">
                    No payroll records found.
                  </td>
                </tr>

                <tr
                  v-for="emp in employees"
                  :key="emp.id"
                  class="hover:bg-gray-50"
                >
                  <!-- Replace row checkbox -->
                  <td class="border px-3 py-2 text-center">
                    <input
                      type="checkbox"
                      :checked="selectedEmployeeIds.includes(emp.id)"
                      @change="toggleEmployee(emp.id)"
                    />
                  </td>
                  <td class="border px-3 py-2 font-semibold">
                    {{ emp.first_name }} {{ emp.last_name }}
                    <small class="text-teal-600 text-xs">
                      ({{ emp.employee_code }})
                    </small>
                  </td>
                  <td class="border px-3 py-2 text-center">
                    {{ emp.total_days }}
                  </td>
                  <td class="border px-3 py-2 text-center">
                    {{ emp.total_hours }}
                  </td>
                  <td class="border px-3 py-2 text-center text-red-600">
                    {{ emp.total_late }}
                  </td>
                  <td class="border px-3 py-2 text-center text-orange-600">
                    {{ emp.total_undertime }}
                  </td>
                  <td class="border px-3 py-2 text-center text-green-600">
                    {{ Number(emp.overtime_hours || 0).toFixed(2) }}
                  </td>
                  <td class="border px-3 py-2 text-center font-bold">
                    ₱ {{ Number(emp.total_earnings || 0).toFixed(2) }}
                  </td>
                </tr>
              </tbody>

              <tfoot v-if="employees.length > 0" class="bg-gray-100">
                <tr class="font-bold">
                  <td colspan="6" class="border px-3 py-2 text-right">
                    Grand Total
                  </td>
                  <td class="border px-3 py-2 text-center">
                    ₱ {{ grandTotal }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePayrollGenerate } from '@/Composables/payroll/usePayrollGenerate'

const {
  filters,
  employees,
  isLoading,
  grandTotal,
  getSchedules,
  postPayroll,
  branches,
  selectedEmployeeIds,
  allSelected,
  toggleEmployee,
  toggleAll,
} = usePayrollGenerate()
</script>

<style>
.custom-scroll::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}
.custom-scroll::-webkit-scrollbar-thumb {
  background: #94a3b8;
  border-radius: 10px;
}
.custom-scroll::-webkit-scrollbar-track {
  background: #f1f5f9;
}
</style>