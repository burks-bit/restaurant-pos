import axios from 'axios'
import { ref, reactive, watch, nextTick, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import JsBarcode from 'jsbarcode'
import useRolePrefix from '@/Composables/useRolePrefix'

export function useOrdersIndex() {
  const { prefix } = useRolePrefix()
  const { props } = usePage()

  const orders = ref(props.orders ?? [])
  const total_sales_per_cashier = ref(props.total_sales_per_cashier ?? 0)
  const total_sales_all_cashier = ref(props.total_sales_all_cashier ?? 0)
  const total_unbilled = ref(props.total_unbilled ?? 0)
  const payment_methods = ref(props.payment_methods ?? 0)
  const isCashier = ref(props.isCashier ?? 0)
  const managers = props.managers ?? []

  console.log(payment_methods.value);
  console.log(orders.value);

  const showModal = ref(false)
  const showCancelModal = ref(false)
  const selectedOrder = ref(null)

  const selectedDate = ref('')
  const search = ref('')
  const selectedStatus = ref('')

  const cancelReason = ref('')
  const managerPassword = ref('')
  const selectedManagers = ref([])

  const isLoading = ref(false)
  const isRefreshLoading = ref(false)

  const showLeftoverModal = ref(false)
  const isPostingLeftover = ref(false)

  const leftoverForm = reactive({
    order_id: null,
    weight: '',
    amount: '',
  })

  const orderTotals = reactive({
    heads_subtotal: 0,
    addons_subtotal: 0,
    leftover_amount: 0,
    subtotal: 0,
    total_discount: 0,
    total: 0,
  })

  const applyFilters = async () => {
    try {
      isLoading.value = true

      const response = await axios.get(
        route(`${prefix.value}.orders.fetch-filtered-orders`),
        {
          params: {
            date: selectedDate.value,
            status: selectedStatus.value,
            search: search.value,
          },
        }
      )

      orders.value = response.data.orders ?? []
    } catch (error) {
      console.error(error)
    } finally {
      isLoading.value = false
    }
  }

  const computeTotals = () => {
    if (!selectedOrder.value) return

    const heads = selectedOrder.value.order_heads ?? []
    const addons = selectedOrder.value.addons ?? []
    const leftoverAmount = Number(selectedOrder.value.leftover?.amount || 0)

    const headsSubtotal = heads.reduce((sum, head) => sum + Number(head.subtotal || 0), 0)
    const addonsSubtotal = addons.reduce((sum, addon) => sum + Number(addon.subtotal || 0), 0)

    const subtotal = headsSubtotal + addonsSubtotal + leftoverAmount
    const totalDiscount = Number(selectedOrder.value.total_discount || 0)
    const total = Math.max(subtotal - totalDiscount, 0)

    orderTotals.heads_subtotal = headsSubtotal
    orderTotals.addons_subtotal = addonsSubtotal
    orderTotals.leftover_amount = leftoverAmount
    orderTotals.subtotal = subtotal
    orderTotals.total_discount = totalDiscount
    orderTotals.total = total
  }

  const renderBarcode = (orderNo) => {
    if (!orderNo) return

    setTimeout(() => {
      JsBarcode('#barcode', orderNo, {
        format: 'CODE128',
        width: 2,
        height: 50,
        displayValue: true,
      })
    }, 50)
  }

  const openModal = async (order) => {
    selectedOrder.value = JSON.parse(JSON.stringify(order))
    computeTotals()
    showModal.value = true

    await nextTick()
    renderBarcode(selectedOrder.value?.order_no)
  }

  const closeModal = () => {
    showModal.value = false
    selectedOrder.value = null
  }

  const getDisplayedOrderTotal = (order) => {
    const baseTotal = Number(order.total || 0)

    const addonsTotal = (order.addons || []).reduce(
      (sum, addon) => sum + Number(addon.subtotal || 0),
      0
    )

    const leftoverAmount = Number(order.leftover?.amount || 0)

    return baseTotal + leftoverAmount
  }

  const getDisplayedItemCount = (order) => {
    const headCount = (order.order_heads || []).reduce(
      (sum, head) => sum + Number(head.quantity || 0),
      0
    )

    const addonCount = (order.addons || []).reduce(
      (sum, addon) => sum + Number(addon.quantity || 0),
      0
    )

    return headCount + addonCount
  }

  const openLeftoverModal = (order) => {
    selectedOrder.value = JSON.parse(JSON.stringify(order))

    leftoverForm.order_id = order.id
    leftoverForm.weight = order.leftover?.weight ?? ''
    leftoverForm.amount = order.leftover?.amount ?? ''

    showLeftoverModal.value = true
  }

  const closeLeftoverModal = () => {
    showLeftoverModal.value = false
    leftoverForm.order_id = null
    leftoverForm.weight = ''
    leftoverForm.amount = ''
  }

  const submitLeftoverCharge = async () => {
    if (!leftoverForm.order_id) {
      alert('No order selected.')
      return
    }

    if (Number(leftoverForm.weight) <= 0) {
      alert('Please enter a valid leftover weight.')
      return
    }

    if (Number(leftoverForm.amount) < 0) {
      alert('Please enter a valid leftover amount.')
      return
    }

    try {
      isPostingLeftover.value = true

      await axios.post(
        route(`${prefix.value}.orders.store-leftover`),
        {
          order_id: leftoverForm.order_id,
          weight: leftoverForm.weight,
          amount: leftoverForm.amount,
        }
      )

      closeLeftoverModal()
      await refreshComputation()
    } catch (error) {
      console.error(error)
      alert(error.response?.data?.message || 'Failed to save leftover charge.')
    } finally {
      isPostingLeftover.value = false
    }
  }

  const formatDateTime = (value) => {
    if (!value) return 'N/A'
    return new Date(value).toLocaleString()
  }

  const printReceipt = (order) => {
    window.open(route(`${prefix.value}.orders.pdf`, order.order_no), '_blank')
  }

  const openCancelOrder = (order) => {
    selectedOrder.value = order
    showCancelModal.value = true
  }

  const closeCancelModal = () => {
    showCancelModal.value = false
    cancelReason.value = ''
    managerPassword.value = ''
    selectedManagers.value = []
  }

  const cancelOrder = () => {
    if (!cancelReason.value.trim()) {
      alert('Please provide a reason.')
      return
    }

    if (!managerPassword.value.trim()) {
      alert('Manager password is required.')
      return
    }

    router.post(
      route(`${prefix.value}.orders.cancel`, selectedOrder.value.id),
      {
        reason: cancelReason.value,
        manager: selectedManagers.value,
        manager_password: managerPassword.value,
      },
      {
        preserveState: true,
        replace: true,
        onSuccess: () => closeCancelModal(),
      }
    )
  }

  const refreshComputation = async () => {
    if (isRefreshLoading.value) return

    try {
      isRefreshLoading.value = true

      const response = await axios.get(
        route(`${prefix.value}.orders.refresh-computation`)
      )

      orders.value = response.data.orders ?? []
      total_sales_per_cashier.value = response.data.total_sales_per_cashier ?? 0
      total_sales_all_cashier.value = response.data.total_sales_all_cashier ?? 0
      total_unbilled.value = response.data.total_unbilled ?? 0
    } catch (error) {
      console.error(error)
    } finally {
      isRefreshLoading.value = false
    }
  }

  const totalPaidForSelectedOrder = computed(() => {
    return (selectedOrder.value?.payments ?? [])
      .filter(payment => !payment.is_void)
      .reduce((sum, payment) => sum + Number(payment.amount || 0), 0)
  })

  watch(selectedOrder, (order) => {
    if (order && showModal.value) {
      renderBarcode(order.order_no)
    }
  })

  return {
    orders,
    total_sales_per_cashier,
    total_sales_all_cashier,
    total_unbilled,
    managers,

    showModal,
    showCancelModal,
    selectedOrder,

    selectedDate,
    search,
    selectedStatus,

    cancelReason,
    managerPassword,
    selectedManagers,

    isLoading,
    isRefreshLoading,

    showLeftoverModal,
    leftoverForm,
    isPostingLeftover,

    orderTotals,

    applyFilters,
    openModal,
    closeModal,
    getDisplayedOrderTotal,
    getDisplayedItemCount,

    openLeftoverModal,
    closeLeftoverModal,
    submitLeftoverCharge,

    computeTotals,
    formatDateTime,
    printReceipt,

    openCancelOrder,
    closeCancelModal,
    cancelOrder,

    refreshComputation,
    totalPaidForSelectedOrder,
    isCashier
  }
}