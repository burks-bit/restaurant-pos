import { ref, computed, watch } from 'vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'
import useRolePrefix from '@/Composables/useRolePrefix'

export function usePayrollDetails(payroll) {
  
  const { props } = usePage()

  const earning_types = ref(props.earning_types ?? 0)
  const deduction_types = ref(props.deduction_types ?? 0)
  // console.log(earning_types.value);
  // console.log(deduction_types.value);
  const { prefix } = useRolePrefix()

  const payrollItems = ref([])
  const isLoading = ref(false)

  watch(
    () => payroll?.items,
    (items) => {
      payrollItems.value = (items || []).map((item) => ({ ...item }))
    },
    { immediate: true, deep: true }
  )

  const selectedIds = ref([])

  const isAllSelected = computed(() => {
    const total = payrollItems.value.length
    return total > 0 && selectedIds.value.length === total
  })

  const toggleSelectAll = (event) => {
    if (event.target.checked) {
      selectedIds.value = payrollItems.value.map((item) => item.id)
    } else {
      selectedIds.value = []
    }
  }

  // ─── Deductions ────────────────────────────────────────────────────────────

  const showDeductionModal = ref(false)
  const deductionForm = ref({
    payroll_item_id: null,
    employee_id: null,
    employee_name: '',
    tardiness: 0,
    undertime: 0,
    cash_advance: 0,
    philhealth: 0,
    sss: 0,
    pagibig: 0,
    // tax: 0,
    // loan: 0,
  })

  const getDeductionAmount = (employee, type) => {
    const row = (employee.deductions || []).find(
      (deduction) => deduction.deduction_type === type
    )
    return row ? Number(row.amount || 0) : 0
  }

  const buildDeductionsFromForm = () => {
    const map = [
      ['tardiness', deductionForm.value.tardiness],
      ['undertime', deductionForm.value.undertime],
      ['cash_advance', deductionForm.value.cash_advance],
      ['philhealth', deductionForm.value.philhealth],
      ['sss', deductionForm.value.sss],
      ['pagibig', deductionForm.value.pagibig],
      // ['tax', deductionForm.value.tax],
      // ['loan', deductionForm.value.loan],
    ]

    return map
      .map(([type, amount]) => ({
        id: `${deductionForm.value.payroll_item_id}-${type}`,
        deduction_type: type,
        amount: Number(amount || 0),
        remarks: null,
      }))
      .filter((deduction) => deduction.amount > 0)
  }

  const sumDeductions = (list) => {
    return list.reduce((sum, deduction) => sum + Number(deduction.amount || 0), 0)
  }

  const openDeductionModal = (employee) => {
    deductionForm.value = {
      payroll_item_id: employee.id,
      employee_id: employee.employee_id,
      employee_name: `${employee.employee?.first_name || ''} ${employee.employee?.last_name || ''}`.trim(),
      tardiness: getDeductionAmount(employee, 'tardiness'),
      undertime: getDeductionAmount(employee, 'undertime'),
      cash_advance: getDeductionAmount(employee, 'cash_advance'),
      philhealth: getDeductionAmount(employee, 'philhealth'),
      sss: getDeductionAmount(employee, 'sss'),
      pagibig: getDeductionAmount(employee, 'pagibig'),
      // tax: getDeductionAmount(employee, 'tax'),
      // loan: getDeductionAmount(employee, 'loan'),
    }
    showDeductionModal.value = true
  }

  const closeDeductionModal = () => {
    showDeductionModal.value = false
  }

  const submitDeduction = async () => {
    const payrollItemId = deductionForm.value.payroll_item_id

    try {
      isLoading.value = true

      const response = await axios.post(
        route(`${prefix.value}.payroll.deduction.post`, payroll.id),
        deductionForm.value
      )

      const updatedItem =
        response?.data?.payroll_item ||
        response?.data?.item ||
        response?.data?.data ||
        null

      const index = payrollItems.value.findIndex((item) => item.id === payrollItemId)

      if (index !== -1) {
        if (updatedItem) {
          payrollItems.value[index] = {
            ...payrollItems.value[index],
            ...updatedItem,
          }
        } else {
          const newList = buildDeductionsFromForm()
          payrollItems.value[index] = {
            ...payrollItems.value[index],
            deductions: newList,
            total_deductions: sumDeductions(newList),
          }
        }

        if (
          showViewDeductionsModal.value &&
          viewDeductions.value.payroll_item_id === payrollItemId
        ) {
          viewDeductions.value.list = payrollItems.value[index].deductions || []
        }
      }

      selectedIds.value = []
      closeDeductionModal()
    } catch (error) {
      console.error(error)
      alert('Failed to save deduction.')
    } finally {
      isLoading.value = false
    }
  }

  // ─── View Deductions ───────────────────────────────────────────────────────

  const showViewDeductionsModal = ref(false)
  const viewDeductions = ref({
    employee_name: '',
    payroll_item_id: null,
    list: [],
  })

  const viewDeductionsTotal = computed(() => {
    return viewDeductions.value.list
      .reduce((sum, deduction) => sum + Number(deduction.amount || 0), 0)
      .toFixed(2)
  })

  const openViewDeductions = (employee) => {
    viewDeductions.value = {
      employee_name: `${employee.employee?.first_name || ''} ${employee.employee?.last_name || ''}`.trim(),
      payroll_item_id: employee.id,
      list: employee.deductions || [],
    }
    showViewDeductionsModal.value = true
  }

  const closeViewDeductions = () => {
    showViewDeductionsModal.value = false
  }

  // ─── Earnings ──────────────────────────────────────────────────────────────

  const showEarningsModal = ref(false)
  const earningsForm = ref({
    payroll_item_id: null,
    employee_id: null,
    employee_name: '',
    salary_adjustment: 0,
    de_minimis: 0,
    overtime: 0,
    night_differential: 0,
    special_holiday_hours: 0,
    regular_holiday_hours: 0,
  })

  const getEarningAmount = (employee, type) => {
    const row = (employee.earnings || []).find(
      (earning) => earning.earning_type === type
    )
    return row ? Number(row.amount || 0) : 0
  }

  const buildEarningsFromForm = () => {
    const map = [
      ['salary_adjustment', earningsForm.value.salary_adjustment],
      ['de_minimis', earningsForm.value.de_minimis],
      ['overtime', earningsForm.value.overtime],
      ['night_differential', earningsForm.value.night_differential],
      ['special_holiday_hours', earningsForm.value.special_holiday_hours],
      ['regular_holiday_hours', earningsForm.value.regular_holiday_hours],
    ]

    return map
      .map(([type, amount]) => ({
        id: `${earningsForm.value.payroll_item_id}-${type}`,
        earning_type: type,
        amount: Number(amount || 0),
        remarks: null,
      }))
      .filter((earning) => earning.amount > 0)
  }

  const sumEarnings = (list) => {
    return list.reduce((sum, earning) => sum + Number(earning.amount || 0), 0)
  }

  const openEarningsModal = (employee) => {
    earningsForm.value = {
      payroll_item_id: employee.id,
      employee_id: employee.employee_id,
      employee_name: `${employee.employee?.first_name || ''} ${employee.employee?.last_name || ''}`.trim(),
      salary_adjustment: getEarningAmount(employee, 'salary_adjustment'),
      de_minimis: getEarningAmount(employee, 'de_minimis'),
      overtime: getEarningAmount(employee, 'overtime'),
      night_differential: getEarningAmount(employee, 'night_differential'),
      special_holiday_hours: getEarningAmount(employee, 'special_holiday_hours'),
      regular_holiday_hours: getEarningAmount(employee, 'regular_holiday_hours'),
    }
    showEarningsModal.value = true
  }

  const closeEarningsModal = () => {
    showEarningsModal.value = false
  }

  const submitEarnings = async () => {
    const payrollItemId = earningsForm.value.payroll_item_id

    try {
      isLoading.value = true

      const response = await axios.post(
        route(`${prefix.value}.payroll.earnings.post`, payroll.id),
        earningsForm.value
      )

      const updatedItem =
        response?.data?.payroll_item ||
        response?.data?.item ||
        response?.data?.data ||
        null

      const index = payrollItems.value.findIndex((item) => item.id === payrollItemId)

      if (index !== -1) {
        if (updatedItem) {
          payrollItems.value[index] = {
            ...payrollItems.value[index],
            ...updatedItem,
          }
        } else {
          const newList = buildEarningsFromForm()
          payrollItems.value[index] = {
            ...payrollItems.value[index],
            earnings: newList,
            total_earnings: sumEarnings(newList),
          }
        }
      }

      closeEarningsModal()
    } catch (error) {
      console.error(error)
      alert('Failed to save earnings.')
    } finally {
      isLoading.value = false
    }
  }

  // ─── Totals ────────────────────────────────────────────────────────────────

  const grandTotalEarnings = computed(() => {
    return payrollItems.value
      .reduce((sum, employee) => sum + Number(employee.gross_pay || 0), 0)
      .toFixed(2)
  })

  const grandTotalDeductions = computed(() => {
    return payrollItems.value
      .reduce((sum, employee) => sum + Number(employee.total_deductions || 0), 0)
      .toFixed(2)
  })

  // ─── Utilities ─────────────────────────────────────────────────────────────

  const formatDate = (date) => new Date(date).toLocaleDateString()

  const printSinglePayslip = (employee) => {
    const url = route(`${prefix.value}.payroll.payslip.print.single`, {
      payroll: payroll.id,
      payrollItem: employee.id,
    })
    window.open(url, '_blank')
  }

  const printSelectedPayslips = () => {
    const url = route(`${prefix.value}.payroll.payslip.print.selected`, payroll.id)
    const queryString = selectedIds.value
      .map((id) => `ids[]=${encodeURIComponent(id)}`)
      .join('&')
    window.open(`${url}?${queryString}`, '_blank')
  }

  return {
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
  }
}