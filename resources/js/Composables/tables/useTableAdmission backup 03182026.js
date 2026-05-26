import { computed, ref } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import axios from 'axios'

export function useTableAdmission() {
  const page = usePage()

  const tables = computed(() => page.props.tables ?? [])
  const headPrices = computed(() => page.props.head_prices ?? [])
  const tableSessions = computed(() => page.props.table_sessions ?? [])

  console.log(pricing_schemes.value);

  const selectedTable = ref(null)

  const form = ref({
    customer_name: '',
    remarks: '',
    is_shared: false,
    head_counts: buildInitialHeadCounts(headPrices.value),
  })

  const errors = ref({})
  const processing = ref(false)

  const activeHeadPrices = computed(() =>
    (headPrices.value ?? []).filter(head => Number(head.is_active) === 1)
  )

  function buildInitialHeadCounts(items = []) {
    return items.reduce((acc, item) => {
      acc[item.id] = 0
      return acc
    }, {})
  }

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
        (session) => Number(session.table_id) === Number(table.id) && session.status === 'open'
      )

      const occupiedPax = openSessions.reduce((sum, session) => {
        return sum + Number(session.pax ?? 0)
      }, 0)

      const availableCapacity = Math.max(Number(table.capacity ?? 0) - occupiedPax, 0)
      const isFullyOccupied = availableCapacity <= 0
      const hasOpenSession = openSessions.length > 0
      const hasSharedSession = openSessions.some((session) => Number(session.is_shared) === 1)

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
      head_counts: buildInitialHeadCounts(headPrices.value),
    }
    errors.value = {}
  }

  async function reloadAdmissionData() {
    await router.reload({
      only: ['tables', 'head_prices', 'table_sessions'],
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

      await axios.post(
        route('frontdoor.table_occupancies.assign', selectedTable.value.id),
        {
          guests_count: totalPax.value,
          head_rule_counts: ruleCounts,
          customer_name: form.value.customer_name,
          remarks: form.value.remarks,
          is_shared: form.value.is_shared ? 1 : 0,
        }
      )

      resetForm()
      selectedTable.value = null
      await reloadAdmissionData()
    } catch (error) {
      if (error.response?.status === 422) {
        errors.value = {
          ...(error.response.data.errors ?? {}),
          ...(error.response.data.message ? { general: error.response.data.message } : {}),
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
    headPrices,
    tableSessions,
    activeHeadPrices,
    selectedTable,
    form,
    errors,
    processing,
    totalPax,
    subtotal,
    tableStats,
    getTableStats,
    canSelectTable,
    selectTable,
    resetForm,
    submitAdmission,
    formatMoney,
    lineTotal,
  }
}