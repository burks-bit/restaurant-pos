import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import useRolePrefix from '@/Composables/useRolePrefix'

export function usePosIndex() {
  const { prefix } = useRolePrefix()
  const page = usePage()

  const table_sessions = ref((page.props.table_sessions ?? []).map(normalizeHeadCounts))
  const managers = ref(page.props.managers ?? [])
  const orderable_items = ref(page.props.orderable_items ?? [])
  const is_discount_allowed = ref(Number(page.props.is_discount_allowed ?? 0))

  const cart = ref([])

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
  const search = ref('')

  const paymentMethod = ref('Cash')
  const cashAmount = ref(0)
  const isLoading = ref(false)

  const voucherError = ref('')
  const voucherSuccess = ref('')

  const toastMessage = ref('')
  const showToast = ref(false)

  const lastOrderNo = ref(null)
  const loading = ref(false)

  const showSelectTableModal = ref(false)
  const showAddonModal = ref(false)
  const showLeftoverModal = ref(false)

  const selectedAddons = ref([])

  // ADDON MODAL STATE
  const addonSearch = ref('')
  const addonSelectedItems = ref([])

  // LEFTOVER STATE
  const leftovers = ref([])
  const leftoverForm = ref(getDefaultLeftoverForm())

  function getDefaultLeftoverForm() {
    return {
      weight: '',
      price: '',
      remarks: '',
    }
  }

  function normalizeHeadCounts(session) {
    return {
      ...session,
      head_counts: (session.head_counts ?? []).map((head) => ({
        ...head,
        qty: Number(head.qty ?? head.count ?? 0),
        price_snapshot: Number(head.price_snapshot ?? 0),
        subtotal: Number(head.subtotal ?? 0),
      })),
      addons: (session.addons ?? []).map((addon) => ({
        ...addon,
        quantity: Number(addon.quantity ?? 0),
        unit_price: Number(addon.unit_price ?? 0),
        subtotal: Number(addon.subtotal ?? 0),
      })),
    }
  }

  const mapSessionAddons = (addons = []) => {
    return addons.map((item) => ({
      id: item.id,
      item_id: item.inventory_item_id,
      item_name: item.item_name,
      quantity: Number(item.quantity ?? 0),
      unit_price: Number(item.unit_price ?? 0),
      subtotal: Number(item.subtotal ?? 0),
      unit: item.unit ?? '',
      is_billed: Number(item.is_billed ?? 0),
    }))
  }

  const getSessionBaseTotal = (session) => {
    return (session.head_counts ?? []).reduce((sum, head) => {
      return sum + Number(head.subtotal || 0)
    }, 0)
  }

  const refreshList = async () => {
    try {
      loading.value = true
      const response = await axios.get(`/${prefix.value}/pos/table-sessions`)
      table_sessions.value = (response.data.table_sessions ?? response.data).map(normalizeHeadCounts)

      // keep current selected table in sync without manual refresh
      if (cart.value.length > 0) {
        const currentSessionId = cart.value[0].id
        const updatedSession = table_sessions.value.find(
          (session) => Number(session.id) === Number(currentSessionId)
        )

        if (updatedSession) {
          cart.value = [updatedSession]
          selectedAddons.value = mapSessionAddons(updatedSession.addons ?? [])
        }
      }
    } catch (error) {
      console.error('Refresh error:', error)
    } finally {
      loading.value = false
    }
  }

  const selectTable = (session) => {
    const normalizedSession = normalizeHeadCounts(session)

    cart.value = [normalizedSession]
    selectedAddons.value = mapSessionAddons(normalizedSession.addons ?? [])
    leftovers.value = []
    resetAddonModalState()
    resetDiscounts()
  }

  const removeFromCart = (index) => {
    cart.value.splice(index, 1)
    selectedAddons.value = []
    leftovers.value = []
    resetAddonModalState()
    resetDiscounts()
  }

  const removeAddon = (index) => {
    selectedAddons.value.splice(index, 1)
  }

  const removeLeftover = (index) => {
    leftovers.value.splice(index, 1)
  }

  const resetAddonModalState = () => {
    addonSearch.value = ''
    addonSelectedItems.value = []
  }

  const openAddonModal = () => {
    if (cart.value.length === 0) {
      showToastMessage('Please select a table first.')
      return
    }

    resetAddonModalState()
    showAddonModal.value = true
  }

  const closeAddonModal = () => {
    resetAddonModalState()
    showAddonModal.value = false
  }

  const openLeftoverModal = () => {
    if (cart.value.length === 0) {
      showToastMessage('Please select a table first.')
      return
    }

    leftoverForm.value = getDefaultLeftoverForm()
    showLeftoverModal.value = true
  }

  const closeLeftoverModal = () => {
    leftoverForm.value = getDefaultLeftoverForm()
    showLeftoverModal.value = false
  }

  const filteredAddonItems = computed(() => {
    const keyword = String(addonSearch.value || '').trim().toLowerCase()

    if (!keyword) return orderable_items.value ?? []

    return (orderable_items.value ?? []).filter((item) => {
      const name = String(item.name ?? '').toLowerCase()
      const category = String(item.category ?? item.category_name ?? '').toLowerCase()
      const type = String(item.type ?? '').toLowerCase()

      return (
        name.includes(keyword) ||
        category.includes(keyword) ||
        type.includes(keyword)
      )
    })
  })

  const isAddonSelected = (itemId) => {
    return addonSelectedItems.value.some((item) => Number(item.item_id) === Number(itemId))
  }

  const toggleAddonSelection = (item) => {
    const existingIndex = addonSelectedItems.value.findIndex(
      (selected) => Number(selected.item_id) === Number(item.id)
    )

    if (existingIndex !== -1) {
      addonSelectedItems.value.splice(existingIndex, 1)
      return
    }

    addonSelectedItems.value.push({
      item_id: item.id,
      name: item.name,
      qty: 1,
      price: Number(item.unit_price ?? item.price ?? 0),
      unit: item.unit ?? null,
    })
  }

  const getAddonSelectedQty = (itemId) => {
    const found = addonSelectedItems.value.find(
      (item) => Number(item.item_id) === Number(itemId)
    )

    return found ? Number(found.qty) : 1
  }

  const updateAddonSelectedQty = (itemId, value) => {
    const found = addonSelectedItems.value.find(
      (item) => Number(item.item_id) === Number(itemId)
    )

    if (!found) return

    const qty = Number(value)
    found.qty = qty > 0 ? qty : 1
  }

  const getAddonSelectedSubtotal = (item) => {
    const found = addonSelectedItems.value.find(
      (selected) => Number(selected.item_id) === Number(item.id)
    )

    if (!found) return 0

    return Number(found.qty || 0) * Number(found.price || 0)
  }

  const addonSelectedTotal = computed(() => {
    return addonSelectedItems.value.reduce((sum, item) => {
      return sum + (Number(item.qty || 0) * Number(item.price || 0))
    }, 0)
  })

  // LIVE POST ADD-ONS
  const addSelectedAddonsToCart = async () => {
    if (cart.value.length === 0) {
      showToastMessage('Please select a table first.')
      return
    }

    if (addonSelectedItems.value.length === 0) {
      showToastMessage('Please select at least one add-on item.')
      return
    }

    try {
      isLoading.value = true

      const response = await axios.post(`/${prefix.value}/orders/table-session-addons/store`, {
        table_session_id: cart.value[0].id,
        add_ons: addonSelectedItems.value.map((item) => ({
          item_id: item.item_id,
          name: item.name,
          qty: Number(item.qty),
          price: Number(item.price),
          unit: item.unit ?? null,
        })),
      })

      const updatedAddons = (response.data.addons ?? []).map((addon) => ({
        ...addon,
        quantity: Number(addon.quantity ?? 0),
        unit_price: Number(addon.unit_price ?? 0),
        subtotal: Number(addon.subtotal ?? 0),
      }))

      // 1. immediately update displayed add-ons at right panel
      selectedAddons.value = mapSessionAddons(updatedAddons)

      // 2. immediately update current cart session
      if (cart.value.length > 0) {
        cart.value[0] = {
          ...cart.value[0],
          addons: updatedAddons,
        }
      }

      // 3. immediately update source table_sessions list
      const sessionIndex = table_sessions.value.findIndex(
        (session) => Number(session.id) === Number(cart.value[0].id)
      )

      if (sessionIndex !== -1) {
        table_sessions.value[sessionIndex] = {
          ...table_sessions.value[sessionIndex],
          addons: updatedAddons,
        }
      }

      closeAddonModal()
      showToastMessage(response.data.message || 'Add-ons posted successfully.')
    } catch (error) {
      console.error('Post add-ons error:', error)

      if (error.response?.data?.errors) {
        const firstError = Object.values(error.response.data.errors)[0][0]
        showToastMessage(firstError)
      } else {
        showToastMessage(error.response?.data?.message || 'Failed to post add-ons.')
      }
    } finally {
      isLoading.value = false
    }
  }

  // LEFTOVER
  const leftoverPreviewTotal = computed(() => {
    return Number(leftoverForm.value.price || 0)
  })

  const addLeftoverToCart = () => {
    if (
      leftoverForm.value.weight === '' ||
      leftoverForm.value.weight === null ||
      leftoverForm.value.weight === undefined ||
      Number(leftoverForm.value.price) < 0 ||
      leftoverForm.value.price === ''
    ) {
      showToastMessage('Please complete the leftover details.')
      return
    }

    leftovers.value.push({
      weight: leftoverForm.value.weight,
      price: Number(leftoverForm.value.price),
      remarks: leftoverForm.value.remarks,
      subtotal: Number(leftoverForm.value.price),
    })

    closeLeftoverModal()
  }

  const requestManagerApproval = (type) => {
    console.log('type')
    console.log(type)
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
        console.log(response.data)
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
        manager_id: selectedManager.value?.id,
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

  const baseSubtotal = computed(() => {
    if (cart.value.length === 0) return 0

    return cart.value[0].head_counts.reduce((sum, head) => {
      return sum + Number(head.subtotal || 0)
    }, 0)
  })

  const addonsTotal = computed(() => {
    return selectedAddons.value.reduce((sum, item) => {
      return sum + Number(item.subtotal || 0)
    }, 0)
  })

  const leftoversTotal = computed(() => {
    return leftovers.value.reduce((sum, item) => {
      return sum + Number(item.price || 0)
    }, 0)
  })

  const subtotal = computed(() => {
    return baseSubtotal.value + addonsTotal.value + leftoversTotal.value
  })

  const discountAmount = computed(() => {
    if (!managerApprovedDiscount.value) return 0

    const base = Number(baseSubtotal.value || 0)

    // Senior / PWD
    if (appliedDiscountType.value === 'senior') {
      return base * 0.2
    }

    // Voucher
    if (appliedDiscountType.value === 'voucher' && voucherDiscount.value) {
      const voucherType = String(voucherDiscount.value).trim()

      if (voucherType.includes('%')) {
        const percent = parseFloat(voucherType)
        return isNaN(percent) ? 0 : base * (percent / 100)
      }

      if (
        voucherType === 'Free Meal' ||
        voucherType === 'Free Meal (Bring 2 Companions)'
      ) {
        return base
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
    if (cart.value.length === 0) {
      showToastMessage('Please select a table first.')
      return
    }

    if (paymentMethod.value === 'Cash' && Number(cashAmount.value || 0) < Number(totalPrice.value || 0)) {
      showToastMessage('Insufficient cash amount.')
      return
    }

    isLoading.value = true

    try {
      const sessionId = cart.value[0].id

      const response = await axios.post(`/${prefix.value}/orders/store`, {
        table_session_id: cart.value[0].id,
        heads: cart.value[0].head_counts,
        leftovers: leftovers.value,
        base_subtotal: baseSubtotal.value,
        addons_total: addonsTotal.value,
        leftovers_total: leftoversTotal.value,
        subtotal: subtotal.value,
        total_discount: discountAmount.value,
        total_amount: totalPrice.value,
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
      selectedAddons.value = []
      leftovers.value = []
      resetAddonModalState()
      resetDiscounts()

      table_sessions.value = table_sessions.value.filter((session) => session.id !== sessionId)
    } catch (error) {
      if (error.response) {
        console.error('Validation / Server Error:', error.response.data)

        toastMessage.value = error.response.data.message || 'Checkout failed.'
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
    let result = table_sessions.value

    // search filter
    if (search.value) {
      const keyword = search.value.toLowerCase()
      result = result.filter((session) =>
        (session.table?.name || '').toLowerCase().includes(keyword) ||
        (session.customer_name || '').toLowerCase().includes(keyword) ||
        (session.ref_no || '').toLowerCase().includes(keyword)
      )
    }

    // status filter
    result = result.filter((session) => session.status === 'open')

    return result
  })

  const printReceipt = () => {
    window.open(`/${prefix.value}/orders/${lastOrderNo.value}/pdf`, '_blank')
    showPrintModal.value = false
  }

  const showToastMessage = (message) => {
    toastMessage.value = message
    showToast.value = true

    setTimeout(() => {
      showToast.value = false
    }, 3000)
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
    search,

    table_sessions,
    managers,
    orderable_items,
    is_discount_allowed,

    cart,

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

    showSelectTableModal,
    showAddonModal,
    showLeftoverModal,

    addonSearch,
    addonSelectedItems,
    leftovers,
    leftoverForm,
    selectedAddons,

    filteredAddonItems,

    leftoverPreviewTotal,
    addonSelectedTotal,

    refreshList,
    selectTable,
    removeFromCart,
    removeAddon,
    removeLeftover,
    openAddonModal,
    closeAddonModal,
    openLeftoverModal,
    closeLeftoverModal,

    toggleAddonSelection,
    isAddonSelected,
    getAddonSelectedQty,
    updateAddonSelectedQty,
    getAddonSelectedSubtotal,
    addSelectedAddonsToCart,

    addLeftoverToCart,

    requestManagerApproval,
    handleSeniorPWDChange,
    applyVoucher,
    useManagerAccount,
    closeManagerModal,
    resetDiscounts,

    baseSubtotal,
    addonsTotal,
    leftoversTotal,
    subtotal,
    discountAmount,
    totalPrice,
    changeAmount,

    checkoutSelectedTable,

    filteredTableSessions,
    printReceipt,
    getSessionBaseTotal,
  }
}