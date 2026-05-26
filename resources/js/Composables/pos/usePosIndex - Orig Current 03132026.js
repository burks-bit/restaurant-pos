import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import useRolePrefix from '@/Composables/useRolePrefix'

export function usePosIndex() {
  const { prefix } = useRolePrefix()
  const page = usePage()

  const table_sessions = ref(page.props.table_sessions ?? [])
  const managers = ref(page.props.managers ?? [])
  const is_discount_allowed = ref(Number(page.props.is_discount_allowed ?? 0))

  const cart = ref([])
  const openAccordion = ref(null)

  const showPrintModal = ref(false)
  const isSeniorOrPWD = ref(false)
  const voucherCode = ref('')
  const voucherApplied = ref(false)
  const voucherDiscount = ref(null)

  const managerApprovedDiscount = ref(false)
  const appliedDiscountType = ref(null)
  const approvalType = ref(null)

  const showManagerDiscountModal = ref(false)
  const selectedManager = ref(null)
  const managerPassword = ref('')
  const passwordError = ref('')

  const paymentMethod = ref('Cash')
  const cashAmount = ref(0)
  const isLoading = ref(false)

  const voucherError = ref('')
  const voucherSuccess = ref('')

  const toastMessage = ref('')
  const showToast = ref(false)

  const lastOrderNo = ref(null)
  const loading = ref(false)

  const normalizeHeadCounts = (session) => {
    return {
      ...session,
      head_counts: (session.head_counts ?? []).map((head) => {
        const quantity = Number(head.count ?? head.qty ?? 0)
        const price = Number(head.price_snapshot ?? 0)

        return {
          ...head,
          qty: quantity,
          price_snapshot: price,
          subtotal: quantity * price,
        }
      }),
    }
  }

  const refreshList = async () => {
    try {
      loading.value = true

      const response = await axios.get(`/${prefix.value}/pos/table-sessions`)

      table_sessions.value = (response.data.table_sessions ?? response.data).map(normalizeHeadCounts)
    } catch (error) {
      console.error('Refresh error:', error)
    } finally {
      loading.value = false
    }
  }

  const selectTable = (session) => {
    cart.value = [normalizeHeadCounts(session)]
  }

  const removeFromCart = (index) => {
    cart.value.splice(index, 1)
    resetDiscounts()
  }

  const requestManagerApproval = (type) => {
    approvalType.value = type
    showManagerDiscountModal.value = true
  }

  const handleSeniorPWDChange = (event) => {
    if (event.target.checked) {
      requestManagerApproval('senior')
    } else {
      resetDiscounts()
    }
  }

  const applyVoucher = async () => {
    try {
      const response = await axios.get(`/${prefix.value}/pos/verify-voucher/${voucherCode.value}`)

      if (response.data.success) {
        voucherDiscount.value = response.data.voucher.type
        voucherSuccess.value = response.data.message
        voucherError.value = ''
        requestManagerApproval('voucher')
      } else {
        voucherError.value = response.data.message
        voucherSuccess.value = ''
      }
    } catch (error) {
      console.error(error)
      voucherError.value = 'Failed to verify voucher.'
      voucherSuccess.value = ''
    }
  }

  const useManagerAccount = async () => {
    try {
      const response = await axios.post(`/${prefix.value}/verify-manager-password`, {
        manager_id: selectedManager.value.id,
        password: managerPassword.value,
      })

      if (response.data.success) {
        managerApprovedDiscount.value = true
        appliedDiscountType.value = approvalType.value

        if (approvalType.value === 'senior') {
          isSeniorOrPWD.value = true
          voucherApplied.value = false
        }

        if (approvalType.value === 'voucher') {
          voucherApplied.value = true
          isSeniorOrPWD.value = false
        }

        showManagerDiscountModal.value = false
        managerPassword.value = ''
        passwordError.value = ''
        approvalType.value = null
      } else {
        passwordError.value = response.data.message || 'Invalid manager password.'
      }
    } catch (error) {
      console.error(error)
      passwordError.value = error.response?.data?.message || 'Invalid manager password.'
    }
  }

  const closeManagerModal = () => {
    showManagerDiscountModal.value = false
    selectedManager.value = null
    managerPassword.value = ''
    passwordError.value = ''
  }

  const resetDiscounts = () => {
    managerApprovedDiscount.value = false
    appliedDiscountType.value = null
    voucherApplied.value = false
    voucherDiscount.value = null
    isSeniorOrPWD.value = false
    voucherError.value = ''
    voucherSuccess.value = ''
    selectedManager.value = null
    managerPassword.value = ''
    passwordError.value = ''
  }

  const subtotal = computed(() => {
    if (cart.value.length === 0) return 0

    return cart.value[0].head_counts.reduce(
      (sum, head) => sum + Number(head.qty || 0) * Number(head.price_snapshot || 0),
      0
    )
  })

  const discountAmount = computed(() => {
    if (is_discount_allowed.value !== 1) return 0
    if (!managerApprovedDiscount.value) return 0

    if (appliedDiscountType.value === 'senior') {
      return subtotal.value * 0.2
    }

    if (appliedDiscountType.value === 'voucher' && voucherDiscount.value) {
      if (voucherDiscount.value.includes('%')) {
        return subtotal.value * (parseFloat(voucherDiscount.value) / 100)
      }

      if (voucherDiscount.value === 'Free Meal') {
        return subtotal.value
      }

      if (voucherDiscount.value === 'Free Meal (Bring 2 Companions)') {
        return subtotal.value
      }
    }

    return 0
  })

  const totalPrice = computed(() => {
    return Math.max(0, subtotal.value - discountAmount.value)
  })

  const changeAmount = computed(() => {
    if (paymentMethod.value !== 'Cash') return 0
    return Number(cashAmount.value || 0) - totalPrice.value
  })

  const checkoutSelectedTable = async () => {
    if (cart.value.length === 0) return

    isLoading.value = true

    try {
      const sessionId = cart.value[0].id

      const response = await axios.post(`/${prefix.value}/orders/store`, {
        table_session_id: cart.value[0].id,
        heads: cart.value[0].head_counts,
        total_discount: discountAmount.value,
        payment_method: paymentMethod.value,
        cash_amount: paymentMethod.value === 'Cash' ? cashAmount.value : null,
        voucher_code: voucherCode.value,
        voucher_discount: voucherDiscount.value,
        approving_manager: selectedManager.value,
      })

      voucherCode.value = ''
      cashAmount.value = ''
      voucherSuccess.value = ''
      voucherError.value = ''

      lastOrderNo.value = response.data.order_no
      toastMessage.value = 'Order Successful'
      showToast.value = true
      showPrintModal.value = true

      setTimeout(() => {
        showToast.value = false
      }, 3000)

      cart.value = []
      resetDiscounts()

      table_sessions.value = table_sessions.value.filter((session) => session.id !== sessionId)
    } catch (error) {
      if (error.response) {
        console.error('Validation / Server Error:', error.response.data)

        toastMessage.value = error.response.data.message
        showToast.value = true

        setTimeout(() => {
          showToast.value = false
        }, 3000)

        if (error.response.data.errors) {
          const firstError = Object.values(error.response.data.errors)[0][0]
          alert(firstError)
        }
      } else {
        console.error('Unexpected Error:', error)
        alert('Something went wrong.')
      }
    } finally {
      isLoading.value = false
    }
  }

  const filteredTableSessions = computed(() => {
    return table_sessions.value.filter((session) => session.status === 'open')
  })

  const toggleAccordion = (id) => {
    openAccordion.value = openAccordion.value === id ? null : id
  }

  const printReceipt = () => {
    window.open(`/${prefix.value}/orders/${lastOrderNo.value}/pdf`, '_blank')
    showPrintModal.value = false
  }

  let interval = null

  onMounted(() => {
    interval = setInterval(() => {
      refreshList()
    }, 15000)
  })

  onUnmounted(() => {
    clearInterval(interval)
  })

  return {
    table_sessions,
    managers,
    is_discount_allowed,

    cart,
    openAccordion,

    showPrintModal,
    isSeniorOrPWD,
    voucherCode,
    voucherApplied,
    voucherDiscount,

    managerApprovedDiscount,
    appliedDiscountType,
    approvalType,

    showManagerDiscountModal,
    selectedManager,
    managerPassword,
    passwordError,

    paymentMethod,
    cashAmount,
    isLoading,

    voucherError,
    voucherSuccess,

    toastMessage,
    showToast,

    lastOrderNo,
    loading,

    refreshList,
    selectTable,
    removeFromCart,

    requestManagerApproval,
    handleSeniorPWDChange,
    applyVoucher,
    useManagerAccount,
    closeManagerModal,
    resetDiscounts,

    subtotal,
    discountAmount,
    totalPrice,
    changeAmount,

    checkoutSelectedTable,

    filteredTableSessions,
    toggleAccordion,
    printReceipt,
  }
}