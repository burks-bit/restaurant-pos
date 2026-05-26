<template>
  <AuthenticatedLayout>
    <div
      v-if="isLoading"
      class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50"
    >
      <div class="bg-white p-8 rounded-xl shadow-xl flex flex-col items-center">
        <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
        <p class="text-lg font-semibold text-gray-700">Processing Payroll...</p>
      </div>
    </div>

    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50">
        <h1 class="text-2xl font-semibold mb-6 text-gray-900">Payroll Summary</h1>

        <div class="bg-white p-4 rounded-lg shadow mb-6">
          <div class="flex flex-wrap items-end gap-4">
            <div class="flex flex-col">
              <label class="text-sm text-gray-600 mb-1">Search</label>
              <input
                v-model="search"
                type="text"
                placeholder="Search employee..."
                class="border px-3 py-2 rounded text-sm w-64"
              />
            </div>

            <div class="flex flex-col">
              <label class="text-sm text-gray-600 mb-1">Rows</label>
              <select v-model="perPage" class="border px-3 py-2 rounded text-sm w-24">
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="20">20</option>
              </select>
            </div>

            <div class="flex flex-col">
              <label class="text-sm text-gray-600 mb-1">Start Date</label>
              <input
                type="date"
                v-model="filters.start_date"
                class="border rounded px-3 py-2 text-sm"
                :disabled="isLoading"
              />
            </div>

            <div class="flex flex-col">
              <label class="text-sm text-gray-600 mb-1">End Date</label>
              <input
                type="date"
                v-model="filters.end_date"
                class="border rounded px-3 py-2 text-sm"
                :disabled="isLoading"
              />
            </div>

            <div class="flex gap-2">
              <button
                @click="getSchedules"
                :disabled="isLoading"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
              >
                <span class="fa fa-filter"></span> Get Schedules
              </button>

              <button
                @click="printPayroll"
                :disabled="employees.length === 0 || isLoading"
                class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-800 disabled:opacity-50"
              >
                <span class="fa fa-print"></span> Print Payroll Copy
              </button>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow mb-6">
          <div class="max-h-[500px] overflow-auto custom-scroll">
            <table class="min-w-full border-collapse text-sm">
              <thead class="bg-gray-100 sticky top-0 z-20">
                <tr>
                  <th class="bg-gray-100 z-30 border px-3 py-2">Employee Name</th>
                  <th class="border px-3 py-2 text-center">Days</th>
                  <th class="border px-3 py-2 text-center">Hours</th>
                  <th class="border px-3 py-2 text-center">Late</th>
                  <th class="border px-3 py-2 text-center">Undertime</th>
                  <th class="border px-3 py-2 text-center">Overtime</th>
                  <th class="border px-3 py-2 text-center">Earnings</th>
                  <th class="sticky right-0 bg-gray-100 z-30 border px-3 py-2 text-center">Actions</th>
                </tr>
              </thead>

              <tbody>
                <tr v-if="paginatedEmployees.length === 0">
                  <td colspan="8" class="text-center py-6 text-gray-400">No payroll records found.</td>
                </tr>

                <tr v-for="emp in paginatedEmployees" :key="emp.id" class="hover:bg-gray-50">
                  <td class="bg-white border px-3 py-2 min-w-[200px] font-bold">
                    {{ emp.first_name }} {{ emp.last_name }}
                    (<small style="font-size:10px;color:teal">{{ emp.employee_code }}</small>)
                  </td>
                  <td class="border px-3 py-2 text-center">{{ emp.total_days }}</td>
                  <td class="border px-3 py-2 text-center">{{ emp.total_hours }}</td>
                  <td class="border px-3 py-2 text-center text-red-600">{{ emp.total_late }}</td>
                  <td class="border px-3 py-2 text-center text-orange-600">{{ emp.total_undertime }}</td>
                  <td class="border px-3 py-2 text-center text-green-600">{{ emp.overtime_hours }}</td>
                  <td class="border px-3 py-2 text-center font-semibold">₱ {{ Number(emp.total_earnings).toFixed(2) }}</td>

                  <td class="sticky right-0 bg-white border px-3 py-2 text-center flex justify-center gap-1">
                    <button
                      @click="postDeduction(emp)"
                      class="px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-xs"
                    >
                      <i class="fa fa-plus"></i> Post
                    </button>
                    <button
                      @click="viewDeduction(emp)"
                      class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-xs"
                    >
                      <i class="fa fa-eye"></i> View
                    </button>
                    <button
                      @click="editDeduction(emp)"
                      class="px-2 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600 text-xs"
                    >
                      <i class="fa fa-edit"></i> Edit
                    </button>
                  </td>
                </tr>
              </tbody>

              <tfoot v-if="filteredEmployees.length > 0" class="sticky bottom-0 bg-gray-100 z-20">
                <tr class="font-bold">
                  <td colspan="6" class="border px-3 py-2 text-right">Grand Total Payroll</td>
                  <td class="border px-3 py-2 text-center">₱ {{ grandTotal }}</td>
                  <td class="border px-3 py-2"></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <div v-if="filteredEmployees.length > 0" class="flex justify-between items-center">
          <p class="text-sm text-gray-600">
            Page {{ page }} of {{ totalPages }}
          </p>

          <div class="flex gap-2">
            <button
              @click="prevPage"
              :disabled="page === 1"
              class="px-3 py-1 border rounded disabled:opacity-50"
            >
              Previous
            </button>
            <button
              @click="nextPage"
              :disabled="page === totalPages"
              class="px-3 py-1 border rounded disabled:opacity-50"
            >
              Next
            </button>
          </div>
        </div>

        <div
          v-if="showDeductionModal"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        >
          <div class="bg-white rounded-lg shadow-lg w-[400px] p-6 max-h-[90vh] overflow-auto">
            <h2 class="text-lg font-semibold mb-4">
              {{ deductionForm.id ? 'Edit Deduction' : 'Post Deduction' }}
              for {{ deductionForm.employee_name }}
            </h2>

            <div class="flex flex-col gap-3 mb-4">
              <label>Lates</label>
              <input type="number" v-model.number="deductionForm.lates" class="border px-3 py-2 rounded" />

              <label>PhilHealth</label>
              <input type="number" v-model.number="deductionForm.philhealth" class="border px-3 py-2 rounded" />

              <label>SSS</label>
              <input type="number" v-model.number="deductionForm.sss" class="border px-3 py-2 rounded" />

              <label>Pag-IBIG</label>
              <input type="number" v-model.number="deductionForm.pagibig" class="border px-3 py-2 rounded" />

              <label>Tax</label>
              <input type="number" v-model.number="deductionForm.tax" class="border px-3 py-2 rounded" />

              <label>Loan</label>
              <input type="number" v-model.number="deductionForm.loan" class="border px-3 py-2 rounded" />
            </div>

            <div class="flex justify-end gap-2">
              <button @click="closeDeductionModal" class="px-4 py-2 border rounded hover:bg-gray-100">
                Cancel
              </button>
              <button @click="submitDeduction" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                Save
              </button>
            </div>
          </div>
        </div>

        <div
          v-if="showViewModal"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        >
          <div class="bg-white rounded-lg shadow-lg w-[800px] p-6 max-h-[90vh] overflow-auto">
            <h2 class="text-lg font-semibold mb-4">
              Deductions for {{ deductionForm.employee_name }}
            </h2>

            <table class="min-w-full text-sm border-collapse">
              <thead class="bg-gray-100 sticky top-0">
                <tr>
                  <th class="border px-3 py-2">Lates</th>
                  <th class="border px-3 py-2">PhilHealth</th>
                  <th class="border px-3 py-2">SSS</th>
                  <th class="border px-3 py-2">Pag-IBIG</th>
                  <th class="border px-3 py-2">Tax</th>
                  <th class="border px-3 py-2">Loan</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="border px-3 py-2">{{ deductionForm.lates }}</td>
                  <td class="border px-3 py-2">{{ deductionForm.philhealth }}</td>
                  <td class="border px-3 py-2">{{ deductionForm.sss }}</td>
                  <td class="border px-3 py-2">{{ deductionForm.pagibig }}</td>
                  <td class="border px-3 py-2">{{ deductionForm.tax }}</td>
                  <td class="border px-3 py-2">{{ deductionForm.loan }}</td>
                </tr>
              </tbody>
            </table>

            <div class="flex justify-end mt-4">
              <button @click="closeViewModal" class="px-4 py-2 border rounded hover:bg-gray-100">
                Close
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
import { useEmployeeDTR } from '@/Composables/employees/useEmployeeDTR'

const {
  filters,
  employees,
  isLoading,

  showDeductionModal,
  showViewModal,
  deductionForm,

  search,
  page,
  perPage,
  totalPages,

  filteredEmployees,
  paginatedEmployees,
  grandTotal,

  getSchedules,
  printPayroll,

  postDeduction,
  editDeduction,
  viewDeduction,

  closeDeductionModal,
  closeViewModal,
  submitDeduction,

  nextPage,
  prevPage,
} = useEmployeeDTR()
</script>

<style>
.custom-scroll::-webkit-scrollbar { width: 8px; height: 8px; }
.custom-scroll::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 10px; }
.custom-scroll::-webkit-scrollbar-track { background: #f1f5f9; }
</style>