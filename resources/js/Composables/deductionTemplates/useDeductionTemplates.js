import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import useRolePrefix from '@/Composables/useRolePrefix'

export function useDeductionTemplates() {
  const { prefix } = useRolePrefix()
  const page = usePage()

  const employees = computed(() => page.props.employees ?? [])
  const deductionTypes = computed(() => page.props.deduction_types ?? [])
  const templates = computed(() => page.props.templates ?? [])

  const search = ref('')
  const showModal = ref(false)
  const isEditing = ref(false)
  const editingId = ref(null)

  const employeeSearch = ref('')
  const showEmployeeDropdown = ref(false)
  const selectedEmployee = ref(null)

  const details = ref([])

  const CUTOFFS = [
    { value: '15th', label: '15th Cutoff', period: '26th – 10th' },
    { value: '30th', label: '30th Cutoff', period: '11th – 25th' },
  ]

  const cutoffMeta = (cutoff) => {
    return CUTOFFS.find((item) => item.value === cutoff) ?? CUTOFFS[0]
  }

  const cutoffLabel = (cutoff) => {
    const meta = cutoffMeta(cutoff)
    return `${meta.label} (${meta.period})`
  }

  const groupedDetails = (templateDetails = []) => {
    return CUTOFFS.map((cutoff) => ({
      ...cutoff,
      items: templateDetails.filter(
        (detail) => (detail.cutoff ?? '15th') === cutoff.value
      ),
    })).filter((group) => group.items.length > 0)
  }

  const filteredTemplates = computed(() => {
    if (!search.value) return templates.value

    const keyword = search.value.toLowerCase()

    return templates.value.filter((template) => {
      const name = `${template.employee?.first_name ?? ''} ${template.employee?.last_name ?? ''}`.toLowerCase()
      const code = (template.employee?.employee_code ?? '').toLowerCase()

      return name.includes(keyword) || code.includes(keyword)
    })
  })

  const filteredEmployees = computed(() => {
    const keyword = employeeSearch.value.trim().toLowerCase()

    const selectedIds = templates.value
      .filter((template) => template.id !== editingId.value)
      .map((template) => template.employee_id)

    return employees.value.filter((employee) => {
      if (selectedIds.includes(employee.id)) return false
      if (!keyword) return true

      const name = `${employee.first_name ?? ''} ${employee.last_name ?? ''}`.toLowerCase()
      const code = (employee.employee_code ?? '').toLowerCase()

      return name.includes(keyword) || code.includes(keyword)
    })
  })

  const employeeName = (employee) => {
    if (!employee) return '—'
    return `${employee.first_name ?? ''} ${employee.last_name ?? ''}`.trim()
  }

  const selectEmployee = (employee) => {
    selectedEmployee.value = employee
    employeeSearch.value = employeeName(employee)
    showEmployeeDropdown.value = false
  }

  const addDetailRow = (cutoff = '15th') => {
    details.value.push({ deduction_type_id: '', cutoff, default_value: '' })
  }

  const isDeductionTypeSelected = (typeId, currentIndex) => {
    const currentCutoff = details.value[currentIndex]?.cutoff

    return details.value.some(
      (detail, index) =>
        index !== currentIndex &&
        detail.cutoff === currentCutoff &&
        detail.deduction_type_id === typeId
    )
  }

  const removeDetailRow = (index) => {
    details.value.splice(index, 1)
  }

  const resetForm = () => {
    isEditing.value = false
    editingId.value = null
    selectedEmployee.value = null
    employeeSearch.value = ''
    showEmployeeDropdown.value = false
    details.value = []
  }

  const openCreate = () => {
    resetForm()
    addDetailRow()
    showModal.value = true
  }

  const openEdit = (template) => {
    resetForm()

    isEditing.value = true
    editingId.value = template.id
    selectedEmployee.value = template.employee ?? null
    employeeSearch.value = employeeName(template.employee)

    details.value = (template.details ?? []).map((detail) => ({
      deduction_type_id: detail.deduction_type_id,
      cutoff: detail.cutoff ?? '15th',
      default_value: detail.default_value,
    }))

    if (details.value.length === 0) addDetailRow()

    showModal.value = true
  }

  const closeModal = () => {
    showModal.value = false
    resetForm()
  }

  const saveTemplate = () => {
    if (!selectedEmployee.value) {
      alert('Please select an employee.')
      return
    }

    const payload = {
      employee_id: selectedEmployee.value.id,
      details: details.value
        .filter((detail) => detail.deduction_type_id)
        .map((detail) => ({
          deduction_type_id: detail.deduction_type_id,
          cutoff: detail.cutoff ?? '15th',
          default_value: detail.default_value || 0,
        })),
    }

    const options = { onSuccess: () => closeModal() }

    if (isEditing.value) {
      router.put(route(`${prefix.value}.deduction-templates.update`, { employeeDeductionTemplate: editingId.value }), payload, options)
    } else {
      router.post(route(`${prefix.value}.deduction-templates.store`), payload, options)
    }
  }

  const deleteTemplate = (template) => {
    const name = employeeName(template.employee)

    if (confirm(`Delete deduction template for ${name}?`)) {
      router.delete(route(`${prefix.value}.deduction-templates.destroy`, { employeeDeductionTemplate: template.id }))
    }
  }

  return {
    employees,
    deductionTypes,
    templates,
    search,
    filteredTemplates,

    showModal,
    isEditing,
    editingId,
    employeeSearch,
    showEmployeeDropdown,
    selectedEmployee,
    filteredEmployees,
    details,
    CUTOFFS,
    cutoffLabel,
    groupedDetails,

    employeeName,
    selectEmployee,
    addDetailRow,
    removeDetailRow,
    isDeductionTypeSelected,
    openCreate,
    openEdit,
    closeModal,
    saveTemplate,
    deleteTemplate,
  }
}
