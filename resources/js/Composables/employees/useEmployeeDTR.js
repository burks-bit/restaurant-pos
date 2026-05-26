import { ref, computed, watch } from 'vue'
import axios from 'axios'
import useRolePrefix from '@/Composables/useRolePrefix'

export function useEmployeeDTR() {
  const { prefix } = useRolePrefix()

  const filters = ref({
    start_date: '',
    end_date: '',
  })

  const employees = ref([])
  const deductions = ref([])
  const isLoading = ref(false)

  const showDeductionModal = ref(false)
  const showViewModal = ref(false)

  const deductionForm = ref({
    id: null,
    employee_id: null,
    employee_name: '',
    lates: 0,
    philhealth: 0,
    sss: 0,
    pagibig: 0,
    tax: 0,
    loan: 0,
  })

  const search = ref('')
  const page = ref(1)
  const perPage = ref(10)

  const resetDeductionForm = () => {
    deductionForm.value = {
      id: null,
      employee_id: null,
      employee_name: '',
      lates: 0,
      philhealth: 0,
      sss: 0,
      pagibig: 0,
      tax: 0,
      loan: 0,
    }
  }

  const filteredEmployees = computed(() => {
    return employees.value
      .filter((emp) =>
        `${emp.first_name} ${emp.last_name} ${emp.employee_code}`
          .toLowerCase()
          .includes(search.value.toLowerCase())
      )
      .map((emp) => ({
        ...emp,
        overtime_hours: Number(emp.overtime_hours || 0).toFixed(2),
        total_earnings: Number(emp.total_earnings || 0).toFixed(2),
      }))
  })

  const paginatedEmployees = computed(() => {
    const start = (page.value - 1) * perPage.value
    return filteredEmployees.value.slice(start, start + perPage.value)
  })

  const grandTotal = computed(() => {
    return filteredEmployees.value
      .reduce((sum, emp) => sum + Number(emp.total_earnings || 0), 0)
      .toFixed(2)
  })

  const totalPages = computed(() => {
    return Math.max(1, Math.ceil(filteredEmployees.value.length / perPage.value))
  })

  watch([search, perPage], () => {
    page.value = 1
  })

  const getSchedules = async () => {
    if (!filters.value.start_date || !filters.value.end_date) {
      alert('Please select both dates.')
      return
    }

    try {
      isLoading.value = true

      const { data } = await axios.get(`/${prefix.value}/payroll/summary`, {
        params: filters.value,
      })

      employees.value = data
      page.value = 1
    } catch (error) {
      alert(error.response?.data?.message ?? 'Failed to fetch payroll summary.')
    } finally {
      isLoading.value = false
    }
  }

  const printPayroll = () => {
    const query = new URLSearchParams(filters.value).toString()
    window.open(`/${prefix.value}/payroll/summary/print?${query}`, '_blank')
  }

  const postDeduction = (emp) => {
    deductionForm.value = {
      id: null,
      employee_id: emp.id,
      employee_name: `${emp.first_name} ${emp.last_name}`,
      lates: 0,
      philhealth: 0,
      sss: 0,
      pagibig: 0,
      tax: 0,
      loan: 0,
    }

    showDeductionModal.value = true
  }

  const editDeduction = (emp) => {
    const ded = deductions.value.find((d) => d.employee_id === emp.id)

    if (!ded) {
      alert('No deductions to edit.')
      return
    }

    deductionForm.value = { ...ded }
    showDeductionModal.value = true
  }

  const viewDeduction = (emp) => {
    const ded = deductions.value.find((d) => d.employee_id === emp.id)

    deductionForm.value = ded
      ? { ...ded }
      : {
          id: null,
          employee_id: emp.id,
          employee_name: `${emp.first_name} ${emp.last_name}`,
          lates: 0,
          philhealth: 0,
          sss: 0,
          pagibig: 0,
          tax: 0,
          loan: 0,
        }

    showViewModal.value = true
  }

  const closeDeductionModal = () => {
    showDeductionModal.value = false
    resetDeductionForm()
  }

  const closeViewModal = () => {
    showViewModal.value = false
    resetDeductionForm()
  }

  const submitDeduction = () => {
    if (!deductionForm.value.employee_id) {
      alert('Select an employee.')
      return
    }

    if (deductionForm.value.id) {
      const index = deductions.value.findIndex((d) => d.id === deductionForm.value.id)
      if (index !== -1) {
        deductions.value[index] = { ...deductionForm.value }
      }
    } else {
      deductions.value.push({
        ...deductionForm.value,
        id: Date.now(),
      })
    }

    closeDeductionModal()
  }

  const nextPage = () => {
    if (page.value < totalPages.value) {
      page.value++
    }
  }

  const prevPage = () => {
    if (page.value > 1) {
      page.value--
    }
  }

  return {
    filters,
    employees,
    deductions,
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
  }
}