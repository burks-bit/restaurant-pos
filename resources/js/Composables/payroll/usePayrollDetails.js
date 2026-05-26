import { ref, computed, watch } from 'vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'
import useRolePrefix from '@/Composables/useRolePrefix'

export function usePayrollDetails(payroll) {

  const { props } = usePage()

  const earning_types = ref(props.earning_types ?? [])
  const deduction_types = ref(props.deduction_types ?? [])

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

  const makeDeductionForm = () => ({
    payroll_item_id: null,
    employee_id: null,
    employee_name: '',
    amounts: Object.fromEntries(deduction_types.value.map((t) => [t.name, 0])),
  })

  const deductionForm = ref(makeDeductionForm())

  const getDeductionAmount = (employee, typeName) => {
    const row = (employee.deductions || []).find(
      (d) => d.deduction_type === typeName
    )
    return row ? Number(row.amount || 0) : 0
  }

  const buildDeductionsFromForm = () => {
    return deduction_types.value
      .map((t) => ({
        id: `${deductionForm.value.payroll_item_id}-${t.name}`,
        deduction_type: t.name,
        amount: Number(deductionForm.value.amounts[t.name] || 0),
        remarks: null,
      }))
      .filter((d) => d.amount > 0)
  }

  const sumDeductions = (list) => {
    return list.reduce((sum, d) => sum + Number(d.amount || 0), 0)
  }

  const openDeductionModal = (employee) => {
    const amounts = Object.fromEntries(
      deduction_types.value.map((t) => [t.name, getDeductionAmount(employee, t.name)])
    )
    deductionForm.value = {
      payroll_item_id: employee.id,
      employee_id: employee.employee_id,
      employee_name: `${employee.employee?.first_name || ''} ${employee.employee?.last_name || ''}`.trim(),
      amounts,
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
    earnings: [],
  })

  const viewDeductionsTotal = computed(() => {
    return viewDeductions.value.list
      .reduce((sum, d) => sum + Number(d.amount || 0), 0)
      .toFixed(2)
  })

  const viewEarningsTotal = computed(() => {       // ← add this too
    return viewDeductions.value.earnings
      .reduce((sum, e) => sum + Number(e.amount || 0), 0)
      .toFixed(2)
  })

  const openViewDeductions = (employee) => {
    viewDeductions.value = {
      employee_name: `${employee.employee?.first_name || ''} ${employee.employee?.last_name || ''}`.trim(),
      payroll_item_id: employee.id,
      list: employee.deductions || [],
      earnings: employee.earnings || [],
    }
    showViewDeductionsModal.value = true
  }

  const closeViewDeductions = () => {
    showViewDeductionsModal.value = false
  }

  // ─── Earnings ──────────────────────────────────────────────────────────────

  const showEarningsModal = ref(false)

  const makeEarningsForm = () => ({
    payroll_item_id: null,
    employee_id: null,
    employee_name: '',
    amounts: Object.fromEntries(earning_types.value.map((t) => [t.name, 0])),
  })

  const earningsForm = ref(makeEarningsForm())

  const getEarningAmount = (employee, typeName) => {
    const row = (employee.earnings || []).find(
      (e) => e.earning_type === typeName
    )
    return row ? Number(row.amount || 0) : 0
  }

  const buildEarningsFromForm = () => {
    return earning_types.value
      .map((t) => ({
        id: `${earningsForm.value.payroll_item_id}-${t.name}`,
        earning_type: t.name,
        amount: Number(earningsForm.value.amounts[t.name] || 0),
        remarks: null,
      }))
      .filter((e) => e.amount > 0)
  }

  const sumEarnings = (list) => {
    return list.reduce((sum, e) => sum + Number(e.amount || 0), 0)
  }

  const openEarningsModal = (employee) => {
    console.log('Opening earnings modal for employee:', employee)
    const amounts = Object.fromEntries(
      earning_types.value.map((t) => [t.name, getEarningAmount(employee, t.name)])
    )
    earningsForm.value = {
      payroll_item_id: employee.id,
      employee_id: employee.employee_id,
      employee_name: `${employee.employee?.first_name || ''} ${employee.employee?.last_name || ''}`.trim(),
      amounts,
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
    openViewDeductions,
    closeViewDeductions,
    viewEarningsTotal,

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