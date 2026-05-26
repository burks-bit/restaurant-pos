import axios from 'axios'
import { ref, computed, watch } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

export function useTableAdmission() {
  const page = usePage()

  const tablesState = ref(page.props.tables ?? [])
  const head_prices = computed(() => page.props.head_prices ?? [])

  const search = ref('')
  const admissionMode = ref('single')
  const showAssignModal = ref(false)

  const selectedSlot = ref(null)
  const selectedParent = ref(null)

  watch(
    () => page.props.tables,
    (newTables) => {
      tablesState.value = newTables ?? []
    },
    { deep: true }
  )

  const normalizeStatus = (status) => {
    return String(status ?? '').trim().toLowerCase()
  }

  const isChildOccupied = (child) => {
    const status = normalizeStatus(child?.status)
    return status === 'occupied' || status === 'open'
  }

  const normalizedHeadPrices = computed(() => {
    return (head_prices.value || [])
      .filter((rule) => Number(rule.is_active) === 1)
      .map((rule) => ({
        ...rule,
        id: Number(rule.id),
        price: Number(rule.price ?? 0),
        min_age: rule.min_age != null ? Number(rule.min_age) : null,
        max_age: rule.max_age != null ? Number(rule.max_age) : null,
        min_height: rule.min_height != null ? Number(rule.min_height) : null,
        max_height: rule.max_height != null ? Number(rule.max_height) : null,
      }))
  })

  const buildRuleCounts = () => {
    return normalizedHeadPrices.value.reduce((acc, rule) => {
      acc[rule.id] = 0
      return acc
    }, {})
  }

  const singleForm = ref({
    table_id: null,
    customer_name: '',
    pax: 1,
    rule_id: null,
    remarks: '',
  })

  const groupForm = ref({
    parent_table_id: null,
    customer_name: '',
    remarks: '',
    rule_counts: {},
  })

  watch(
    normalizedHeadPrices,
    (rules) => {
      const nextRuleCounts = {}

      rules.forEach((rule) => {
        nextRuleCounts[rule.id] = Number(groupForm.value.rule_counts?.[rule.id] ?? 0)
      })

      groupForm.value.rule_counts = nextRuleCounts

      if (
        singleForm.value.rule_id != null &&
        !rules.some((rule) => rule.id === Number(singleForm.value.rule_id))
      ) {
        singleForm.value.rule_id = rules[0]?.id ?? null
      }
    },
    { immediate: true }
  )

  const filteredTables = computed(() => {
    if (!search.value) return tablesState.value

    const keyword = search.value.toLowerCase()

    return tablesState.value.filter((table) => {
      const parentMatch = String(table.name ?? '').toLowerCase().includes(keyword)

      const childMatch = (table.children || []).some((child) => {
        return (
          String(child.name ?? '').toLowerCase().includes(keyword) ||
          normalizeStatus(child.status).includes(keyword) ||
          String(child.customer_name ?? '').toLowerCase().includes(keyword)
        )
      })

      return parentMatch || childMatch
    })
  })

  const totalChildrenCount = (table) => {
    return table.children?.length ?? 0
  }

  const occupiedChildrenCount = (table) => {
    return (table.children || []).filter((child) => isChildOccupied(child)).length
  }

  const availableChildrenCount = (table) => {
    return (table.children || []).filter((child) => !isChildOccupied(child)).length
  }

  const getRemainingCapacity = (child) => {
    const capacity = Number(child?.capacity ?? 1)
    const guestCount = Number(child?.guest_count ?? 0)

    return Math.max(capacity - guestCount, 0)
  }

  const availableChildrenCapacity = (table) => {
    return (table.children || []).reduce((sum, child) => {
      if (isChildOccupied(child)) {
        return sum
      }

      return sum + getRemainingCapacity(child)
    }, 0)
  }

  const selectedGroupParent = computed(() => {
    const parentId = Number(groupForm.value.parent_table_id)
    if (!parentId) return null

    return tablesState.value.find((table) => Number(table.id) === parentId) ?? null
  })

  const selectedGroupParentAvailableSlots = computed(() => {
    if (!selectedGroupParent.value) return 0
    return availableChildrenCapacity(selectedGroupParent.value)
  })

  const groupTotalPax = computed(() => {
    return Object.values(groupForm.value.rule_counts || {}).reduce((sum, qty) => {
      return sum + Number(qty || 0)
    }, 0)
  })

  const groupEstimatedTotal = computed(() => {
    return normalizedHeadPrices.value.reduce((sum, rule) => {
      const qty = Number(groupForm.value.rule_counts?.[rule.id] || 0)
      return sum + qty * Number(rule.price || 0)
    }, 0)
  })

  const selectedSingleRule = computed(() => {
    return normalizedHeadPrices.value.find(
      (rule) => rule.id === Number(singleForm.value.rule_id)
    ) ?? null
  })

  const singleSelectedPrice = computed(() => {
    return Number(selectedSingleRule.value?.price || 0)
  })

  const getRuleDescription = (rule) => {
    const parts = []

    if (rule.min_age != null || rule.max_age != null) {
      if (rule.min_age != null && rule.max_age != null) {
        parts.push(`Age ${rule.min_age} - ${rule.max_age}`)
      } else if (rule.min_age != null) {
        parts.push(`Age ${rule.min_age} and above`)
      } else if (rule.max_age != null) {
        parts.push(`Age up to ${rule.max_age}`)
      }
    }

    if (rule.min_height != null || rule.max_height != null) {
      if (rule.min_height != null && rule.max_height != null) {
        parts.push(`Height ${rule.min_height}ft - ${rule.max_height}ft`)
      } else if (rule.min_height != null) {
        parts.push(`Height ${rule.min_height}ft and above`)
      } else if (rule.max_height != null) {
        parts.push(`Height up to ${rule.max_height}ft`)
      }
    }

    if (!parts.length) {
      return 'No condition set'
    }

    return parts.join(' • ')
  }

  const formatPrice = (value) => {
    return Number(value || 0).toFixed(2)
  }

  const getGroupLineTotal = (ruleId, price) => {
    const qty = Number(groupForm.value.rule_counts?.[ruleId] || 0)
    return qty * Number(price || 0)
  }

  const setAdmissionMode = (mode) => {
    admissionMode.value = mode
    closeAssignModal()

    if (mode === 'group') {
      selectedSlot.value = null
      selectedParent.value = null
    }
  }

  const getParentStatus = (table) => {
    const totalCapacity = (table.children || []).reduce((sum, child) => {
      return sum + Number(child.capacity ?? 1)
    }, 0)

    const availableCapacity = availableChildrenCapacity(table)

    if (totalCapacity === 0) return 'no slots'
    if (availableCapacity === totalCapacity) return 'vacant'
    if (availableCapacity === 0) return 'full'
    return 'partially occupied'
  }

  const getParentStatusClass = (table) => {
    const status = getParentStatus(table)

    if (status === 'vacant') return 'bg-green-100 text-green-700'
    if (status === 'full') return 'bg-red-100 text-red-700'
    if (status === 'partially occupied') return 'bg-yellow-100 text-yellow-700'
    return 'bg-gray-100 text-gray-700'
  }

  const getSlotCardClass = (child) => {
    return !isChildOccupied(child)
      ? 'border-green-200 bg-green-50 hover:bg-green-100 hover:border-green-300'
      : 'border-red-200 bg-red-50 cursor-not-allowed opacity-80'
  }

  const resetSingleForm = () => {
    singleForm.value = {
      table_id: null,
      customer_name: '',
      pax: 1,
      rule_id: null,
      remarks: '',
    }
  }

  const resetGroupForm = () => {
    groupForm.value = {
      parent_table_id: null,
      customer_name: '',
      remarks: '',
      rule_counts: buildRuleCounts(),
    }
  }

  const openAssignModal = (child, parent) => {
    if (isChildOccupied(child)) return
    if (admissionMode.value !== 'single') return

    selectedSlot.value = child
    selectedParent.value = parent

    singleForm.value = {
      table_id: child.id,
      customer_name: '',
      pax: 1,
      rule_id: normalizedHeadPrices.value[0]?.id ?? null,
      remarks: '',
    }

    showAssignModal.value = true
  }

  const closeAssignModal = () => {
    showAssignModal.value = false
    selectedSlot.value = null
    selectedParent.value = null
    resetSingleForm()
  }

  const selectParentTable = (table) => {
    groupForm.value.parent_table_id = Number(table.id)
  }

  const markTableAsOccupied = (session) => {
    const childTableId = Number(session?.table_id)

    if (!childTableId) return

    tablesState.value = tablesState.value.map((parent) => {
      const updatedChildren = (parent.children || []).map((child) => {
        if (Number(child.id) !== childTableId) return child

        return {
          ...child,
          status: 'occupied',
          guest_count: Number(session?.pax ?? 1),
          customer_name: session?.customer_name ?? '',
          table_session_id: session?.id ?? null,
          ref_no: session?.ref_no ?? null,
          updated_at: session?.updated_at ?? child.updated_at,
        }
      })

      return {
        ...parent,
        children: updatedChildren,
      }
    })
  }

  const markGroupTablesAsOccupied = (sessions) => {
    if (!Array.isArray(sessions)) return
    sessions.forEach((session) => markTableAsOccupied(session))
  }

  const submitSingleAdmission = async () => {
    try {
      const payload = {
        ...singleForm.value,
        price: singleSelectedPrice.value,
        pricing_rule: selectedSingleRule.value,
      }

      console.log('single admission payload', payload)

      const response = await axios.post(route('frontdoor.table_admission.assign'), payload)

      console.log(response.data)

      const session = response?.data?.table_session

      if (session) {
        markTableAsOccupied(session)
      }

      closeAssignModal()

      router.reload({
        only: ['tables'],
        preserveScroll: true,
      })
    } catch (error) {
      console.error('submitSingleAdmission error', error)

      const message =
        error?.response?.data?.message ||
        'Failed to assign single admission.'

      alert(message)
    }
  }

  const submitGroupAdmission = async () => {
    try {
      const payload = {
        ...groupForm.value,
        pax: groupTotalPax.value,
        estimated_total: groupEstimatedTotal.value,
        pricing_breakdown: normalizedHeadPrices.value
          .map((rule) => {
            const qty = Number(groupForm.value.rule_counts?.[rule.id] || 0)

            return {
              rule_id: rule.id,
              label: rule.label,
              qty,
              price: Number(rule.price || 0),
              line_total: qty * Number(rule.price || 0),
            }
          })
          .filter((item) => item.qty > 0),
      }

      console.log('group admission payload', payload)

      if (!payload.parent_table_id) {
        alert('Please select a parent table.')
        return
      }

      if (payload.pax <= 0) {
        alert('Please enter at least 1 pax.')
        return
      }

      if (payload.pax > selectedGroupParentAvailableSlots.value) {
        alert('Entered pax exceeds available capacity for the selected parent table.')
        return
      }

      const response = await axios.post(route('frontdoor.table_admission.assign'), payload)

      console.log('group admission response', response.data)

      resetGroupForm()

      router.reload({
        only: ['tables', 'table_sessions'],
        preserveScroll: true,
      })
    } catch (error) {
      console.error('submitGroupAdmission error', error)

      const message =
        error?.response?.data?.message ||
        'Failed to assign group admission.'

      alert(message)
    }
  }

  return {
    search,
    admissionMode,
    showAssignModal,
    selectedSlot,
    selectedParent,
    singleForm,
    groupForm,
    filteredTables,
    normalizedHeadPrices,
    groupTotalPax,
    groupEstimatedTotal,
    selectedGroupParentAvailableSlots,
    selectedSingleRule,
    singleSelectedPrice,
    setAdmissionMode,
    totalChildrenCount,
    occupiedChildrenCount,
    availableChildrenCount,
    availableChildrenCapacity,
    getParentStatus,
    getParentStatusClass,
    getSlotCardClass,
    getRuleDescription,
    getGroupLineTotal,
    formatPrice,
    isChildOccupied,
    openAssignModal,
    closeAssignModal,
    submitSingleAdmission,
    submitGroupAdmission,
    selectParentTable,
    resetGroupForm,
  }
}