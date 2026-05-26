import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import useRolePrefix from '@/Composables/useRolePrefix'

export function useEmployeeOvertimes() {
  const { prefix } = useRolePrefix()
  const page = usePage()

  const employees = ref(page.props.employees ?? [])

  const filters = ref({
    search: '',
    startDate: '',
    endDate: '',
  })

  const toast = ref({
    show: false,
    message: '',
    type: 'success',
  })

  const showToast = (message, type = 'success') => {
    toast.value.message = message
    toast.value.type = type
    toast.value.show = true

    setTimeout(() => {
      toast.value.show = false
    }, 3000)
  }

  const employeesWithOvertime = computed(() => {
    return employees.value.filter((employee) => employee.overtimes?.length > 0)
  })

  const fetchFilteredOvertime = async () => {
    try {
      const response = await axios.get(route(`${prefix.value}.overtimes.filter`), {
        params: {
          search: filters.value.search,
          start_date: filters.value.startDate,
          end_date: filters.value.endDate,
        },
      })

      employees.value = response.data.employees
      showToast('Filtered successfully!', 'success')
    } catch (error) {
      showToast('Failed to fetch filtered requests.', 'error')
    }
  }

  const updateStatus = async (otId, status) => {
    try {
      const response = await axios.put(
        route(`${prefix.value}.overtimes.update-status`, otId),
        { status }
      )

      showToast(response.data.message, 'success')

      employees.value.forEach((employee) => {
        employee.overtimes?.forEach((ot) => {
          if (ot.id === otId) {
            ot.status = status
          }
        })
      })
    } catch (error) {
      showToast('Failed to update status.', 'error')
    }
  }

  const formatOtType = (type) => {
    const map = {
      regular_day: 'Regular Day',
      rest_day: 'Rest Day',
      regular_holiday: 'Regular Holiday',
      special_holiday: 'Special Holiday',
      rest_day_regular_holiday: 'Rest Day Regular Holiday',
      rest_day_special_holiday: 'Rest Day Special Holiday',
    }

    return map[type] ?? type
  }

  const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
  }

  return {
    employees,
    filters,
    toast,

    employeesWithOvertime,

    fetchFilteredOvertime,
    updateStatus,

    formatOtType,
    formatDate,
    showToast,
  }
}