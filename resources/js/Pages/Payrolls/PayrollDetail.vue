<template>
  <AuthenticatedLayout>

    <div
      v-if="isLoading"
      class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-[9999]"
    >
      <div class="bg-white p-8 rounded-xl shadow-xl flex flex-col items-center">
        <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
        <p class="text-lg font-semibold text-gray-700">
          Posting. Please wait...
        </p>
      </div>
    </div>

    <div class="p-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-2xl font-bold">Payroll Details</h1>
          <span class="text-gray-600 text-sm">
            Cutoff: {{ formatDate(payroll.cutoff_start) }} - {{ formatDate(payroll.cutoff_end) }}
          </span>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="printSelectedPayslips"
            :disabled="selectedIds.length === 0"
            class="px-3 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700 text-sm disabled:opacity-50"
          >
            <i class="fa fa-print"></i> Print Selected Payslips ({{ selectedIds.length }})
          </button>
        </div>
      </div>

      <div class="bg-white shadow rounded-lg overflow-auto max-h-[600px]">
        <table class="min-w-full text-sm text-gray-700 border-collapse">
          <thead class="bg-gray-100 sticky top-0">
            <tr>
              <th class="px-4 py-2 text-left">
                <div class="flex items-center gap-2">
                  <input
                    type="checkbox"
                    :checked="isAllSelected"
                    @change="toggleSelectAll($event)"
                  />
                  <span>Employee</span>
                </div>
              </th>
              <th class="px-4 py-2 text-center">Days</th>
              <!-- <th class="px-4 py-2 text-center">Hours</th> -->
              <!-- <th class="px-4 py-2 text-center">Late</th> -->
              <!-- <th class="px-4 py-2 text-center">Undertime</th> -->
              <th class="px-4 py-2 text-center">Overtime</th>
              <th class="px-4 py-2 text-center">Gross Pay</th>
              <th class="px-4 py-2 text-center">Net Pay</th>
              <th class="px-4 py-2 text-center">Deduction</th>
              <th class="px-4 py-2 text-center">Action</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="emp in payrollItems"
              :key="emp.id"
              class="border-t hover:bg-gray-50"
            >
              <td class="px-4 py-2 font-semibold">
                <div class="flex items-start gap-2">
                  <input
                    type="checkbox"
                    :value="emp.id"
                    v-model="selectedIds"
                    class="mt-1"
                  />
                  <div>
                    {{ emp.employee?.first_name }} {{ emp.employee?.last_name }} <br>
                    <small class="text-xs text-teal-600">{{ emp.employee?.employee_code }}</small>
                  </div>
                </div>
              </td>

              <td class="px-4 py-2 text-center">{{ emp.days }}</td>
              <!-- <td class="px-4 py-2 text-center">{{ emp.hours }}</td> -->
              <!-- <td class="px-4 py-2 text-center text-red-600">{{ emp.late }}</td> -->
              <!-- <td class="px-4 py-2 text-center text-orange-600">{{ emp.undertime }}</td> -->
              <td class="px-4 py-2 text-center text-green-600">{{ emp.overtime_hours }}</td>

              <td class="px-4 py-2 text-center font-semibold">
                ₱ {{ Number(emp.gross_pay).toFixed(2) }}
              </td>

              <td class="px-4 py-2 text-center font-semibold">
                ₱ {{ Number(emp.net_pay).toFixed(2) }}
              </td>

              <td class="px-4 py-2 text-center">
                ₱ {{ Number(emp.total_deductions || 0).toFixed(2) }}
              </td>

              <td class="px-4 py-2 text-center">
                <div class="flex flex-wrap gap-2 justify-center">
                  <button
                    @click="openEarningsModal(emp)"
                    class="px-2 py-1 bg-orange-600 text-white rounded hover:bg-orange-700 text-xs"
                  >
                    <i class="fa fa-edit"></i> Earnings
                  </button>

                  <button
                    @click="openDeductionModal(emp)"
                    class="px-2 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-xs"
                  >
                    <i class="fa fa-edit"></i> Deduction
                  </button>

                  <button
                    @click="openViewDeductions(emp)"
                    class="px-2 py-1 bg-slate-600 text-white rounded hover:bg-slate-700 text-xs"
                  >
                    <i class="fa fa-eye"></i> View
                  </button>

                  <button
                    @click="printSinglePayslip(emp)"
                    class="px-2 py-1 bg-emerald-600 text-white rounded hover:bg-emerald-700 text-xs"
                  >
                    <i class="fa fa-print"></i> Payslip
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="!payrollItems.length">
              <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                No employees in this payroll.
              </td>
            </tr>
          </tbody>

          <tfoot class="bg-gray-100 font-bold sticky bottom-0">
            <tr>
              <td colspan="3" class="px-4 py-2 text-right">Grand Total</td>
              <td class="px-4 py-2 text-center">₱ {{ grandTotalEarnings }}</td>
              <td class="px-4 py-2 text-center">₱ {{ grandTotalDeductions }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- ── Earnings Modal ─────────────────────────────────────────────────── -->
      <div
        v-if="showEarningsModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
      >
        <div class="bg-white rounded-lg shadow-lg w-[420px] p-6 max-h-[90vh] overflow-auto">
          <h2 class="text-lg font-semibold mb-1">
            Post Earnings — {{ earningsForm.employee_name }}
          </h2>
          <p class="text-xs text-gray-500 mb-4">Add or update earnings for this payroll period.</p>

          <div class="flex flex-col gap-3 mb-4">
            <template v-for="type in earning_types" :key="type.id">
              <label class="text-sm font-medium">{{ type.name }}</label>
              <input
                type="number"
                v-model.number="earningsForm.amounts[type.name]"
                class="border px-3 py-2 rounded"
              />
            </template>
          </div>

          <div class="flex justify-end gap-2">
            <button @click="closeEarningsModal" class="px-4 py-2 border rounded hover:bg-gray-100">Cancel</button>
            <button @click="submitEarnings" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
              Save
            </button>
          </div>
        </div>
      </div>

      <!-- ── Deduction Modal ────────────────────────────────────────────────── -->
      <div
        v-if="showDeductionModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
      >
        <div class="bg-white rounded-lg shadow-lg w-[420px] p-6 max-h-[90vh] overflow-auto">
          <h2 class="text-lg font-semibold mb-4">
            Post / Update Deductions — {{ deductionForm.employee_name }}
          </h2>

          <div class="flex flex-col gap-3 mb-4">
            <template v-for="type in deduction_types" :key="type.id">
              <label class="text-sm font-medium">{{ type.name }}</label>
              <input
                type="number"
                v-model.number="deductionForm.amounts[type.name]"
                class="border px-3 py-2 rounded"
              />
            </template>
          </div>

          <div class="flex justify-end gap-2">
            <button @click="closeDeductionModal" class="px-4 py-2 border rounded hover:bg-gray-100">Cancel</button>
            <button @click="submitDeduction" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
              Save
            </button>
          </div>
        </div>
      </div>

      <!-- ── View Deductions Modal ──────────────────────────────────────────── -->
      <div
        v-if="showViewDeductionsModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
      >
        <div class="bg-white rounded-lg shadow-lg w-[900px] p-6 max-h-[90vh] overflow-auto">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">
              Payroll Summary — {{ viewDeductions.employee_name }}
            </h2>
            <button @click="closeViewDeductions" class="text-gray-500 hover:text-gray-700">
              <i class="fa fa-times"></i>
            </button>
          </div>

          <div class="grid grid-cols-2 gap-4 mb-5">
            <!-- Earnings Section -->
            <div>
              <h3 class="text-sm font-bold text-emerald-700 uppercase tracking-wide mb-2">Earnings</h3>
              <div v-if="!viewDeductions.earnings?.length" class="text-gray-400 text-sm">
                No posted earnings.
              </div>
              <table v-else class="w-full text-sm border rounded overflow-hidden">
                <thead class="bg-emerald-50">
                  <tr>
                    <th class="px-3 py-2 text-left">Type</th>
                    <th class="px-3 py-2 text-right">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="e in viewDeductions.earnings" :key="e.id" class="border-t">
                    <td class="px-3 py-2 capitalize">{{ e.earning_type }}</td>
                    <td class="px-3 py-2 text-right text-emerald-700">₱ {{ Number(e.amount || 0).toFixed(2) }}</td>
                  </tr>
                  <tr class="border-t font-semibold bg-emerald-50">
                    <td class="px-3 py-2">Total</td>
                    <td class="px-3 py-2 text-right text-emerald-700">₱ {{ viewEarningsTotal }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Deductions Section -->
            <div>
              <h3 class="text-sm font-bold text-red-700 uppercase tracking-wide mb-2">Deductions</h3>
              <div v-if="!viewDeductions.list.length" class="text-gray-400 text-sm">
                No posted deductions.
              </div>
              <table v-else class="w-full text-sm border rounded overflow-hidden">
                <thead class="bg-red-50">
                  <tr>
                    <th class="px-3 py-2 text-left">Type</th>
                    <th class="px-3 py-2 text-right">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="d in viewDeductions.list" :key="d.id" class="border-t">
                    <td class="px-3 py-2 capitalize">{{ d.deduction_type }}</td>
                    <td class="px-3 py-2 text-right text-red-600">₱ {{ Number(d.amount || 0).toFixed(2) }}</td>
                  </tr>
                  <tr class="border-t font-semibold bg-red-50">
                    <td class="px-3 py-2">Total</td>
                    <td class="px-3 py-2 text-right text-red-600">₱ {{ viewDeductionsTotal }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Net Pay -->
          <!-- <div class="border-t pt-3 flex justify-between items-center">
            <span class="text-sm font-bold text-gray-700">Net Pay</span>
            <span class="text-base font-bold text-blue-700">
              ₱ {{ (Number(viewEarningsTotal || 0) - Number(viewDeductionsTotal || 0)).toFixed(2) }}
            </span>
          </div> -->

          <!-- <div class="flex justify-end mt-4">
            <button @click="closeViewDeductions" class="px-4 py-2 border rounded hover:bg-gray-100">
              Close
            </button>
          </div> -->
        </div>
      </div>

    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePayrollDetails } from '@/Composables/payroll/usePayrollDetails'

const props = defineProps({
  payroll: Object
})

const {
  earning_types,
  deduction_types,

  payrollItems,
  isLoading,
  selectedIds,
  isAllSelected,
  toggleSelectAll,

  showDeductionModal,
  deductionForm,
  openDeductionModal,
  closeDeductionModal,
  submitDeduction,

  showViewDeductionsModal,
  viewDeductions,
  viewDeductionsTotal,
  viewEarningsTotal,
  openViewDeductions,
  closeViewDeductions,

  showEarningsModal,
  earningsForm,
  openEarningsModal,
  closeEarningsModal,
  submitEarnings,

  grandTotalEarnings,
  grandTotalDeductions,
  formatDate,
  printSinglePayslip,
  printSelectedPayslips,
} = usePayrollDetails(props.payroll)
</script>