import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import useRolePrefix from '@/Composables/useRolePrefix'

export function useEmployeeSchedule() {
  const { prefix } = useRolePrefix()
  const page = usePage()

  const employee = ref(page.props.employee)
  console.log('Employee in useEmployeeSchedule:', employee.value)

  const showModal = ref(false)
  const showBatchModal = ref(false)

  const workingStatuses = ['Scheduled']
  const isSavingBatch = ref(false)
  const isSavingSchedule = ref(false)

  const form = ref({
    id: null,
    schedule_date: '',
    time_in: '08:00',
    time_out: '17:00',
    status: 'Scheduled',
    remarks: '',
  })

  const batchForm = ref({
    start_date: '',
    end_date: '',
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

  const formatTime = (time) => {
    return time ? time.slice(0, 5) : ''
  }

  const requiresTime = (status) => {
    return workingStatuses.includes(status)
  }

  const showTimeRange = (schedule) => {
    return requiresTime(schedule.status) && schedule.time_in && schedule.time_out
  }

  const clearTimeIfNotNeeded = (targetForm) => {
    if (!requiresTime(targetForm.status)) {
      targetForm.time_in = ''
      targetForm.time_out = ''
    } else {
      if (!targetForm.time_in) targetForm.time_in = '08:00'
      if (!targetForm.time_out) targetForm.time_out = '17:00'
    }
  }

  watch(
    () => page.props.employee,
    (newEmployee) => {
      if (newEmployee) {
        employee.value = newEmployee
      }
    },
    { deep: true }
  )

  watch(
    () => form.value.status,
    () => clearTimeIfNotNeeded(form.value)
  )

  watch(
    () => batchForm.value.status,
    () => clearTimeIfNotNeeded(batchForm.value)
  )

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

  const getScheduleByDate = (date) => {
    return (employee.value?.schedules ?? []).find(
      (schedule) => formatLocalDate(schedule.schedule_date) === date
    ) || null
  }

  const hasSchedule = (date) => {
    return !!getScheduleByDate(date)
  }

  const openExistingSchedule = (date) => {
    const existingSchedule = getScheduleByDate(date)

    if (existingSchedule) {
      openModal(existingSchedule)
    }
  }

  const scheduleBadgeClass = (status) => {
    return {
      'bg-blue-200 text-blue-800': status === 'Scheduled',
      'bg-red-200 text-red-800': status === 'Absent',
      'bg-yellow-200 text-yellow-800': status === 'Leave',
      'bg-gray-300 text-gray-700': status === 'Day Off',
    }
  }

  const buildScheduleTitle = (schedule) => {
    let title = schedule.status || 'Schedule'

    if (showTimeRange(schedule)) {
      title += ` (${formatTime(schedule.time_in)} - ${formatTime(schedule.time_out)})`
    }

    if (schedule.remarks && schedule.remarks.trim() !== '') {
      title += `: ${schedule.remarks}`
    }

    return title
  }

  const resetForm = () => {
    form.value = {
      id: null,
      schedule_date: formatLocalDate(new Date()),
      time_in: '08:00',
      time_out: '17:00',
      status: 'Scheduled',
      remarks: '',
      actual_time_in: null,
      actual_time_out: null,
      time_in_photo: null,
      time_out_photo: null,
    }
  }

  const resetBatchForm = () => {
    const firstDate = new Date(currentYear.value, currentMonth.value, 1)
    const lastDate = new Date(currentYear.value, currentMonth.value + 1, 0)

    batchForm.value = {
      start_date: formatLocalDate(firstDate),
      end_date: formatLocalDate(lastDate),
      time_in: '08:00',
      time_out: '17:00',
      status: 'Scheduled',
      remarks: '',
    }
  }

  const openModal = (schedule = null) => {
    if (schedule && schedule.id) {
      form.value = {
        id: schedule.id,
        schedule_date: formatLocalDate(schedule.schedule_date),
        time_in: schedule.time_in || '08:00',
        time_out: schedule.time_out || '17:00',
        status: schedule.status || 'Scheduled',
        remarks: schedule.remarks || '',
        actual_time_in: schedule.actual_time_in || null,
        actual_time_out: schedule.actual_time_out || null,
        time_in_photo: schedule.time_in_photo || null,
        time_out_photo: schedule.time_out_photo || null,
      }
    } else {
      form.value = {
        id: null,
        schedule_date: formatLocalDate(schedule?.schedule_date || new Date()),
        time_in: '08:00',
        time_out: '17:00',
        status: 'Scheduled',
        remarks: '',
        actual_time_in: null,
        actual_time_out: null,
        time_in_photo: null,
        time_out_photo: null,
      }
    }

    clearTimeIfNotNeeded(form.value)
    showModal.value = true
  }

  const closeModal = () => {
    showModal.value = false
    resetForm()
  }

  const openBatchModal = () => {
    resetBatchForm()
    showBatchModal.value = true
  }

  const closeBatchModal = () => {
    showBatchModal.value = false
    resetBatchForm()
  }

  // const saveSchedule = () => {
  //   const employeeId = employee.value.id
  //   const payload = { ...form.value }

  //   if (!requiresTime(payload.status)) {
  //     payload.time_in = null
  //     payload.time_out = null
  //   }

  //   if (payload.id) {
  //     router.put(
  //       route(`${prefix.value}.employees.schedules.update`, {
  //         schedule: payload.id,
  //       }),
  //       payload,
  //       {
  //         preserveScroll: true,
  //         onSuccess: () => {
  //           const index = employee.value.schedules.findIndex(
  //             (schedule) => schedule.id === payload.id
  //           )

  //           if (index !== -1) {
  //             employee.value.schedules[index] = {
  //               ...employee.value.schedules[index],
  //               ...payload,
  //             }
  //           }

  //           closeModal()
  //         },
  //       }
  //     )
  //   } else {
  //     router.post(
  //       route(`${prefix.value}.employees.schedules.store`, {
  //         employee: employeeId,
  //       }),
  //       payload,
  //       {
  //         preserveScroll: true,
  //         onSuccess: (pageResponse) => {
  //           if (pageResponse.props.employee?.schedules) {
  //             employee.value.schedules = pageResponse.props.employee.schedules
  //           }

  //           closeModal()
  //         },
  //       }
  //     )
  //   }
  // }
  const saveSchedule = () => {
    if (isSavingSchedule.value) return
    isSavingSchedule.value = true

    const employeeId = employee.value.id
    const payload = { ...form.value }

    if (!requiresTime(payload.status)) {
      payload.time_in = null
      payload.time_out = null
    }

    if (payload.id) {
      router.put(
        route(`${prefix.value}.employees.schedules.update`, { schedule: payload.id }),
        payload,
        {
          preserveScroll: true,
          onSuccess: () => {
            const index = employee.value.schedules.findIndex(s => s.id === payload.id)
            if (index !== -1) {
              employee.value.schedules[index] = { ...employee.value.schedules[index], ...payload }
            }
            closeModal()
          },
          onFinish: () => { isSavingSchedule.value = false },
        }
      )
    } else {
      router.post(
        route(`${prefix.value}.employees.schedules.store`, { employee: employeeId }),
        payload,
        {
          preserveScroll: true,
          onSuccess: (pageResponse) => {
            if (pageResponse.props.employee?.schedules) {
              employee.value.schedules = pageResponse.props.employee.schedules
            }
            closeModal()
          },
          onFinish: () => { isSavingSchedule.value = false },
        }
      )
    }
  }

  // const saveBatchSchedule = () => {
  //   const employeeId = employee.value.id
  //   const payload = { ...batchForm.value }

  //   if (!payload.start_date || !payload.end_date) {
  //     alert('Please select start date and end date.')
  //     return
  //   }

  //   if (payload.start_date > payload.end_date) {
  //     alert('End date must not be earlier than start date.')
  //     return
  //   }

  //   if (!requiresTime(payload.status)) {
  //     payload.time_in = null
  //     payload.time_out = null
  //   }

  //   router.post(
  //     route(`${prefix.value}.employees.schedules.batchStore`, {
  //       employee: employeeId,
  //     }),
  //     payload,
  //     {
  //       preserveScroll: true,
  //       onSuccess: (pageResponse) => {
  //         if (pageResponse.props.employee?.schedules) {
  //           employee.value.schedules = pageResponse.props.employee.schedules
  //         }

  //         closeBatchModal()
  //       },
  //     }
  //   )
  // }
  const saveBatchSchedule = () => {
    if (isSavingBatch.value) return

    const employeeId = employee.value.id
    const payload = { ...batchForm.value }

    if (!payload.start_date || !payload.end_date) {
      alert('Please select start date and end date.')
      return
    }

    if (payload.start_date > payload.end_date) {
      alert('End date must not be earlier than start date.')
      return
    }

    if (!requiresTime(payload.status)) {
      payload.time_in = null
      payload.time_out = null
    }

    isSavingBatch.value = true

    router.post(
      route(`${prefix.value}.employees.schedules.batchStore`, { employee: employeeId }),
      payload,
      {
        preserveScroll: true,
        onSuccess: (pageResponse) => {
          if (pageResponse.props.employee?.schedules) {
            employee.value.schedules = pageResponse.props.employee.schedules
          }
          closeBatchModal()
        },
        onFinish: () => { isSavingBatch.value = false },
      }
    )
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

    showBatchModal,
    batchForm,
    openBatchModal,
    closeBatchModal,
    saveBatchSchedule,

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
    formatTime,
    requiresTime,
    showTimeRange,
    getSchedules,
    getScheduleByDate,
    hasSchedule,
    openExistingSchedule,
    scheduleBadgeClass,
    buildScheduleTitle,

    prevMonth,
    nextMonth,
    goBackToEmployees,

    isSavingSchedule,
    isSavingBatch,
  }
}