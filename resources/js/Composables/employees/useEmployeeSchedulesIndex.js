import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import useRolePrefix from '@/Composables/useRolePrefix'

export function useEmployeeSchedulesIndex() {
  const { prefix } = useRolePrefix()
  const page = usePage()

  const employees = computed(() => page.props.employees ?? [])

  const search = ref('')
  const startDate = ref('')
  const endDate = ref('')

  const filteredEmployees = computed(() => {
    if (!search.value) return employees.value

    const keyword = search.value.toLowerCase()

    return employees.value.filter((employee) =>
      `${employee.first_name} ${employee.last_name}`.toLowerCase().includes(keyword) ||
      (employee.employee_code || '').toLowerCase().includes(keyword)
    )
  })

  const getEmploymentText = (employee) => {
    return employee.employment_details?.length
      ? employee.employment_details
          .map((detail) => `${detail.position} - ${detail.department}`)
          .join(', ')
      : 'N/A'
  }

  const printAllSchedules = () => {
    if (!startDate.value || !endDate.value) {
      alert('Please select both start and end dates')
      return
    }

    const url = route(`${prefix.value}.employees.schedules.printAll`, {
      start_date: startDate.value,
      end_date: endDate.value,
    })

    window.open(url, '_blank')
  }

  return {
    prefix,
    employees,
    search,
    startDate,
    endDate,
    filteredEmployees,
    getEmploymentText,
    printAllSchedules,
  }
}