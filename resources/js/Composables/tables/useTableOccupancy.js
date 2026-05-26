import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { usePage, router } from '@inertiajs/vue3'
import useRolePrefix from '@/Composables/useRolePrefix'

export function useTableOccupancy() {
  const page = usePage()
  const { prefix } = useRolePrefix()

  const tableSessions = computed(() => page.props.table_sessions ?? [])
  const tables = ref(page.props.tables ?? [])
  const head_prices = ref(page.props.head_prices ?? [])
  const managers = ref(page.props.managers ?? [])

  // console.log(tables.value)
  // console.log(head_prices.value)
  // console.log(tableSessions.value)

  const now = ref(new Date())
  let interval = null

  const search = ref('')
  const cancelReason = ref('')
  const showAssignModal = ref(false)
  const showDetailModal = ref(false)
  const showCancelModal = ref(false)
  const selectedSession = ref(null)

  const modalSearch = ref('')
  const pax = ref({})
  const headCounts = ref({})
  const customerName = ref({})
  const tableDetails = ref({})

  const selectedManager = ref(null)
  const managerPassword = ref('')

  const toast = ref({
    show: false,
    message: '',
  })

  const isBlinkOn = () => {
    return now.value.getSeconds() % 2 === 0
  }

  const isPaid = (session) => {
    return !!session.cashier_id || Number(session.order?.total ?? 0) > 0
  }

  const formatSessionStatus = (status) => {
    if (status === 'open') return 'Occupied'
    if (status === 'closed') return 'Closed'
    if (status === 'cancelled') return 'Cancelled'
    return status ?? '-'
  }

  const getStatusBadgeClass = (status) => {
    if (status === 'open') return 'bg-red-100 text-red-700'
    if (status === 'closed') return 'bg-gray-200 text-gray-700'
    if (status === 'cancelled') return 'bg-red-50 text-red-500'
    return 'bg-gray-100 text-gray-600'
  }

  const showToast = (message, duration = 6000) => {
    toast.value.message = message
    toast.value.show = true
    setTimeout(() => {
      toast.value.show = false
    }, duration)
  }

  const refreshPage = () => {
    router.reload({
      only: ['table_sessions', 'managers'],
    })
  }

  const checkPaid = () => {
    refreshPage()
  }

  const initHeadCounts = () => {
    tables.value.forEach((table) => {
      headCounts.value[table.id] = {}
      head_prices.value.forEach((rule) => {
        headCounts.value[table.id][rule.id] = 0
      })
    })
  }

  const openAssignModal = () => {
    showAssignModal.value = true
    initHeadCounts()
  }

  const closeAssignModal = () => {
    showAssignModal.value = false
  }

  const hasCashier = (session) => {
    return session.cashier_id !== null &&
      session.cashier_id !== undefined &&
      session.cashier_id !== ''
  }

  const cancelTableSession = (session) => {
    if (session.status !== 'open') {
      showToast('Only open sessions can be cancelled')
      return
    }

    if (hasCashier(session)) {
      showToast('Cannot cancel. This table session already has cashier payment')
      return
    }

    selectedSession.value = session
    cancelReason.value = ''
    selectedManager.value = null
    managerPassword.value = ''
    showCancelModal.value = true
  }

  const closeCancelModal = () => {
    showCancelModal.value = false
    selectedSession.value = null
    cancelReason.value = ''
    selectedManager.value = null
    managerPassword.value = ''
  }

  const closeDetailModal = () => {
    showDetailModal.value = false
    tableDetails.value = ''
  }

  const cancelOrder = async () => {
    if (!selectedSession.value) {
      showToast('No table session selected')
      return
    }

    if (!cancelReason.value.trim()) {
      showToast('Please provide a reason')
      return
    }

    if (!selectedManager.value) {
      showToast('Please select a manager')
      return
    }

    if (!managerPassword.value.trim()) {
      showToast('Please enter manager password')
      return
    }

    try {
      await axios.post(
        route(`${prefix.value}.table_occupancies.cancel`, selectedSession.value),
        {
          remarks: cancelReason.value,
          manager_id: selectedManager.value,
          manager_password: managerPassword.value,
        }
      )

      showToast('Table session cancelled successfully')
      closeCancelModal()
      refreshPage()
    } catch (error) {
      console.error(error)
      showToast(error.response?.data?.message || 'Failed to cancel table session')
    }
  }

  const filteredTableSessions = computed(() => {
    let result = tableSessions.value

    if (search.value) {
      const keyword = search.value.toLowerCase()
      result = result.filter((session) =>
        (session.table?.name || '').toLowerCase().includes(keyword) ||
        (session.customer_name || '').toLowerCase().includes(keyword) ||
        (session.ref_no || '').toLowerCase().includes(keyword)
      )
    }

    return result.sort((a, b) =>
      a.status === 'open' && b.status !== 'open' ? -1 : 1
    )
  })

  const filteredModalTables = computed(() => {
    let result = tables.value

    if (modalSearch.value) {
      const keyword = modalSearch.value.toLowerCase()
      result = result.filter((table) =>
        (table.name || '').toLowerCase().includes(keyword)
      )
    }

    return result
  })

  const isTableOccupied = (table) => {
    return tableSessions.value.some(
      (session) => session.table?.id === table.id && session.status === 'open'
    )
  }

  const updateTotalPax = (tableId) => {
    const counts = headCounts.value[tableId] || {}
    let total = 0

    Object.values(counts).forEach((value) => {
      total += Number(value || 0)
    })

    pax.value[tableId] = total
  }

  const viewDettails = (session) => {
    console.log('viewDettails')
    console.log(session)
    tableDetails.value = session
    showDetailModal.value = true

  }

  const markVacant = async (session) => {
    try {
      const response = await axios.post(
        route(`${prefix.value}.table_occupancies.update`, session.table.id),
        {
          status: 'vacant',
          pax: session.pax ?? 0,
        }
      )

      if (!response.data.success) {
        showToast(response.data.message)
        return
      }

      session.status = 'closed'
      showToast(response.data.message || 'Table marked vacant')
    } catch (error) {
      console.error(error)

      if (error.response?.data?.message) {
        showToast(error.response.data.message)
      } else {
        showToast('Failed to mark vacant')
      }
    }
  }

  const assignTable = async (table) => {
    const name = customerName.value[table.id] ?? ''
    if (!name.trim()) {
      alert('No Customer Name. Please provide name!')
      return
    }

    const numPax = pax.value[table.id] ?? 1
    const ruleCounts = headCounts.value[table.id] ?? {}

    try {
      await axios.post(route(`${prefix.value}.table_occupancies.assign`, table.id), {
        guests_count: numPax,
        head_rule_counts: ruleCounts,
        customer_name: name,
      })

      refreshPage()

      pax.value[table.id] = 0
      customerName.value[table.id] = ''
      initHeadCounts()

      showToast('Table assigned successfully')
    } catch (error) {
      console.error(error)
      showToast('Failed to assign table')
    }
  }

  const formatDuration = (createdAt) => {
    if (!createdAt) return '-'

    const created = new Date(createdAt)
    const diff = Math.floor((now.value - created) / 1000)

    if (diff < 0) return '00:00:00'

    const days = Math.floor(diff / 86400)
    const hours = Math.floor((diff % 86400) / 3600)
    const minutes = Math.floor((diff % 3600) / 60)
    const seconds = diff % 60

    const time =
      String(hours).padStart(2, '0') + ':' +
      String(minutes).padStart(2, '0') + ':' +
      String(seconds).padStart(2, '0')

    return days > 0 ? `${days}d ${time}` : time
  }

  const getDurationClass = (createdAt) => {
    if (!createdAt) return ''

    const created = new Date(createdAt)
    const diffSeconds = Math.floor((now.value - created) / 1000)
    const twoHours = 2 * 60 * 60

    return diffSeconds >= twoHours
      ? 'text-red-600 font-semibold'
      : 'text-gray-900'
  }

  const getElapsedMinutes = (createdAt) => {
    if (!createdAt) return 0

    const created = new Date(createdAt)
    const diffMs = now.value - created

    return Math.max(0, Math.floor(diffMs / 1000 / 60))
  }

  const getStayAlertStatus = (createdAt) => {
    const minutes = getElapsedMinutes(createdAt)

    if (minutes >= 120) return 'overdue'
    if (minutes >= 80) return 'warning'
    return 'normal'
  }

  const getStayAlertText = (createdAt) => {
    const status = getStayAlertStatus(createdAt)

    if (status === 'overdue') return 'Over 2 hrs'
    if (status === 'warning') return '40 mins left'
    return 'OK'
  }

  const getStayAlertIcon = (createdAt) => {
    const status = getStayAlertStatus(createdAt)

    if (status === 'overdue' || status === 'warning') {
      return isBlinkOn() ? 'fa-solid fa-bell' : 'fa-solid fa-bell-slash'
    }

    return 'fa-solid fa-bell-slash'
  }

  const getStayAlertClass = (createdAt) => {
    const status = getStayAlertStatus(createdAt)

    if (status === 'overdue') {
      return isBlinkOn() ? 'bg-red-100 text-red-600' : 'bg-white text-red-600'
    }

    if (status === 'warning') {
      return isBlinkOn() ? 'bg-yellow-100 text-yellow-600' : 'bg-white text-yellow-600'
    }

    return 'bg-gray-100 text-gray-500'
  }

  const getStayAlertTextClass = (createdAt) => {
    const status = getStayAlertStatus(createdAt)

    if (status === 'overdue') return 'text-red-600'
    if (status === 'warning') return 'text-yellow-600'
    return 'text-gray-500'
  }

  const getRowAlertClass = (session) => {
    if (!session?.created_at || session.status !== 'open') return ''

    const status = getStayAlertStatus(session.created_at)

    if (status === 'overdue') {
      return isBlinkOn() ? 'bg-red-100 text-red-700' : ''
    }

    if (status === 'warning') {
      return isBlinkOn() ? 'bg-yellow-100' : 'bg-white'
    }

    return ''
  }

  onMounted(() => {
    interval = setInterval(() => {
      now.value = new Date()
    }, 1000)
  })

  onUnmounted(() => {
    if (interval) clearInterval(interval)
  })

  return {
    search,
    cancelReason,
    showAssignModal,
    showDetailModal,
    showCancelModal,
    closeDetailModal,
    selectedSession,
    modalSearch,
    pax,
    headCounts,
    customerName,
    tableDetails,
    head_prices,
    managers,
    selectedManager,
    managerPassword,
    toast,
    isPaid,
    formatSessionStatus,
    getStatusBadgeClass,
    showToast,
    refreshPage,
    checkPaid,
    openAssignModal,
    closeAssignModal,
    cancelTableSession,
    closeCancelModal,
    hasCashier,
    cancelOrder,
    filteredTableSessions,
    filteredModalTables,
    isTableOccupied,
    updateTotalPax,
    markVacant,
    viewDettails,
    assignTable,
    formatDuration,
    getDurationClass,
    getElapsedMinutes,
    getStayAlertStatus,
    getStayAlertText,
    getStayAlertIcon,
    getStayAlertClass,
    getStayAlertTextClass,
    getRowAlertClass,
  }
}