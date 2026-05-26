import { ref, computed, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import useRolePrefix from '@/Composables/useRolePrefix'

export function useCashRegisterIndex(props) {
  const { prefix } = useRolePrefix()

  const DENOMS = [1, 5, 10, 20, 50, 100, 200, 500, 1000]

  const sales           = computed(() => props.cashRegisters  ?? [])
  const shifts          = computed(() => props.shifts         ?? [])
  const totalCashSales  = computed(() => props.totalCashSales ?? 0)
  const totalGcashSales = computed(() => props.totalGcashSales ?? 0)
  const cashierExpenses = computed(() => props.cashierExpenses ?? 0)
  const shiftStart      = computed(() => props.shiftStart     ?? null)
  const shiftEnd        = computed(() => props.shiftEnd       ?? null)
  const currentShift    = computed(() => props.currentShift   ?? null)

  // FIX #3: paymentSummary exposed from props through composable
  const paymentSummary  = computed(() => props.paymentSummary ?? [])

  const isLoading     = ref(false)
  const showNewModal  = ref(false)
  const showViewModal = ref(false)
  const selectedSale  = ref(null)
  const selectedDate  = ref(new Date().toISOString().slice(0, 10))
  const search        = ref('')

  // ─── New Sale Form ────────────────────────────────────────────────────────────

  const newSale = reactive({
    date:       new Date().toISOString().slice(0, 10),
    shift_id:   null,
    gross_sales: 0,
    notes:      '',
  })

  const deductionRows = ref([])
  const denomRows     = ref([])

  // ─── Computed ─────────────────────────────────────────────────────────────────

  const totalDeductions = computed(() =>
    deductionRows.value.reduce((sum, r) => sum + Number(r.amount || 0), 0)
  )

  const computedNetSales = computed(() =>
    Math.max(Number(newSale.gross_sales || 0) - totalDeductions.value, 0)
  )

  const cashOnHandTotal = computed(() =>
    denomRows.value.reduce((sum, r) => sum + Number(r.denom || 0) * Number(r.qty || 0), 0)
  )

  // FIX: expectedCashOnHand now sums ALL payment methods minus expenses
  const expectedCashOnHand = computed(() => {
    const totalPayments = paymentSummary.value.reduce((sum, pm) => sum + Number(pm.total || 0), 0)
    return Math.max(totalPayments - Number(cashierExpenses.value), 0)
  })

  const overShortNewValue = computed(() => cashOnHandTotal.value - expectedCashOnHand.value)

  const overShortNewLabel = computed(() => {
    if (overShortNewValue.value > 0) return 'Over'
    if (overShortNewValue.value < 0) return 'Short'
    return 'Balanced'
  })

  const overShortNewClass = computed(() => {
    if (overShortNewValue.value > 0) return 'bg-green-50 border-green-200 text-green-700'
    if (overShortNewValue.value < 0) return 'bg-red-50 border-red-200 text-red-700'
    return 'bg-gray-50 border-gray-200 text-gray-600'
  })

  // ─── Over/Short helpers for table rows ────────────────────────────────────────

  function overShortLabel(sale) {
    const diff = Number(sale.cash_on_hand) - Number(sale.expected_cash_on_hand)
    if (diff > 0) return 'Over'
    if (diff < 0) return 'Short'
    return 'Balanced'
  }

  function overShortClass(sale) {
    const diff = Number(sale.cash_on_hand) - Number(sale.expected_cash_on_hand)
    if (diff > 0) return 'text-green-600'
    if (diff < 0) return 'text-red-600'
    return 'text-gray-400'
  }

  // ─── Filtered List ─────────────────────────────────────────────────────────────

  const filteredSales = computed(() => {
    let result = sales.value

    if (selectedDate.value) {
      result = result.filter((s) => {
        const recordDate = (s.created_at || '').slice(0, 10)
        return recordDate === selectedDate.value
      })
    }

    if (search.value) {
      const kw = search.value.toLowerCase()
      result = result.filter(
        (s) =>
          (s.cashier?.name || '').toLowerCase().includes(kw) ||
          (s.shift?.name   || '').toLowerCase().includes(kw)
      )
    }

    return result
  })

  // ─── Row Helpers ──────────────────────────────────────────────────────────────

  function addDeductionRow()        { deductionRows.value.push({ label: '', amount: 0 }) }
  function removeDeductionRow(idx)  { deductionRows.value.splice(idx, 1) }
  function addDenomRow()            { denomRows.value.push({ denom: null, qty: 0 }) }
  function removeDenomRow(idx)      { denomRows.value.splice(idx, 1) }

  // ─── Modal Controls ───────────────────────────────────────────────────────────

  function openNewSaleModal() {
    showNewModal.value  = true
    newSale.date        = new Date().toISOString().slice(0, 10)
    newSale.shift_id    = null
    newSale.gross_sales = 0
    newSale.notes       = ''
    deductionRows.value = []
    denomRows.value     = [{ denom: null, qty: 0 }]
  }

  function closeNewModal()  { showNewModal.value  = false }

  function openViewModal(sale) {
    selectedSale.value  = { ...sale }
    showViewModal.value = true
  }

  function closeViewModal() { showViewModal.value = false }

  // ─── Edit Modal ───────────────────────────────────────────────────────────────

  const showEditModal = ref(false)
  const editSale = reactive({
    id:                    null,
    date:                  '',
    shift_id:              null,
    expected_cash_on_hand: 0,
    // FIX #4: gross_sales kept in state only for legacy binding safety,
    //         but is no longer rendered or sent to the backend
    gross_sales:           0,
    notes:                 '',
  })
  const editDenomRows = ref([])

  function openEditModal(sale) {
    editSale.id                    = sale.id
    editSale.shift_id              = sale.shift?.id ?? null
    editSale.notes                 = sale.notes ?? ''
    editSale.expected_cash_on_hand = Number(sale.expected_cash_on_hand)
    editDenomRows.value            = sale.denomination?.map(d => ({
      denom: Number(d.denom),
      qty:   Number(d.qty),
    })) ?? [{ denom: null, qty: 0 }]
    showEditModal.value = true
  }

  function closeEditModal() { showEditModal.value = false }

  const editCashOnHandTotal = computed(() =>
    editDenomRows.value.reduce((sum, r) => sum + Number(r.denom || 0) * Number(r.qty || 0), 0)
  )

  const editOverShortValue = computed(() =>
    editCashOnHandTotal.value - Number(editSale.expected_cash_on_hand || 0)
  )

  const editOverShortLabel = computed(() => {
    if (editOverShortValue.value > 0) return 'Over'
    if (editOverShortValue.value < 0) return 'Short'
    return 'Balanced'
  })

  const editOverShortClass = computed(() => {
    if (editOverShortValue.value > 0) return 'bg-green-50 border-green-200 text-green-700'
    if (editOverShortValue.value < 0) return 'bg-red-50 border-red-200 text-red-700'
    return 'bg-gray-50 border-gray-200 text-gray-600'
  })

  function addEditDenomRow()        { editDenomRows.value.push({ denom: null, qty: 0 }) }
  function removeEditDenomRow(idx)  { editDenomRows.value.splice(idx, 1) }

  // ─── Update Net Sales ─────────────────────────────────────────────────────────

  function updateNetSales() {
    if (!editSale.shift_id) {
      alert('Please select a shift.')
      return
    }

    const check = validateDenomRows(editDenomRows.value)
    if (!check.ok) {
      alert(check.msg)
      return
    }

    if (editCashOnHandTotal.value <= 0) {
      alert('Cash on hand total must be greater than 0.')
      return
    }

    const payload = {
      shift_id:     editSale.shift_id,
      cash_on_hand: editCashOnHandTotal.value,
      notes:        editSale.notes,
      denomination: editDenomRows.value.map((r) => ({
        denom: Number(r.denom),
        qty:   Number(r.qty),
      })),
    }

    router.put(
      route(`${prefix.value}.cash-registers.update`, editSale.id),
      payload,
      {
        preserveScroll: true,
        preserveState:  true,
        onStart:   () => { isLoading.value = true },
        onSuccess: () => { showEditModal.value = false },
        onFinish:  () => { isLoading.value = false },
        onError: (errors) => {
          const messages = Object.values(errors).flat().join('\n')
          alert(messages || 'Something went wrong while updating the record.')
        },
      }
    )
  }

  // ─── Validation ───────────────────────────────────────────────────────────────

  function validateDenomRows(rows) {
    if (!rows.length) return { ok: false, msg: 'Please add at least one denomination row.' }
    for (const row of rows) {
      if (!row.denom)            return { ok: false, msg: 'Please select a denomination on each row.' }
      if (Number(row.qty) <= 0)  return { ok: false, msg: 'Quantity must be greater than 0.' }
    }
    return { ok: true, msg: '' }
  }

  // ─── Post Net Sales ───────────────────────────────────────────────────────────

  function postNetSales() {
    if (!newSale.shift_id) {
      alert('Please select a shift.')
      return
    }

    const check = validateDenomRows(denomRows.value)
    if (!check.ok) {
      alert(check.msg)
      return
    }

    if (cashOnHandTotal.value <= 0) {
      alert('Cash on hand total must be greater than 0.')
      return
    }

    const payload = {
      shift_id:     newSale.shift_id,
      cash_on_hand: cashOnHandTotal.value,
      denomination: denomRows.value.map((r) => ({
        denom: Number(r.denom),
        qty:   Number(r.qty),
      })),
      notes: newSale.notes,
    }

    router.post(route(`${prefix.value}.cash-registers.store`), payload, {
      preserveScroll: true,
      preserveState:  true,
      onStart:   () => { isLoading.value = true },
      onSuccess: () => {
        showNewModal.value = false
        newSale.shift_id   = null
        newSale.notes      = ''
        denomRows.value    = [{ denom: null, qty: 0 }]
      },
      onFinish: () => { isLoading.value = false },
      onError: (errors) => {
        const messages = Object.values(errors).flat().join('\n')
        alert(messages || 'Something went wrong while posting net sales.')
      },
    })
  }

  // ─── Filter by Date ───────────────────────────────────────────────────────────

  function filterByDate() {
    router.get(
      route(`${prefix.value}.cash-registers.index`),
      { date: selectedDate.value },
      {
        preserveState:  true,
        preserveScroll: true,
        replace:        true,
        onStart:  () => { isLoading.value = true },
        onFinish: () => { isLoading.value = false },
        onError:  (errors) => {
          console.error(errors)
          alert('Failed to load cash records.')
        },
      }
    )
  }

  // ─── Return ───────────────────────────────────────────────────────────────────

  return {
    shifts,
    DENOMS,
    sales,
    showNewModal,
    showViewModal,
    selectedSale,
    selectedDate,
    search,
    filteredSales,
    newSale,
    deductionRows,
    denomRows,
    totalDeductions,
    computedNetSales,
    cashOnHandTotal,
    overShortNewValue,
    overShortNewLabel,
    overShortNewClass,
    overShortLabel,
    overShortClass,
    addDeductionRow,
    removeDeductionRow,
    addDenomRow,
    removeDenomRow,
    openNewSaleModal,
    closeNewModal,
    openViewModal,
    closeViewModal,
    postNetSales,
    filterByDate,
    isLoading,
    showEditModal,
    editSale,
    editDenomRows,
    editCashOnHandTotal,
    editOverShortValue,
    editOverShortLabel,
    editOverShortClass,
    openEditModal,
    closeEditModal,
    addEditDenomRow,
    removeEditDenomRow,
    updateNetSales,
    totalCashSales,
    totalGcashSales,
    cashierExpenses,
    currentShift,
    shiftStart,
    shiftEnd,
    expectedCashOnHand,
    paymentSummary,     // FIX #3: exported so Index.vue can destructure it
  }
}