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
              <th class="px-4 py-2 text-center">Hours</th>
              <th class="px-4 py-2 text-center">Late</th>
              <th class="px-4 py-2 text-center">Undertime</th>
              <th class="px-4 py-2 text-center">Overtime</th>
              <th class="px-4 py-2 text-center">Earnings</th>
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
              <td class="px-4 py-2 text-center">{{ emp.hours }}</td>
              <td class="px-4 py-2 text-center text-red-600">{{ emp.late }}</td>
              <td class="px-4 py-2 text-center text-orange-600">{{ emp.undertime }}</td>
              <td class="px-4 py-2 text-center text-green-600">{{ emp.overtime_hours }}</td>

              <td class="px-4 py-2 text-center font-semibold">
                ₱ {{ Number(emp.total_earnings).toFixed(2) }}
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
              <td colspan="9" class="px-4 py-6 text-center text-gray-500">
                No employees in this payroll.
              </td>
            </tr>
          </tbody>

          <tfoot class="bg-gray-100 font-bold sticky bottom-0">
            <tr>
              <td colspan="6" class="px-4 py-2 text-right">Grand Total</td>
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
            <label class="text-sm font-medium">Salary Adjustment</label>
            <input type="number" v-model.number="earningsForm.salary_adjustment" class="border px-3 py-2 rounded" />

            <label class="text-sm font-medium">De Minimis</label>
            <input type="number" v-model.number="earningsForm.de_minimis" class="border px-3 py-2 rounded" />

            <label class="text-sm font-medium">Overtime</label>
            <input type="number" v-model.number="earningsForm.overtime" class="border px-3 py-2 rounded" />

            <label class="text-sm font-medium">Night Differential</label>
            <input type="number" v-model.number="earningsForm.night_differential" class="border px-3 py-2 rounded" />

            <hr class="my-1" />

            <label class="text-sm font-medium">
              Special Holiday
              <span class="text-xs text-gray-400 font-normal">(hours)</span>
            </label>
            <input type="number" v-model.number="earningsForm.special_holiday_hours" class="border px-3 py-2 rounded" />

            <label class="text-sm font-medium">
              Regular Holiday
              <span class="text-xs text-gray-400 font-normal">(hours)</span>
            </label>
            <input type="number" v-model.number="earningsForm.regular_holiday_hours" class="border px-3 py-2 rounded" />
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
            <label class="text-sm font-medium">Tardiness</label>
            <input type="number" v-model.number="deductionForm.tardiness" class="border px-3 py-2 rounded" />

            <label class="text-sm font-medium">Undertime</label>
            <input type="number" v-model.number="deductionForm.undertime" class="border px-3 py-2 rounded" />

            <label class="text-sm font-medium">Cash Advance</label>
            <input type="number" v-model.number="deductionForm.cash_advance" class="border px-3 py-2 rounded" />

            <label class="text-sm font-medium">PhilHealth</label>
            <input type="number" v-model.number="deductionForm.philhealth" class="border px-3 py-2 rounded" />

            <label class="text-sm font-medium">SSS</label>
            <input type="number" v-model.number="deductionForm.sss" class="border px-3 py-2 rounded" />

            <label class="text-sm font-medium">Pag-IBIG</label>
            <input type="number" v-model.number="deductionForm.pagibig" class="border px-3 py-2 rounded" />

            <!-- <label class="text-sm font-medium">Tax</label>
            <input type="number" v-model.number="deductionForm.tax" class="border px-3 py-2 rounded" />

            <label class="text-sm font-medium">Loan</label>
            <input type="number" v-model.number="deductionForm.loan" class="border px-3 py-2 rounded" /> -->
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
        <div class="bg-white rounded-lg shadow-lg w-[520px] p-6 max-h-[90vh] overflow-auto">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">
              Posted Deductions — {{ viewDeductions.employee_name }}
            </h2>
            <button @click="closeViewDeductions" class="text-gray-500 hover:text-gray-700">
              <i class="fa fa-times"></i>
            </button>
          </div>

          <div v-if="!viewDeductions.list.length" class="text-gray-500 text-sm">
            No posted deductions.
          </div>

          <table v-else class="min-w-full text-sm border">
            <thead class="bg-gray-100">
              <tr>
                <th class="px-3 py-2 text-left">Type</th>
                <th class="px-3 py-2 text-right">Amount</th>
                <th class="px-3 py-2 text-left">Remarks</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in viewDeductions.list" :key="d.id" class="border-t">
                <td class="px-3 py-2 capitalize">{{ d.deduction_type }}</td>
                <td class="px-3 py-2 text-right">₱ {{ Number(d.amount || 0).toFixed(2) }}</td>
                <td class="px-3 py-2">{{ d.remarks || '-' }}</td>
              </tr>
              <tr class="border-t font-semibold bg-gray-50">
                <td class="px-3 py-2 text-right" colspan="1">Total</td>
                <td class="px-3 py-2 text-right">₱ {{ viewDeductionsTotal }}</td>
                <td class="px-3 py-2"></td>
              </tr>
            </tbody>
          </table>

          <div class="flex justify-end mt-4">
            <button @click="closeViewDeductions" class="px-4 py-2 border rounded hover:bg-gray-100">
              Close
            </button>
          </div>
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