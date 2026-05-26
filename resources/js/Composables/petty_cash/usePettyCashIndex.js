import { ref, computed, reactive } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import axios from 'axios'
import useRolePrefix from '@/Composables/useRolePrefix'

export function usePettyCashIndex() {
  const { prefix } = useRolePrefix()
  const { props } = usePage()

  const DENOMS = [1, 5, 10, 20, 50, 100, 200, 500, 1000]

  const pettyCashes = ref(props.pettyCashes ?? [])

  const showModal = ref(false)
  const showNewModal = ref(false)
  const selectedPettyCash = ref(null)
  const selectedDate = ref(new Date().toISOString().slice(0, 10))
  const search = ref('')

  const hasRemaining = computed(() => {
    if (!selectedPettyCash.value) return false
    return Number(selectedPettyCash.value.remaining) > 0
  })

  const newPettyCash = reactive({
    date: new Date().toISOString().slice(0, 10),
    notes: '',
  })

  const newPettyCashRows = ref([])

  const newUsage = reactive({
    purpose: '',
    notes: '',
  })

  const newUsageRows = ref([])

  function addNewPettyRow() {
    newPettyCashRows.value.push({ denom: null, qty: 0 })
  }

  function removeNewPettyRow(idx) {
    newPettyCashRows.value.splice(idx, 1)
  }

  const newPettyCashTotal = computed(() => {
    return newPettyCashRows.value.reduce((sum, row) => {
      const denom = Number(row.denom || 0)
      const qty = Number(row.qty || 0)
      return sum + denom * qty
    }, 0)
  })

  function addUsageRow() {
    newUsageRows.value.push({ denom: null, qty: 0 })
  }

  function removeUsageRow(idx) {
    newUsageRows.value.splice(idx, 1)
  }

  const usageTotal = computed(() => {
    return newUsageRows.value.reduce((sum, row) => {
      const denom = Number(row.denom || 0)
      const qty = Number(row.qty || 0)
      return sum + denom * qty
    }, 0)
  })

  function denomArrayToMap(arr) {
    const map = {}
    if (!Array.isArray(arr)) return map

    for (const row of arr) {
      const denom = Number(row?.denom || 0)
      const qty = Number(row?.qty || 0)

      if (!denom || qty <= 0) continue
      map[denom] = (map[denom] || 0) + qty
    }

    return map
  }

  const usedCoinsMap = computed(() => {
    const map = {}
    const details = selectedPettyCash.value?.details || []

    for (const detail of details) {
      const denMap = denomArrayToMap(detail?.denominations)

      for (const [denomStr, qty] of Object.entries(denMap)) {
        const denom = Number(denomStr)
        map[denom] = (map[denom] || 0) + Number(qty || 0)
      }
    }

    return map
  })

  const startCoinsMap = computed(() => {
    return denomArrayToMap(selectedPettyCash.value?.denominations || [])
  })

  const usageCoinRows = computed(() => {
    const used = usedCoinsMap.value
    const start = startCoinsMap.value

    const denoms = new Set([
      ...Object.keys(start).map(Number),
      ...Object.keys(used).map(Number),
    ])

    return Array.from(denoms)
      .filter((denom) => denom > 0)
      .sort((a, b) => a - b)
      .map((denom) => {
        const startQty = Number(start[denom] || 0)
        const usedQty = Number(used[denom] || 0)
        const balanceQty = Math.max(startQty - usedQty, 0)

        return {
          denom,
          startQty,
          usedQty,
          usedTotal: denom * usedQty,
          balanceQty,
          balanceTotal: denom * balanceQty,
        }
      })
  })

  const usageCoinsGrandTotal = computed(() => {
    return usageCoinRows.value.reduce((sum, row) => sum + row.usedTotal, 0)
  })

  const balanceCoinsGrandTotal = computed(() => {
    return usageCoinRows.value.reduce((sum, row) => sum + row.balanceTotal, 0)
  })

  const filteredPettyCashes = computed(() => {
    let result = pettyCashes.value

    if (selectedDate.value) {
      result = result.filter((pettyCash) => {
        const pettyCashDate = (pettyCash.date || '').slice(0, 10)
        return pettyCashDate === selectedDate.value
      })
    }

    if (search.value) {
      const keyword = search.value.toLowerCase()
      result = result.filter((pettyCash) =>
        (pettyCash.notes || '').toLowerCase().includes(keyword) ||
        pettyCash.details?.some((detail) =>
          (detail.purpose || '').toLowerCase().includes(keyword)
        )
      )
    }

    return result
  })

  function openModal(pettyCash) {
    selectedPettyCash.value = {
      ...pettyCash,
      total_amount: Number(pettyCash.total_amount),
      amount_used: Number(pettyCash.amount_used),
      remaining: Number(pettyCash.remaining),
    }

    newUsage.purpose = ''
    newUsage.notes = ''
    newUsageRows.value = [{ denom: null, qty: 0 }]

    showModal.value = true
  }

  function openNewPettyCashModal() {
    showNewModal.value = true
    newPettyCashRows.value = [{ denom: null, qty: 0 }]
    newPettyCash.notes = ''
    newPettyCash.date = new Date().toISOString().slice(0, 10)
  }

  function closeNewModal() {
    showNewModal.value = false
  }

  function closeDetailsModal() {
    showModal.value = false
  }

  function validateRows(rows) {
    if (!rows.length) {
      return { ok: false, msg: 'Please add at least one denomination row.' }
    }

    for (const row of rows) {
      const denom = Number(row.denom || 0)
      const qty = Number(row.qty || 0)

      if (!denom) {
        return { ok: false, msg: 'Please select a denomination on each row.' }
      }

      if (qty <= 0) {
        return { ok: false, msg: 'Quantity must be greater than 0.' }
      }
    }

    return { ok: true, msg: '' }
  }

  async function postNewPettyCash() {
    const check = validateRows(newPettyCashRows.value)
    if (!check.ok) {
      alert(check.msg)
      return
    }

    const total = newPettyCashTotal.value
    if (total <= 0) {
      alert('Computed total must be greater than 0.')
      return
    }

    const payload = {
      date: newPettyCash.date,
      total_amount: total,
      denominations: newPettyCashRows.value.map((row) => ({
        denom: Number(row.denom),
        qty: Number(row.qty),
      })),
      notes: newPettyCash.notes,
    }

    try {
      const response = await axios.post(
        route(`${prefix.value}.petty-cashes.store`),
        payload
      )

      pettyCashes.value.unshift(response.data.pettyCash)
      showNewModal.value = false
    } catch (error) {
      console.error(error)
      alert('Something went wrong.')
    }
  }

  async function postUsage() {
    if (!hasRemaining.value) {
      alert('No remaining petty cash balance.')
      return
    }

    if (!newUsage.purpose) {
      alert('Purpose is required')
      return
    }

    const check = validateRows(newUsageRows.value)
    if (!check.ok) {
      alert(check.msg)
      return
    }

    const amount = usageTotal.value
    if (amount <= 0) {
      alert('Computed usage amount must be greater than 0.')
      return
    }

    if (amount > Number(selectedPettyCash.value.remaining)) {
      alert('Amount exceeds remaining balance.')
      return
    }

    const payload = {
      purpose: newUsage.purpose,
      amount,
      denominations: newUsageRows.value.map((row) => ({
        denom: Number(row.denom),
        qty: Number(row.qty),
      })),
      notes: newUsage.notes,
    }

    try {
      const response = await axios.post(
        route(`${prefix.value}.petty-cashes.details.store`, {
          pettyCash: selectedPettyCash.value.id,
        }),
        payload
      )

      const updated = response.data.pettyCash
      const index = pettyCashes.value.findIndex((pettyCash) => pettyCash.id === updated.id)

      if (index !== -1) {
        pettyCashes.value[index] = updated
      }

      selectedPettyCash.value = updated
      newUsage.purpose = ''
      newUsage.notes = ''
      newUsageRows.value = [{ denom: null, qty: 0 }]
    } catch (error) {
      console.error(error)
      alert('Something went wrong.')
    }
  }

  function filterByDate() {
    router.get(
      route(`${prefix.value}.petty-cashes.index`),
      { date: selectedDate.value },
      { preserveState: true, replace: true }
    )
  }

  return {
    DENOMS,
    pettyCashes,
    showModal,
    showNewModal,
    selectedPettyCash,
    selectedDate,
    search,
    hasRemaining,
    newPettyCash,
    newPettyCashRows,
    newPettyCashTotal,
    newUsage,
    newUsageRows,
    usageTotal,
    usageCoinRows,
    usageCoinsGrandTotal,
    balanceCoinsGrandTotal,
    filteredPettyCashes,
    addNewPettyRow,
    removeNewPettyRow,
    addUsageRow,
    removeUsageRow,
    openModal,
    openNewPettyCashModal,
    closeNewModal,
    closeDetailsModal,
    postNewPettyCash,
    postUsage,
    filterByDate,
  }
}