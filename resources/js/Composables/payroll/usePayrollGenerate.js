import { ref, computed } from 'vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'
import useRolePrefix from '@/Composables/useRolePrefix'

export function usePayrollGenerate() {
  const { prefix } = useRolePrefix()
  const page = usePage()
  
  const branches = computed(() => page.props.branches ?? [])
  console.log('Branches in usePayrollGenerate:', branches.value)

  const filters = ref({
    start_date: '',
    end_date: '',
    branch_id: '',
  })

  const employees = ref([])
  const selectedEmployeeIds = ref([])
  const isLoading = ref(false)

  const toggleEmployee = (id) => {
  if (selectedEmployeeIds.value.includes(id)) {
    selectedEmployeeIds.value = selectedEmployeeIds.value.filter(i => i !== id)
  } else {
    selectedEmployeeIds.value.push(id)
  }
}

const toggleAll = (checked) => {
  selectedEmployeeIds.value = checked ? employees.value.map(e => e.id) : []
}

const allSelected = computed(() =>
  employees.value.length > 0 &&
  selectedEmployeeIds.value.length === employees.value.length
)

// Update grandTotal to only sum selected
// const grandTotal = computed(() => {
//   return employees.value
//     .reduce((sum, e) => sum + Number(e.total_earnings || 0), 0)
//     .toFixed(2)
// })
const grandTotal = computed(() => {
  return employees.value
    .reduce((sum, e) => sum + Number(e.total_earnings || 0), 0)
    .toFixed(2)
})

  const resetState = () => {
    filters.value = {
      start_date: '',
      end_date: '',
    }
    employees.value = []
  }

  const getSchedules = async () => {
    if (!filters.value.start_date || !filters.value.end_date) {
      alert('Please select cutoff dates.')
      return
    }

    try {
      isLoading.value = true

      console.log('Fetching payroll summary with filters:', filters.value)

      const { data } = await axios.get(`/${prefix.value}/payroll/summary`, {
        params: filters.value,
      })

      employees.value = data ?? []
      console.log('Fetched employees:', employees.value)
      selectedEmployeeIds.value = [] 
    } catch (error) {
      console.error(error)
      alert('Failed to fetch payroll preview.')
    } finally {
      isLoading.value = false
    }
  }

// Update postPayroll to only post selected
const postPayroll = async () => {
  if (selectedEmployeeIds.value.length === 0) {
    alert('Please select at least one employee.')
    return
  }
  if (!confirm('Are you sure you want to post this payroll?')) return

  try {
    isLoading.value = true

    const selectedEmployees = employees.value.filter(e =>
      selectedEmployeeIds.value.includes(e.id)
    )

    await axios.post(route(`${prefix.value}.payroll.post`), {
      ...filters.value,
      employees: selectedEmployees,
    })

    alert('Payroll posted successfully.')
    resetState()
    selectedEmployeeIds.value = []
  } catch (error) {
    console.error(error.response?.data || error)
    alert('Failed to post payroll.')
  } finally {
    isLoading.value = false
  }
}

  return {
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
  }
}