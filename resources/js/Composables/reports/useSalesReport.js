import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import useRolePrefix from '@/Composables/useRolePrefix'

// NEW BUG
// 1. on salesreport.vue, when cashier, status is selected with ALL shift, no cash and gcash

export function useSalesReport() {
  const { prefix } = useRolePrefix()
  const page = usePage()

  const orders = ref(page.props.orders ?? [])
  const startDate = ref(page.props.startDate ?? '')
  const endDate = ref(page.props.endDate ?? '')
  const cashiers = ref(page.props.cashiers ?? [])
  const selectedCashier = ref(page.props.selectedCashier ?? '')
  const consumedAddons = ref(page.props.consumedAddons ?? [])
  const selectedStatus = ref(page.props.selectedStatus ?? 'paid')
  const selectedShift = ref(page.props.selectedShift ?? '')
  const totalCashierExpenses = ref(Number(page.props.totalCashierExpenses ?? 0))
  const shifts = ref(page.props.shifts ?? [])
  const isLoading = ref(false)
  const totalCashSales = ref(Number(page.props.totalCashSales ?? 0))
  const totalGcashSales = ref(Number(page.props.totalGcashSales ?? 0))
  const totalMayaSales = ref(Number(page.props.totalMayaSales ?? 0))
  const totalReservationFees = ref(Number(page.props.totalReservationFees ?? 0))
  const payments = ref(page.props.payments ?? {})

  const paymentBreakdown = computed(() => {
    const breakdown = {}
    orders.value
      .filter(o => o.status === 'paid')
      .forEach(order => {
        order.payments?.forEach(p => {
          if (p.is_void) return
          const method = p.payment_method?.name ?? 'Unknown'
          breakdown[method] = (breakdown[method] || 0) + Number(p.amount || 0)
        })
      })
    return breakdown
  })

  const totalDiscounts = computed(() =>
    orders.value
      .filter(order => order.status === 'paid')
      .reduce((sum, order) => sum + Number(order.total_discount || 0), 0)
  )

  const grossSales = computed(() => {
    return Object.values(payments.value).reduce((sum, amount) => sum + Number(amount || 0), 0)
  })

  const netSales = computed(() => {
    return grossSales.value - Number(totalCashierExpenses.value || 0)
  })

  const transactionCount = computed(() =>
    orders.value.filter(order => order.status === 'paid').length
  )

  const voidedCount = computed(() =>
    orders.value.filter(order => order.status === 'cancelled').length
  )

  const fetchSalesReport = async () => {
    try {
      isLoading.value = true

      const response = await axios.get(
        route(`${prefix.value}.sales.fetch-sales-report`),
        {
          params: {
            start_date: startDate.value,
            end_date: endDate.value,
            status: selectedStatus.value,
            cashier_id: selectedCashier.value,
            shift_id: selectedShift.value,
          },
        }
      )

      // ✅ update orders
      orders.value = response.data.orders ?? []
      consumedAddons.value = response.data.consumedAddons ?? []
      console.log(orders.value)
      console.log(response.data)

      // 🔥 IMPORTANT: update expenses
      totalCashierExpenses.value = Number(response.data.totalCashierExpenses ?? 0)
      totalCashSales.value = Number(response.data.totalCashSales ?? 0)
      totalGcashSales.value = Number(response.data.totalGcashSales ?? 0)
      totalMayaSales.value = Number(response.data.totalMayaSales ?? 0)
      totalReservationFees.value = Number(response.data.totalReservationFees ?? 0)
      payments.value = response.data.payments ?? {}

      console.log('response.data')
      console.log(response.data)

    } catch (error) {
      console.error(error)
      alert('Failed to fetch sales report.')
    } finally {
      isLoading.value = false
    }
  }

  const generatePdfReport = () => {
    if (orders.value.length === 0) {
      alert('No data to print.')
      return
    }

    const url = route(`${prefix.value}.sales.print-sales-report-pdf`, {
      start_date: startDate.value,
      end_date: endDate.value,
      status: selectedStatus.value,
      cashier_id: selectedCashier.value,
    })

    window.open(url, '_blank')
  }

  const exportSalesSummary = () => {
    if (orders.value.length === 0) {
      alert('No data to export.')
      return
    }

    const url = route(`${prefix.value}.sales.export-sales-summary-report`, {
      start_date: startDate.value,
      end_date:   endDate.value,
      status:     selectedStatus.value,
      cashier_id: selectedCashier.value,
      shift_id:   selectedShift.value,
    })

    window.location.href = url   // triggers file download directly, same pattern as exportToExcel
  }

  const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
  }

  const exportToExcel = () => {
    if (orders.value.length === 0) {
      alert('No data to export.')
      return
    }

    const url = route(`${prefix.value}.sales.export-sales-report-excel`, {
      start_date: startDate.value,
      end_date:   endDate.value,
      status:     selectedStatus.value,
      cashier_id: selectedCashier.value,
      shift_id:   selectedShift.value,
    })

    window.location.href = url   // triggers file download directly
  }

  return {
    orders,
    startDate,
    endDate,
    cashiers,
    selectedCashier,
    selectedStatus,
    shifts,
    selectedShift,
    isLoading,
    grossSales,
    totalDiscounts,
    totalCashierExpenses,
    netSales,
    transactionCount,
    voidedCount,
    totalCashSales,
    totalGcashSales,
    totalReservationFees,
    fetchSalesReport,
    generatePdfReport,
    exportSalesSummary,
    formatDate,
    consumedAddons,
    totalMayaSales,
    paymentBreakdown,
    exportToExcel,
    payments
  }
}