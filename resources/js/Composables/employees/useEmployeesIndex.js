import { ref, computed, reactive } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import useRolePrefix from '@/Composables/useRolePrefix'
import axios from 'axios'

export function useEmployeesIndex(props) {
  const { prefix } = useRolePrefix()

  const employees = computed(() => props.employees ?? [])
  const roles = computed(() => props.roles ?? {})
  const branches = computed(() => props.branches ?? [])
  
  console.log(roles.value)

  const isLoading = ref(false)
  const isLoadingOT = ref(false)

  const search = ref('')
  const showModal = ref(false)
  const showAccountModal = ref(false)
  const showEmploymentModal = ref(false)
  const show201FileModal = ref(false)
  const showDTRModal = ref(false)
  const showOtModal = ref(false)
  const showOtDetailsModal = ref(false)
  const editingOtId = ref(null)

  const isEditing = ref(false)
  const selectedEmployee = ref(null)
  const selectedEmployment = ref(null)
  const selectedOt = ref(null)

  const schedules = ref([])
  const payrollStartDate = ref('')
  const payrollEndDate = ref('')
  const systemAccount = ref({})
  const selectedBranch = ref('')
  const showAddEmploymentModal = ref(false)

  const addEmployment = () => {
    showAddEmploymentModal.value = true
    showEmploymentModal.value = false
  }

  const form = ref({
    id: null,
    first_name: '',
    last_name: '',
    middle_name: '',
    email: '',
    contact_number: '',
    birth_date: '',
    gender: '',
    address: '',
    status: 'active',
    access: 0,
  })

  const employmentForm = ref({
    position: '',
    department: '',
    hire_date: '',
    salary: '',
    daily_rate: '',
  })

  const newEmployment = ref({
    position: '',
    department: '',
    hire_date: '',
    salary: '',
    daily_rate: '',
  })

  const otForm = ref({
    employee_schedule_id: null,
    ot_date: '',
    start_time: '',
    end_time: '',
    type: 'regular_day',
    remarks: '',
    employee_id: '',
  })

  const documentTypes = [
    // 'Appointment/Contract',
    // 'SSS',
    // 'TIN',
    // 'PhilHealth',
    // 'Clearances',
    // 'Medical Exam',
    // 'Diploma/Birth Cert',
    'Employee 201 File',
  ]

  const employeeDocuments = reactive({})
  const fileInputs = reactive({})

  // const filteredEmployees = computed(() => {
  //   if (!search.value) return employees.value

  //   const keyword = search.value.toLowerCase()

  //   return employees.value.filter((employee) =>
  //     `${employee.first_name} ${employee.last_name}`.toLowerCase().includes(keyword) ||
  //     (employee.employee_code || '').toLowerCase().includes(keyword)
  //   )
  // })
  const filteredEmployees = computed(() => {
    return employees.value.filter((employee) => {
      const keyword = search.value.toLowerCase()

      const matchesSearch = !search.value ||
        `${employee.first_name} ${employee.last_name}`.toLowerCase().includes(keyword) ||
        (employee.employee_code || '').toLowerCase().includes(keyword)

      const matchesBranch = !selectedBranch.value ||
        employee.branch_id === selectedBranch.value

      return matchesSearch && matchesBranch
    })
  })

  const displayedSchedules = computed(() => {
    if (!payrollStartDate.value || !payrollEndDate.value) return schedules.value

    const start = new Date(payrollStartDate.value)
    const end = new Date(payrollEndDate.value)

    return schedules.value.filter((schedule) => {
      const schDate = new Date(schedule.schedule_date)
      return schDate >= start && schDate <= end
    })
  })

  const totalLate = computed(() => {
    let total = 0

    displayedSchedules.value.forEach((schedule) => {
      const val = calculateLate(schedule.time_in, schedule.actual_time_in)
      const [h, m] = val.split(':').map(Number)
      total += h * 60 + m
    })

    return `${Math.floor(total / 60)}:${total % 60}`
  })

  const totalUndertime = computed(() => {
    let total = 0

    displayedSchedules.value.forEach((schedule) => {
      const val = calculateUndertime(schedule)
      const [h, m] = val.split(':').map(Number)
      total += h * 60 + m
    })

    return `${Math.floor(total / 60)}:${total % 60}`
  })

  const totalScheduledDays = computed(() => {
    return displayedSchedules.value.filter(
      (schedule) => schedule.status === 'Scheduled'
    ).length
  })

  const totalDaysWithActual = computed(() => {
    return displayedSchedules.value.filter(
      (schedule) =>
        schedule.status === 'Scheduled' &&
        schedule.actual_time_in &&
        schedule.actual_time_out
    ).length
  })

  const resetEmployeeForm = () => {
    form.value = {
      id: null,
      first_name: '',
      last_name: '',
      middle_name: '',
      email: '',
      contact_number: '',
      birth_date: '',
      gender: '',
      address: '',
      status: 'active',
      access: '',
    }
  }

  const resetEmploymentForm = () => {
    employmentForm.value = {
      position: '',
      department: '',
      hire_date: '',
      salary: '',
      daily_rate: '',
    }
  }

  const resetNewEmployment = () => {
    
    showAddEmploymentModal.value = false
    newEmployment.value = {
      position: '',
      department: '',
      hire_date: '',
      salary: '',
      daily_rate: '',
    }
  }

  const resetOtForm = () => {
    otForm.value = {
      employee_schedule_id: null,
      ot_date: '',
      start_time: '',
      end_time: '',
      type: 'regular_day',
      remarks: '',
      employee_id: '',
    }
  }

  const clearEmployeeDocuments = () => {
    for (const key in employeeDocuments) {
      delete employeeDocuments[key]
    }
  }

  const createEmployee = () => {
    isEditing.value = false
    resetEmployeeForm()
    showModal.value = true
  }

  const viewSystemAccount = (employee) => {
    showAccountModal.value = true
    systemAccount.value = employee
  }

  const editEmployee = (employee) => {
    isEditing.value = true
    form.value = { ...employee }
    showModal.value = true
  }

  const closeEmployeeModal = () => {
    showModal.value = false
    resetEmployeeForm()
  }

  const submit = () => {
    if (isEditing.value) {
      router.put(`/${prefix.value}/employees/edit/${form.value.id}`, form.value, {
        onSuccess: () => closeEmployeeModal(),
      })
    } else {
      router.post(`/${prefix.value}/employees/store`, form.value, {
        onSuccess: () => closeEmployeeModal(),
      })
    }
  }

  const deleteEmployee = (employee) => {
    if (confirm(`Delete ${employee.first_name} ${employee.last_name}?`)) {
      router.delete(`/${prefix.value}/employees/delete/${employee.id}`)
    }
  }

  const viewEmployment = (employee) => {
    selectedEmployee.value = employee
    resetNewEmployment()
    resetEmploymentForm()
    selectedEmployment.value = null
    clearEmployeeDocuments()
    showEmploymentModal.value = true
  }

  const closeEmploymentModal = () => {
    showEmploymentModal.value = false
    selectedEmployment.value = null
    resetEmploymentForm()
    resetNewEmployment()
    clearEmployeeDocuments()
  }

  const loadEmploymentDocuments = (employment) => {
    clearEmployeeDocuments()

    ;(employment.documents ?? []).forEach((doc) => {
      employeeDocuments[doc.document_type] = doc
    })
  }

  const selectEmployment = (employment) => {
    selectedEmployment.value = employment
    employmentForm.value = { ...employment }
    loadEmploymentDocuments(employment)
  }

  const editEmployment = (employment) => {
    selectedEmployment.value = employment
    employmentForm.value = { ...employment }
    loadEmploymentDocuments(employment)
  }

  const cancelEditEmployment = () => {
    selectedEmployment.value = null
    resetEmploymentForm()
    clearEmployeeDocuments()
  }

  const updateEmployment = () => {
    router.put(
      `/${prefix.value}/employees/${selectedEmployee.value.id}/employment/${selectedEmployment.value.id}/update`,
      employmentForm.value,
      {
        onSuccess: () => {
          selectedEmployment.value = null
          resetEmploymentForm()
          closeEmploymentModal()
          clearEmployeeDocuments()
        },
      }
    )
  }

  const saveEmployment = () => {
    router.post(
      `/${prefix.value}/employees/${selectedEmployee.value.id}/employment`,
      newEmployment.value,
      {
        onSuccess: () => {
          resetNewEmployment()
        },
      }
    )
  }

  const getDocument = (type) => employeeDocuments[type] ?? null

  const handleFileUpload = async (type, event) => {
    const file = event.target.files?.[0]
    if (!file || !selectedEmployee.value || !selectedEmployment.value) return

    const formData = new FormData()
    formData.append('file', file)
    formData.append('document_type', type)

    try {
      const { data } = await axios.post(
        `/${prefix.value}/employees/${selectedEmployee.value.id}/employment/${selectedEmployment.value.id}/documents/upload`,
        formData
      )

      if (data.success) {
        employeeDocuments[type] = data.document

        if (!selectedEmployment.value.documents) {
          selectedEmployment.value.documents = []
        }

        const index = selectedEmployment.value.documents.findIndex(
          (doc) => doc.document_type === type
        )

        if (index >= 0) {
          selectedEmployment.value.documents[index] = data.document
        } else {
          selectedEmployment.value.documents.push(data.document)
        }
      }
    } catch (error) {
      console.error(error)
    }
  }

  const removeDocument = async (type) => {
      const doc = getDocument(type)
      if (!doc) return
      if (!confirm(`Remove ${type}?`)) return

      try {
          const { data } = await axios.delete(
              `/${prefix.value}/employees/documents/${doc.id}/delete`
          )

          if (data.success) {
              delete employeeDocuments[type]
          }
      } catch (error) {
          console.error(error)
      }
  }

  const view201File = (employee) => {
    if (!employee) return
    selectedEmployee.value = employee
    show201FileModal.value = true
  }

  const close201FileModal = () => {
    show201FileModal.value = false
  }

  const closeAccountModal = () => {
    showAccountModal.value = false
  }

  const openDTR = async (employee) => {
    selectedEmployee.value = employee
    showDTRModal.value = true

    try {
      isLoading.value = true

      const url = route(`${prefix.value}.employees.dtr`, { employee: employee.id })
      const { data } = await axios.get(url)

      schedules.value = data.schedules.map((schedule) => ({
        ...schedule,
        actual_time_in: schedule.actual_time_in || '',
        actual_time_out: schedule.actual_time_out || '',
      }))
    } catch (error) {
      console.error('Failed to fetch schedules:', error)
      schedules.value = []
    } finally {
      isLoading.value = false
    }
  }

  const closeDTRModal = () => {
    showDTRModal.value = false
    schedules.value = []
    payrollStartDate.value = ''
    payrollEndDate.value = ''
  }

  const calculateLate = (scheduled, actual) => {
    if (!scheduled || !actual) return '0:00'

    const [sh, sm] = scheduled.split(':').map(Number)
    const [ah, am] = actual.split(':').map(Number)

    const lateMins = ah * 60 + am - (sh * 60 + sm)

    if (lateMins <= 0) return '0:00'
    return `${Math.floor(lateMins / 60)}:${lateMins % 60}`
  }

  const timeToMinutes = (value) => {
    if (!value) return null

    const [h, m] = String(value).split(':').map(Number)
    if (Number.isNaN(h) || Number.isNaN(m)) return null

    return h * 60 + m
  }

  const calculateUndertime = (schedule) => {
    const schedIn = timeToMinutes(schedule.time_in)
    const schedOut = timeToMinutes(schedule.time_out)
    const actualIn = timeToMinutes(schedule.actual_time_in)
    const actualOut = timeToMinutes(schedule.actual_time_out)

    if (schedIn === null || schedOut === null || actualIn === null || actualOut === null) {
      return '0:00'
    }

    // Overnight shifts (e.g. 21:00 -> 05:00) are rolled past midnight.
    const shiftEnd = schedOut <= schedIn ? schedOut + 24 * 60 : schedOut
    const workedEnd = actualOut <= actualIn ? actualOut + 24 * 60 : actualOut

    const scheduledDuration = shiftEnd - schedIn
    const workedDuration = workedEnd - actualIn
    const lateMinutes = Math.max(0, actualIn - schedIn)

    // Charged time is the shortfall between the scheduled shift length and the
    // time actually worked. Early arrival is credited and time already counted
    // as late is not charged again, so an employee who works the full duration
    // (even if they clock in early) is not flagged as undertime.
    const undertimeMins = Math.max(0, scheduledDuration - workedDuration - lateMinutes)

    if (undertimeMins <= 0) return '0:00'
    return `${Math.floor(undertimeMins / 60)}:${undertimeMins % 60}`
  }

  const saveDTR = async () => {
    try {
      const payload = { schedules: schedules.value }
      const postUrl = route(`${prefix.value}.employees.dtr.update`, {
        employee: selectedEmployee.value.id,
      })

      await axios.post(postUrl, payload)
      alert('DTR saved successfully!')
    } catch (error) {
      console.error('Failed to save DTR:', error)
      alert('Error saving DTR.')
    }
  }

  const updateDTR = async () => {
    try {
      const payload = { schedules: schedules.value }
      const putUrl = route(`${prefix.value}.employees.dtr.updateDtr`, {
        employee: selectedEmployee.value.id,
      })

      await axios.put(putUrl, payload)
      alert('DTR updated successfully!')
    } catch (error) {
      console.error('Failed to update DTR:', error)
      alert('Error updating DTR.')
    }
  }

  const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleDateString('en-CA')
  }

  const rowClass = (schedule) => {
    if (schedule.status === 'Day Off') {
      return 'bg-gray-100 text-gray-500'
    }

    if (schedule.status === 'Absent') {
      return 'bg-red-50 text-red-700'
    }

    if (
      schedule.status === 'Scheduled' &&
      calculateLate(schedule.time_in, schedule.actual_time_in) === '0:00' &&
      calculateUndertime(schedule) === '0:00' &&
      schedule.actual_time_in &&
      schedule.actual_time_out
    ) {
      return 'bg-green-50 text-green-700'
    }

    return 'hover:bg-gray-50'
  }

  const lateClass = (schedule) => {
    return calculateLate(schedule.time_in, schedule.actual_time_in) !== '0:00'
      ? 'text-red-700'
      : 'text-gray-600'
  }

  const undertimeClass = (schedule) => {
    return calculateUndertime(schedule) !== '0:00'
      ? 'text-orange-700'
      : 'text-gray-600'
  }

  const filterSchedules = async () => {
    if (!payrollStartDate.value || !payrollEndDate.value) {
      alert('Please select both start and end dates.')
      return
    }

    if (new Date(payrollStartDate.value) > new Date(payrollEndDate.value)) {
      alert('Start date cannot be after end date.')
      return
    }

    try {
      const payload = {
        start_date: payrollStartDate.value,
        end_date: payrollEndDate.value,
      }

      const postUrl = route(`${prefix.value}.employees.dtr.fetch`, {
        employee: selectedEmployee.value.id,
      })

      const { data } = await axios.post(postUrl, payload)

      schedules.value = data.schedules
      // if you need these elsewhere:
      // employee.value = data.employee
      // period.value = data.period
    } catch (error) {
      console.error('Failed to fetch DTR:', error)
      alert('Error fetching DTR.')
    }
  }

  const printPayroll = () => {
    if (!payrollStartDate.value || !payrollEndDate.value) {
      alert('Please select both start and end dates.')
      return
    }

    const url = route(`${prefix.value}.employees.payroll.attendance.pdf`, {
      employee: selectedEmployee.value.id,
      start_date: payrollStartDate.value,
      end_date: payrollEndDate.value,
    })

    window.open(url, '_blank')
  }

  const formatDateOnly = (value) => {
    if (!value) return ''

    const match = String(value).match(/^(\d{4}-\d{2}-\d{2})/)
    if (match) return match[1]

    const parsed = new Date(value)
    if (Number.isNaN(parsed.getTime())) return ''

    const year = parsed.getUTCFullYear()
    const month = String(parsed.getUTCMonth() + 1).padStart(2, '0')
    const day = String(parsed.getUTCDate()).padStart(2, '0')

    return `${year}-${month}-${day}`
  }

  const formatTimeForInput = (value) => {
    if (!value) return ''

    const match = String(value).match(/(\d{1,2}):(\d{2})/)
    if (!match) return ''

    return `${String(match[1]).padStart(2, '0')}:${match[2]}`
  }

  const openOtModal = (schedule) => {
    editingOtId.value = null
    otForm.value.employee_schedule_id = schedule.id
    otForm.value.ot_date = formatDateOnly(schedule.schedule_date)
    otForm.value.start_time = formatTimeForInput(schedule.time_out)
    otForm.value.end_time = ''
    otForm.value.type = 'regular_day'
    otForm.value.remarks = ''
    otForm.value.employee_id = schedule.employee_id
    showOtModal.value = true
  }

  const openEditOtModal = (ot) => {
    if (!ot) return

    editingOtId.value = ot.id
    otForm.value.employee_schedule_id = ot.employee_schedule_id
    otForm.value.ot_date = formatDateOnly(ot.ot_date)
    otForm.value.start_time = formatTimeForInput(ot.start_time)
    otForm.value.end_time = formatTimeForInput(ot.end_time)
    otForm.value.type = ot.type ?? 'regular_day'
    otForm.value.remarks = ot.remarks ?? ''
    otForm.value.employee_id = ot.employee_id

    showOtDetailsModal.value = false
    showOtModal.value = true
  }

  const closeOtModal = () => {
    showOtModal.value = false
    editingOtId.value = null
    resetOtForm()
  }

  // ─────────────────────────────────────────────────────────────────────────
  // BACKUP (original) — kept for reference before the 2026-10-17 refactor.
  // It returned 0.00 for overtime that crosses midnight (end <= start).
  // ─────────────────────────────────────────────────────────────────────────
  // const computeTotalHours = () => {
  //   if (!otForm.value.start_time || !otForm.value.end_time) return '0.00'
  //
  //   const start = new Date(`1970-01-01T${otForm.value.start_time}`)
  //   const end = new Date(`1970-01-01T${otForm.value.end_time}`)
  //
  //   const diffMs = end - start
  //   const diffHrs = diffMs / (1000 * 60 * 60)
  //
  //   return diffHrs > 0 ? diffHrs.toFixed(2) : '0.00'
  // }
  // ─────────────────────────────────────────────────────────────────────────

  const computeTotalHours = () => {
    const startMins = timeToMinutes(otForm.value.start_time)
    const endMins = timeToMinutes(otForm.value.end_time)

    if (startMins === null || endMins === null || endMins === startMins) {
      return '0.00'
    }

    // Overnight OT (e.g. 22:30 -> 00:30) crosses midnight: the end time is
    // treated as next day so the duration stays positive.
    const totalMins = endMins < startMins
      ? endMins + 24 * 60 - startMins
      : endMins - startMins

    return (totalMins / 60).toFixed(2)
  }

  const otTotalHours = computed(() => computeTotalHours())

  const otIsOvernight = computed(() => {
    const startMins = timeToMinutes(otForm.value.start_time)
    const endMins = timeToMinutes(otForm.value.end_time)

    return startMins !== null && endMins !== null && endMins < startMins
  })

  const selectedOtIsOvernight = computed(() => {
    const startMins = timeToMinutes(selectedOt.value?.start_time)
    const endMins = timeToMinutes(selectedOt.value?.end_time)

    return startMins !== null && endMins !== null && endMins < startMins
  })

  const selectedOtEndDateTime = computed(() => {
    const ot = selectedOt.value
    if (!ot) return ''

    const baseDate = formatDateOnly(ot.ot_date)
    const endTime = formatTimeForInput(ot.end_time)

    if (!baseDate || !endTime) return ''

    if (!selectedOtIsOvernight.value) {
      return `${baseDate} ${endTime}`
    }

    const nextDay = new Date(`${baseDate}T00:00:00Z`)
    nextDay.setUTCDate(nextDay.getUTCDate() + 1)

    return `${nextDay.toISOString().slice(0, 10)} ${endTime}`
  })

  const submitOt = async () => {
    const total_hours = computeTotalHours()

    const payload = {
      ...otForm.value,
      total_hours,
    }

    try {
      isLoadingOT.value = true

      const isEdit = Boolean(editingOtId.value)

      const response = isEdit
        ? await axios.put(
            route(`${prefix.value}.overtimes.update`, editingOtId.value),
            payload
          )
        : await axios.post(
            route(`${prefix.value}.overtimes.store`),
            payload
          )

      const overtime = response.data.overtime

      const schedule = schedules.value.find(
        (item) => item.id === payload.employee_schedule_id
      )

      if (schedule) {
        schedule.overtime = { ...overtime }
      }

      if (selectedOt.value?.id === overtime.id) {
        selectedOt.value = { ...overtime }
      }

      closeOtModal()
    } catch (error) {
      console.error(error)
    } finally {
      isLoadingOT.value = false
    }
  }

  const viewOtDetails = (ot) => {
    selectedOt.value = ot
    showOtDetailsModal.value = true
  }

  const selectedOtAmount = computed(() => {
    if (!selectedOt.value) return 0

    const dailyRate = Number(selectedEmployee.value?.latest_employment?.daily_rate || 0)
    const hours = Number(selectedOt.value.total_hours || 0)

    return (dailyRate / 8) * 1.25 * hours
  })

  const closeOtDetailsModal = () => {
    selectedOt.value = null
    showOtDetailsModal.value = false
  }

  return {
    prefix,
    employees,

    isLoading,
    isLoadingOT,

    search,
    filteredEmployees,

    showModal,
    showAccountModal,
    showEmploymentModal,
    show201FileModal,
    showDTRModal,
    showOtModal,
    showOtDetailsModal,

    isEditing,
    selectedEmployee,
    selectedEmployment,
    selectedOt,
    selectedOtAmount,
    selectedOtIsOvernight,
    selectedOtEndDateTime,

    schedules,
    displayedSchedules,
    payrollStartDate,
    payrollEndDate,

    form,
    systemAccount,
    employmentForm,
    newEmployment,
    otForm,

    documentTypes,
    employeeDocuments,
    fileInputs,

    totalLate,
    totalUndertime,
    totalScheduledDays,
    totalDaysWithActual,

    createEmployee,
    editEmployee,
    viewSystemAccount,
    closeEmployeeModal,
    submit,
    deleteEmployee,

    viewEmployment,
    closeEmploymentModal,
    closeAccountModal,
    selectEmployment,
    editEmployment,
    cancelEditEmployment,
    updateEmployment,
    saveEmployment,

    getDocument,
    handleFileUpload,
    removeDocument,

    view201File,
    close201FileModal,

    openDTR,
    closeDTRModal,
    saveDTR,
    updateDTR,

    calculateLate,
    calculateUndertime,
    formatDate,
    formatDateOnly,
    rowClass,
    lateClass,
    undertimeClass,
    filterSchedules,
    printPayroll,

    openOtModal,
    openEditOtModal,
    closeOtModal,
    submitOt,
    editingOtId,
    otTotalHours,
    otIsOvernight,
    viewOtDetails,
    closeOtDetailsModal,

    roles,
    branches,
    selectedBranch,
    addEmployment,
    showAddEmploymentModal
  }
}