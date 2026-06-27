import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import useRolePrefix from '@/Composables/useRolePrefix'

export function useEmployeeAttendance() {
  const { prefix } = useRolePrefix()
  const page = usePage()
  const isLoading = ref(false)

  const employee = computed(() => page.props.employee ?? null)
  console.log(prefix.value)

  /* ================= CURRENT DATETIME ================= */
  const now = ref(new Date())
  let interval = null

  onMounted(() => {
    interval = setInterval(() => {
      now.value = new Date()
    }, 1000)
  })

  onUnmounted(() => {
    if (interval) clearInterval(interval)
  })

  const currentDateTime = computed(() => now.value.toLocaleString())

  /* ================= TOAST ================= */
  const toast = ref({
    show: false,
    message: '',
    type: 'success',
    title: null,
  })

  const showToast = (message, type = 'success', title = null) => {
    toast.value.message = message
    toast.value.type = type
    toast.value.title = title
    toast.value.show = true

    setTimeout(() => {
      toast.value.show = false
    }, type === 'error' ? 6000 : 4000) // give errors more time to read
  }

  // const showToast = (message, type = 'success') => {
  //   toast.value.message = message
  //   toast.value.type = type
  //   toast.value.show = true

  //   setTimeout(() => {
  //     toast.value.show = false
  //   }, 3000)
  // }

  /* ================= ATTENDANCE MODAL ================= */
  const showModal = ref(false)
  const selectedSchedule = ref({})

  const openModal = (schedule) => {
    selectedSchedule.value = schedule
    showModal.value = true
  }

  const closeModal = () => {
    showModal.value = false
    selectedSchedule.value = {}
  }

  /* ================= OT MODAL ================= */
  const showOtModal = ref(false)

  const otForm = ref({
    employee_schedule_id: null,
    ot_date: '',
    start_time: '',
    end_time: '',
    type: 'regular_day',
    remarks: '',
  })

  const resetOtForm = () => {
    otForm.value = {
      employee_schedule_id: null,
      ot_date: '',
      start_time: '',
      end_time: '',
      type: 'regular_day',
      remarks: '',
    }
  }

  const openOtModal = (schedule) => {
    otForm.value = {
      employee_schedule_id: schedule.id,
      ot_date: schedule.schedule_date,
      start_time: schedule.time_out ?? '',
      end_time: '',
      type: 'regular_day',
      remarks: '',
    }

    showOtModal.value = true
  }

  const closeOtModal = () => {
    showOtModal.value = false
    resetOtForm()
  }

  const computeTotalHours = () => {
    if (!otForm.value.start_time || !otForm.value.end_time) return '0.00'

    const start = new Date(`1970-01-01T${otForm.value.start_time}`)
    const end = new Date(`1970-01-01T${otForm.value.end_time}`)

    const diffMs = end - start
    const diffHrs = diffMs / (1000 * 60 * 60)

    return diffHrs > 0 ? diffHrs.toFixed(2) : '0.00'
  }

  const submitOt = async () => {
    const total_hours = computeTotalHours()

    const payload = {
      ...otForm.value,
      total_hours,
    }

    try {
      const response = await axios.post(
        route(`${prefix.value}.overtimes.store`),
        payload
      )

      showToast(response.data.message, 'success')
      closeOtModal()
    } catch (error) {
      showToast(error.response?.data?.message ?? 'Failed to file OT.', 'error')
    }
  }

  const scheduleOvertimes = computed(() => {
    if (!selectedSchedule.value?.id) return []

    return (
      employee.value?.overtimes?.filter(
        (ot) => ot.employee_schedule_id === selectedSchedule.value.id
      ) ?? []
    )
  })

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

  /* ================= CLOCK METHODS ================= */
  const updateSchedule = (updated) => {
    if (!employee.value?.schedules) return

    const index = employee.value.schedules.findIndex((s) => s.id === updated.id)

    if (index !== -1) {
      employee.value.schedules[index] = updated
    }
  }

  const clockIn = async (data = null) => {
    try {
      isLoading.value = true
      const config = {}

      if (data instanceof FormData) {
        config.headers = {
          'Content-Type': 'multipart/form-data',
        }
      }

      const response = await axios.post(
        route(`${prefix.value}.clockin`),
        data,
        config
      )

      updateSchedule(response.data.schedule)

      showToast(
        response.data.message,
        response.data.is_late ? 'warning' : 'success',
        response.data.is_late ? 'Clocked In (Late)' : 'Clock In Successful'
      )
    } catch (error) {
      console.error('Clock in error status:', error.response?.status)
      console.error('Clock in error data:', error.response?.data)

      showToast(
        error.response?.data?.message ?? 'Clock in failed. Please try again or contact IT support.',
        'error',
        'Clock In Failed'
      )
    } finally {
      isLoading.value = false
    }
  }

  const clockOut = async (data = null) => {
    try {
      isLoading.value = true
      const config = {}

      if (data instanceof FormData) {
        config.headers = {
          'Content-Type': 'multipart/form-data',
        }
      }

      const response = await axios.post(
        route(`${prefix.value}.clockout`),
        data,
        config
      )

      updateSchedule(response.data.schedule)
      showToast(response.data.message, 'success', 'Clock Out Successful')
    } catch (error) {
      showToast(
        error.response?.data?.message ?? 'Clock out failed. Please try again or contact IT support.',
        'error',
        'Clock Out Failed'
      )
    } finally {
      isLoading.value = false
    }
  }

  // const clockIn = async (data = null) => {
  //   try {
  //     isLoading.value = true
  //     const config = {}

  //     if (data instanceof FormData) {
  //       config.headers = {
  //         'Content-Type': 'multipart/form-data',
  //       }
  //     }

  //     const response = await axios.post(
  //       route(`${prefix.value}.clockin`),
  //       data,
  //       config
  //     )

  //     updateSchedule(response.data.schedule)
  //     showToast(response.data.message, 'success')
  //   } catch (error) {
  //     // 👇 add these logs
  //     console.error('Clock in error status:', error.response?.status)
  //     console.error('Clock in error data:', error.response?.data)
  //     console.error('Route used:', route(`${prefix.value}.clockin`))

  //     showToast(
  //       error.response?.data?.message ?? 'Clock in failed.',
  //       'error'
  //     )
  //   } finally {
  //     isLoading.value = false
  //   }
  // }

  // try {
  //     const response = await axios.post(
  //       route(`${prefix.value}.clockin`),
  //       data,
  //       {
  //         headers: {
  //           'Content-Type': 'multipart/form-data',
  //         },
  //       }
  //     )

  //     updateSchedule(response.data.schedule)
  //     showToast(response.data.message, 'success')
  //   } catch (error) {
  //     showToast(error.response?.data?.message ?? 'Clock in failed.', 'error')
  //   }
  // }

  // const clockOut = async (data = null) => {
  //   try {
  //     isLoading.value = true
  //     const config = {}

  //     // ✅ Only set multipart if there's a photo
  //     if (data instanceof FormData) {
  //       config.headers = {
  //         'Content-Type': 'multipart/form-data',
  //       }
  //     }

  //     const response = await axios.post(
  //       route(`${prefix.value}.clockout`),
  //       data,
  //       config
  //     )

  //     updateSchedule(response.data.schedule)
  //     showToast(response.data.message, 'success')
  //   } catch (error) {
  //     showToast(
  //       error.response?.data?.message ?? 'Clock out failed.',
  //       'error'
  //     )
  //   } finally {
  //     isLoading.value = false
  //   }
  // }

  /* ================= LOGOUT ================= */
  const logout = async () => {
    await axios.post(route('logout'))
    window.location.href = '/'
  }

  /* ================= DATE HELPERS ================= */
  const isToday = (dateString) => {
    const today = new Date().toLocaleDateString('en-CA')
    return today === dateString
  }

  const formatDate = (dateString) => {
    return dateString
  }

  return {
    employee,
    currentDateTime,
    isLoading,

    toast,
    showToast,

    showModal,
    selectedSchedule,
    openModal,
    closeModal,

    showOtModal,
    otForm,
    openOtModal,
    closeOtModal,
    submitOt,
    computeTotalHours,
    scheduleOvertimes,
    formatOtType,

    clockIn,
    clockOut,
    logout,

    isToday,
    formatDate,
  }
}