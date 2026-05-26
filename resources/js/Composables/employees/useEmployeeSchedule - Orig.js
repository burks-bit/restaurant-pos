import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import useRolePrefix from '@/Composables/useRolePrefix'

export function useEmployeeSchedule() {
  const { prefix } = useRolePrefix()
  const page = usePage()

  const employee = ref(page.props.employee)

  const showModal = ref(false)
  const form = ref({
    id: null,
    schedule_date: '',
    shift: 'Morning',
    time_in: '08:00',
    time_out: '17:00',
    status: 'Scheduled',
    remarks: '',
  })

  const today = new Date()
  const currentMonth = ref(today.getMonth())
  const currentYear = ref(today.getFullYear())

  const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
  const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ]

  const formatLocalDate = (date) => {
    const d = new Date(date)
    const tzOffset = d.getTimezoneOffset() * 60000
    return new Date(d - tzOffset).toISOString().split('T')[0]
  }

  const printStartDate = ref(formatLocalDate(today))
  const printEndDate = ref(formatLocalDate(today))

  const calendarDays = computed(() => {
    const firstDay = new Date(currentYear.value, currentMonth.value, 1)
    const lastDay = new Date(currentYear.value, currentMonth.value + 1, 0)
    const days = []

    const startOffset = firstDay.getDay()
    const endOffset = 6 - lastDay.getDay()

    for (let i = startOffset; i > 0; i--) {
      const d = new Date(currentYear.value, currentMonth.value, 1 - i)
      days.push({
        date: formatLocalDate(d),
        day: d.getDate(),
        isCurrentMonth: false,
      })
    }

    for (let d = 1; d <= lastDay.getDate(); d++) {
      const date = new Date(currentYear.value, currentMonth.value, d)
      days.push({
        date: formatLocalDate(date),
        day: d,
        isCurrentMonth: true,
      })
    }

    for (let i = 1; i <= endOffset; i++) {
      const d = new Date(currentYear.value, currentMonth.value, lastDay.getDate() + i)
      days.push({
        date: formatLocalDate(d),
        day: d.getDate(),
        isCurrentMonth: false,
      })
    }

    return days
  })

  const getSchedules = (date) => {
    return (employee.value?.schedules ?? []).filter(
      (schedule) => formatLocalDate(schedule.schedule_date) === date
    )
  }

  const resetForm = () => {
    form.value = {
      id: null,
      schedule_date: formatLocalDate(new Date()),
      shift: 'Morning',
      time_in: '08:00',
      time_out: '17:00',
      status: 'Scheduled',
      remarks: '',
    }
  }

  const openModal = (schedule = null) => {
    if (schedule && schedule.id) {
      form.value = {
        ...schedule,
        schedule_date: formatLocalDate(schedule.schedule_date),
        shift: schedule.shift || 'Morning',
        time_in: schedule.time_in || '08:00',
        time_out: schedule.time_out || '17:00',
        status: schedule.status || 'Scheduled',
        remarks: schedule.remarks || '',
      }
    } else {
      form.value = {
        id: null,
        schedule_date: formatLocalDate(schedule?.schedule_date || new Date()),
        shift: 'Morning',
        time_in: '08:00',
        time_out: '17:00',
        status: 'Scheduled',
        remarks: '',
      }
    }

    showModal.value = true
  }

  const closeModal = () => {
    showModal.value = false
    resetForm()
  }

  const saveSchedule = () => {
    const employeeId = employee.value.id

    if (form.value.id) {
      router.put(
        route(`${prefix.value}.employees.schedules.update`, {
          schedule: form.value.id,
        }),
        form.value,
        {
          preserveScroll: true,
          onSuccess: () => {
            const index = employee.value.schedules.findIndex(
              (schedule) => schedule.id === form.value.id
            )

            if (index !== -1) {
              employee.value.schedules[index] = {
                ...employee.value.schedules[index],
                ...form.value,
              }
            }

            closeModal()
          },
        }
      )
    } else {
      router.post(
        route(`${prefix.value}.employees.schedules.store`, {
          employee: employeeId,
        }),
        form.value,
        {
          preserveScroll: true,
          onSuccess: (pageResponse) => {
            if (pageResponse.props.employee?.schedules) {
              employee.value.schedules = pageResponse.props.employee.schedules
            }

            closeModal()
          },
        }
      )
    }
  }

  const prevMonth = () => {
    if (currentMonth.value === 0) {
      currentMonth.value = 11
      currentYear.value--
    } else {
      currentMonth.value--
    }
  }

  const nextMonth = () => {
    if (currentMonth.value === 11) {
      currentMonth.value = 0
      currentYear.value++
    } else {
      currentMonth.value++
    }
  }

  const goBackToEmployees = () => {
    router.get(`/${prefix.value}/employees`)
  }

  const printSchedule = () => {
    if (!printStartDate.value || !printEndDate.value) {
      alert('Please select both start and end dates')
      return
    }

    const url = route(`${prefix.value}.employees.schedules.print`, {
      employee: employee.value.id,
      start_date: printStartDate.value,
      end_date: printEndDate.value,
    })

    window.open(url, '_blank')
  }

  return {
    employee,

    showModal,
    form,
    openModal,
    closeModal,
    saveSchedule,

    today,
    currentMonth,
    currentYear,
    weekDays,
    monthNames,
    calendarDays,

    printStartDate,
    printEndDate,
    printSchedule,

    formatLocalDate,
    getSchedules,

    prevMonth,
    nextMonth,
    goBackToEmployees,
  }
}