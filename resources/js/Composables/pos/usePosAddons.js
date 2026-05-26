import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import useRolePrefix from '@/Composables/useRolePrefix'

export function usePosAddons() {
  const { prefix } = useRolePrefix()
  const page = usePage()

  const orderable_items = computed(() => page.props.orderable_items ?? [])
  const is_discount_allowed = ref(Number(page.props.is_discount_allowed ?? 0))

  const cart = ref([])

  const isSeniorOrPWD = ref(false)
  const searchQuery = ref('')
  const themeColor = ref('gray')

  const isLoading = ref(false)

  const showToast = ref(false)
  const toastMessage = ref('')

  const showPrintModal = ref(false)
  const lastOrderId = ref(null)
  const lastOrderNo = ref(null)

  const paymentMethod = ref('Cash')
  const cashAmount = ref(0)

  const filteredOrderableItems = computed(() => {
    let list = orderable_items.value.filter((item) => Number(item.orderable) === 1)

    if (searchQuery.value.trim() !== '') {
      const keyword = searchQuery.value.toLowerCase()
      list = list.filter((item) =>
        (item.name || '').toLowerCase().includes(keyword)
      )
    }

    return list
  })

  const addToCart = (item) => {
    const existing = cart.value.find((cartItem) => cartItem.id === item.id)

    if (existing) {
      existing.qty += 1
    } else {
      cart.value.push({
        ...item,
        price: parseFloat(item.unit_price),
        qty: 1,
      })
    }
  }

  const increaseQty = (index) => {
    cart.value[index].qty += 1
  }

  const decreaseQty = (index) => {
    if (cart.value[index].qty > 1) {
      cart.value[index].qty -= 1
    } else {
      cart.value.splice(index, 1)
    }
  }

  const removeFromCart = (index) => {
    cart.value.splice(index, 1)
  }

  const subtotal = computed(() => {
    return cart.value.reduce(
      (sum, item) => sum + parseFloat(item.unit_price) * item.qty,
      0
    )
  })

  const discountAmount = computed(() => {
    if (is_discount_allowed.value !== 1) return 0
    return isSeniorOrPWD.value ? subtotal.value * 0.2 : 0
  })

  const totalPrice = computed(() => {
    return Math.max(0, subtotal.value - discountAmount.value)
  })

  const changeAmount = computed(() => {
    if (paymentMethod.value !== 'Cash') return 0
    return Number(cashAmount.value || 0) - totalPrice.value
  })

  const checkout = async () => {
    if (!cart.value.length) {
      toastMessage.value = 'Cart is empty!'
      showToast.value = true
      setTimeout(() => (showToast.value = false), 3000)
      return
    }

    if (paymentMethod.value === 'Cash' && Number(cashAmount.value || 0) < totalPrice.value) {
      toastMessage.value = 'Insufficient cash amount!'
      showToast.value = true
      setTimeout(() => (showToast.value = false), 3000)
      return
    }

    isLoading.value = true

    try {
      const response = await axios.post(`/${prefix.value}/orders/store/items`, {
        cart: cart.value,
        senior_pwd_discount_approved: is_discount_allowed.value === 1 ? isSeniorOrPWD.value : false,
        total_discount: is_discount_allowed.value === 1 ? discountAmount.value : 0,
        payment_method: paymentMethod.value,
        cash_amount: paymentMethod.value === 'Cash' ? cashAmount.value : null,
      })

      lastOrderId.value = response.data.order_id
      lastOrderNo.value = response.data.order_no

      toastMessage.value = response.data.message
      showToast.value = true
      setTimeout(() => (showToast.value = false), 3000)

      showPrintModal.value = true

      cart.value = []
      cashAmount.value = 0
      paymentMethod.value = 'Cash'
      isSeniorOrPWD.value = false
    } catch (error) {
      toastMessage.value = error.response?.data?.message ?? 'Something went wrong!'
      showToast.value = true
      setTimeout(() => (showToast.value = false), 3000)
    } finally {
      isLoading.value = false
    }
  }

  const printReceipt = () => {
    window.open(`/${prefix.value}/orders/${lastOrderId.value}/pdf`, '_blank')
    showPrintModal.value = false
  }

  return {
    orderable_items,
    is_discount_allowed,

    cart,

    isSeniorOrPWD,
    searchQuery,
    themeColor,

    isLoading,

    showToast,
    toastMessage,

    showPrintModal,
    lastOrderId,
    lastOrderNo,

    paymentMethod,
    cashAmount,

    changeAmount,
    filteredOrderableItems,
    addToCart,
    increaseQty,
    decreaseQty,
    removeFromCart,

    subtotal,
    discountAmount,
    totalPrice,
    checkout,
    printReceipt,
  }
}