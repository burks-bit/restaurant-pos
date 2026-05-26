import { computed, ref, watch } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import axios from 'axios'
import useRolePrefix from '@/Composables/useRolePrefix'

export function useTableAdmission() {
  const page = usePage()
  const { prefix } = useRolePrefix()

  const tables = computed(() => page.props.tables ?? [])
  const tableSessions = computed(() => page.props.table_sessions ?? [])
  const pricingSchemes = computed(() => page.props.pricing_schemes ?? [])
  // const incomingReservation = computed(() => page.props.incoming_reservation ?? null)
  const incomingReservation = ref(page.props.incoming_reservation ?? null)

  const tableSearch = ref('')

  const toast = ref({
    show: false,
    message: '',
  })

  const showToast = (message, duration = 6000) => {
    toast.value.message = message
    toast.value.show = true
    setTimeout(() => {
      toast.value.show = false
    }, duration)
  }

  const filteredTables = computed(() => {
    const keyword = tableSearch.value?.toLowerCase().trim()

    if (!keyword) return tables.value ?? []

    return (tables.value ?? []).filter(table =>
      String(table.name ?? '').toLowerCase().includes(keyword)
    )
  })

  const activePricingSchemes = computed(() =>
    (pricingSchemes.value ?? []).filter(
      scheme => Number(scheme.is_active) === 1
    )
  )

  const selectedTable = ref(null)

  const defaultPricingSchemeId = computed(() => {
    const regularScheme = activePricingSchemes.value.find(
      scheme => scheme.type === 'regular'
    )

    return regularScheme?.id ?? activePricingSchemes.value[0]?.id ?? null
  })

  const form = ref({
    customer_name: '',
    remarks: '',
    is_shared: false,
    pricing_scheme_id: defaultPricingSchemeId.value,
    head_counts: {},
  })

  const errors = ref({})
  const processing = ref(false)

  const selectedPricingScheme = computed(() => {
    return activePricingSchemes.value.find(
      scheme => Number(scheme.id) === Number(form.value.pricing_scheme_id)
    ) ?? null
  })

  const activeHeadPrices = computed(() => {
    return (selectedPricingScheme.value?.head_rules ?? []).filter(
      head => Number(head.is_active) === 1
    )
  })

  function buildInitialHeadCounts(items = []) {
    return items.reduce((acc, item) => {
      acc[item.id] = 0
      return acc
    }, {})
  }

  watch(
    activeHeadPrices,
    (newRules) => {
      const newCounts = buildInitialHeadCounts(newRules)

      newRules.forEach((rule) => {
        if (form.value.head_counts?.[rule.id] != null) {
          newCounts[rule.id] = Number(form.value.head_counts[rule.id] ?? 0)
        }
      })

      form.value.head_counts = newCounts
    },
    { immediate: true }
  )

  watch(
    defaultPricingSchemeId,
    (newId) => {
      if (!form.value.pricing_scheme_id && newId) {
        form.value.pricing_scheme_id = newId
      }
    },
    { immediate: true }
  )

  watch(
    incomingReservation,
    (reservation) => {
      if (!reservation) return

      form.value.pricing_scheme_id = reservation.pricing_scheme_id

      const counts = {}
      reservation.reservation_pax?.forEach((rp) => {
        counts[rp.head_pricing_rule_id] = rp.qty
      })
      form.value.head_counts = counts
      form.value.customer_name = reservation.name
      form.value.remarks = reservation.remarks ?? ''
    },
    { immediate: true }
  )

  const totalPax = computed(() => {
    return activeHeadPrices.value.reduce((sum, head) => {
      const qty = Number(form.value.head_counts[head.id] ?? 0)
      return sum + qty
    }, 0)
  })

  const subtotal = computed(() => {
    return activeHeadPrices.value.reduce((sum, head) => {
      const qty = Number(form.value.head_counts[head.id] ?? 0)
      const price = Number(head.price ?? 0)
      return sum + (qty * price)
    }, 0)
  })

  const tableStats = computed(() => {
    const stats = {}

    tables.value.forEach((table) => {
      const openSessions = tableSessions.value.filter(
        (session) =>
          Number(session.table_id) === Number(table.id) &&
          session.status === 'open'
      )

      const occupiedPax = openSessions.reduce((sum, session) => {
        return sum + Number(session.pax ?? 0)
      }, 0)

      const availableCapacity = Math.max(
        Number(table.capacity ?? 0) - occupiedPax,
        0
      )

      const isFullyOccupied = availableCapacity <= 0
      const hasOpenSession = openSessions.length > 0
      const hasSharedSession = openSessions.some(
        (session) => Number(session.is_shared) === 1
      )

      stats[table.id] = {
        occupiedPax,
        availableCapacity,
        isFullyOccupied,
        hasOpenSession,
        hasSharedSession,
        openSessions,
      }
    })

    return stats
  })

  function getTableStats(tableId) {
    return tableStats.value[tableId] ?? {
      occupiedPax: 0,
      availableCapacity: 0,
      isFullyOccupied: false,
      hasOpenSession: false,
      hasSharedSession: false,
      openSessions: [],
    }
  }

  function canSelectTable(table) {
    return !getTableStats(table.id).isFullyOccupied
  }

  function selectTable(table) {
    if (!canSelectTable(table)) return

    selectedTable.value = table
    errors.value.table_id = null
  }

  function lineTotal(head) {
    const qty = Number(form.value.head_counts[head.id] ?? 0)
    const price = Number(head.price ?? 0)
    return qty * price
  }

  function formatMoney(value) {
    return Number(value ?? 0).toLocaleString('en-PH', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
  }

  function resetForm() {
    form.value = {
      customer_name: '',
      remarks: '',
      is_shared: false,
      pricing_scheme_id: defaultPricingSchemeId.value,
      head_counts: buildInitialHeadCounts(
        (
          activePricingSchemes.value.find(
            scheme => Number(scheme.id) === Number(defaultPricingSchemeId.value)
          )?.head_rules ?? []
        ).filter(head => Number(head.is_active) === 1)
      ),
    }

    errors.value = {}
  }

  function formatDateTime(value) {
    if (!value) return '-'
    return new Date(value).toLocaleString('en-PH', {
      year: 'numeric', month: 'short', day: '2-digit',
      hour: '2-digit', minute: '2-digit',
    })
  }

  async function reloadAdmissionData() {
    await router.reload({
      only: ['tables', 'table_sessions', 'pricing_schemes'],
      preserveScroll: true,
      preserveState: true,
    })
  }

  async function submitAdmission() {
    errors.value = {}

    if (!selectedTable.value) {
      errors.value.table_id = 'Please select a table.'
      return
    }

    if (!form.value.pricing_scheme_id) {
      errors.value.pricing_scheme_id = 'Please select a pricing scheme.'
      return
    }

    if (!form.value.customer_name?.trim()) {
      errors.value.customer_name = 'Customer name is required.'
      return
    }

    if (totalPax.value <= 0) {
      errors.value.head_counts = 'Please enter at least one pax.'
      return
    }

    const selectedStats = getTableStats(selectedTable.value.id)

    if (totalPax.value > selectedStats.availableCapacity) {
      errors.value.head_counts = `Only ${selectedStats.availableCapacity} seat(s) available for ${selectedTable.value.name}.`
      return
    }

    processing.value = true

    try {
      const ruleCounts = {}

      activeHeadPrices.value.forEach((head) => {
        const qty = Number(form.value.head_counts[head.id] ?? 0)

        if (qty > 0) {
          ruleCounts[head.id] = qty
        }
      })

      const response = await axios.post(
        route(`${prefix.value}.table_occupancies.assign`, selectedTable.value.id),
        {
          pricing_scheme_id: form.value.pricing_scheme_id,
          guests_count: totalPax.value,
          head_rule_counts: ruleCounts,
          customer_name: form.value.customer_name,
          remarks: form.value.remarks,
          is_shared: form.value.is_shared ? 1 : 0,
          reservation_id:    incomingReservation.value?.id ?? null,
        }
      )
      
      resetForm()
      selectedTable.value = null
      incomingReservation.value = null
      await reloadAdmissionData()
      showToast('Customer and its table successfully registered.');
    } catch (error) {
      if (error.response?.status === 422) {
        errors.value = {
          ...(error.response.data.errors ?? {}),
          ...(error.response.data.message
            ? { general: error.response.data.message }
            : {}),
        }
      } else {
        console.error('Table admission failed:', error)
        errors.value.general = 'Failed to save table admission.'
      }
    } finally {
      processing.value = false
    }
  }

  return {
    tables,
    tableSearch,
    filteredTables,
    activePricingSchemes,
    selectedPricingScheme,
    activeHeadPrices,
    selectedTable,
    toast,
    form,
    errors,
    processing,
    totalPax,
    subtotal,
    getTableStats,
    canSelectTable,
    selectTable,
    resetForm,
    submitAdmission,
    formatMoney,
    lineTotal,
    incomingReservation,
    formatDateTime
  }
}