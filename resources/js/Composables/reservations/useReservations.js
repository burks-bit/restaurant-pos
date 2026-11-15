import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

export function useReservations(reservationsSource) {
  const search = ref('')
  const statusFilter = ref('')
  const showAddModal = ref(false)
  const showEditModal = ref(false)
  const showArrivalModal = ref(false)
  const processing = ref(false)

  const emptyForm = () => ({
    mode: 'scheme',      // 'scheme' | 'package'
    name: '',
    pricing_scheme_id: '',
    pax_breakdown: [],   // [{ head_pricing_rule_id, qty, price_snapshot, subtotal }]
    pax: 0,
    reservation_datetime: '',
    contact_number: '',
    remarks: '',
    status: 'pending',
    reservation_fee: 0,
    fee_payment_method: 'cash',
    fee_reference_no: '',
    shift_id: null,
    cashier_employee_id: null,
    cashier_user_id: null,
    package_id: null,
    package_pax: null,
    package_price: null,
  })

  const newForm = ref(emptyForm())
  const editForm = ref({ id: null, ...emptyForm() })
  const arrivalForm = ref({ reservation_id: null, table_id: null })

  // ── computed pax total from breakdown ──────────────────────────────────────
  const totalPax = (breakdown) =>
    breakdown.reduce((sum, row) => sum + (parseInt(row.qty) || 0), 0)

  const totalEstimated = (breakdown) =>
    breakdown.reduce((sum, row) => sum + (parseFloat(row.subtotal) || 0), 0)

  // ── filters ────────────────────────────────────────────────────────────────
  const filteredReservations = computed(() => {
    let list = reservationsSource.value ?? []
    if (search.value) {
      const kw = search.value.toLowerCase()
      list = list.filter(
        (r) =>
          String(r.name ?? '').toLowerCase().includes(kw) ||
          String(r.contact_number ?? '').toLowerCase().includes(kw) ||
          String(r.status ?? '').toLowerCase().includes(kw),
      )
    }
    if (statusFilter.value) {
      list = list.filter((r) => r.status === statusFilter.value)
    }
    return list
  })

  function formatDateTime(value) {
    if (!value) return '-'
    return new Date(value).toLocaleString('en-PH', {
      year: 'numeric', month: '2-digit', day: '2-digit',
      hour: '2-digit', minute: '2-digit',
    })
  }

  // ── pax breakdown helpers ──────────────────────────────────────────────────
  const initBreakdown = (pricingRules) =>
    pricingRules.map((rule) => ({
      head_pricing_rule_id: rule.id,
      label: rule.label,
      qty: 0,
      price_snapshot: parseFloat(rule.price ?? 0),
      subtotal: 0,
    }))

  const onQtyChange = (breakdown, index) => {
    const row = breakdown[index]
    row.qty = parseInt(row.qty) || 0
    row.subtotal = row.qty * row.price_snapshot
  }

  // ── CRUD ───────────────────────────────────────────────────────────────────
  const openAddModal = (pricingRules) => {
    newForm.value = emptyForm()
    newForm.value.pax_breakdown = initBreakdown(pricingRules)
    showAddModal.value = true
  }

  const openEditModal = (reservation, pricingRules) => {
    const breakdown = initBreakdown(pricingRules)
    // hydrate with saved qty
    if (reservation.reservation_pax?.length) {
      reservation.reservation_pax.forEach((saved) => {
        const match = breakdown.find(
          (b) => b.head_pricing_rule_id === saved.head_pricing_rule_id,
        )
        if (match) {
          match.qty = saved.qty
          match.price_snapshot = parseFloat(saved.price_snapshot)
          match.subtotal = parseFloat(saved.subtotal)
        }
      })
    }
    // console.log('openEditModal', reservation.order?.user)
    editForm.value = {
      id: reservation.id,
      mode: reservation.package_id ? 'package' : 'scheme',
      name: reservation.name,
      pricing_scheme_id: reservation.pricing_scheme_id ?? '',
      pax_breakdown: breakdown,
      pax: reservation.pax,
      reservation_datetime: toDateTimeLocal(reservation.reservation_datetime),
      contact_number: reservation.contact_number ?? '',
      remarks: reservation.remarks ?? '',
      status: reservation.status ?? 'pending',
      reservation_fee: reservation.reservation_fee ?? 0,
      fee_payment_method: reservation.fee_payment_method ?? 'cash',
      fee_reference_no: reservation.fee_reference_no ?? '',
      shift_id: reservation.shift_id ?? null,
      cashier_employee_id: reservation.cashier_employee_id ?? reservation.order?.user?.id,
      cashier_user_id: reservation.cashier_user_id ?? reservation.order?.user?.id ?? null,
      package_id: reservation.package_id ?? null,
      package_pax: reservation.package_pax ?? null,
      package_price: reservation.package_price ?? null,
    }
    showEditModal.value = true
  }

  const openArrivalModal = (reservation) => {
    arrivalForm.value = { reservation_id: reservation.id, table_id: null }
    showArrivalModal.value = true
  }

  const preparePayload = (form) => {
    const base = {
      mode: form.mode,
      name: form.name,
      reservation_datetime: form.reservation_datetime,
      contact_number: form.contact_number,
      remarks: form.remarks,
      status: form.status,
      reservation_fee: form.reservation_fee,
      fee_payment_method: form.fee_payment_method,
      fee_reference_no: form.fee_reference_no,
      shift_id: form.shift_id,
      cashier_user_id: form.cashier_user_id,
    }

    // Package reservation: pax comes from the selected package variant and
    // there is no head-pricing-rule breakdown.
    if (form.mode === 'package') {
      return {
        ...base,
        package_id: form.package_id,
        package_pax: form.package_pax,
        package_price: form.package_price,
        pax: parseInt(form.package_pax) || 0,
      }
    }

    // Pricing-scheme reservation: original behaviour.
    return {
      ...base,
      pricing_scheme_id: form.pricing_scheme_id,
      pax_breakdown: form.pax_breakdown,
      pax: totalPax(form.pax_breakdown),
    }
  }

  // ── package selection ──────────────────────────────────────────────────────
  const selectPackage = (form, pkg, priceRow) => {
    form.package_id = pkg.id
    form.package_pax = priceRow?.pax ?? null
    form.package_price = priceRow?.price ?? null
  }

  const clearPackage = (form) => {
    form.package_id = null
    form.package_pax = null
    form.package_price = null
  }

  const saveReservation = () => {
    if (newForm.value.mode === 'package' && !newForm.value.package_id) {
      alert('Please select a package and a pax/price variant.')
      return
    }
    processing.value = true
    router.post('/frontdoor/reservations', preparePayload(newForm.value), {
      onSuccess: () => { showAddModal.value = false },
      onFinish: () => { processing.value = false },
    })
  }

  const updateReservation = () => {
    if (editForm.value.mode === 'package' && !editForm.value.package_id) {
      alert('Please select a package and a pax/price variant.')
      return
    }
    processing.value = true
    router.put(
      `/frontdoor/reservations/${editForm.value.id}`,
      preparePayload(editForm.value),
      {
        onSuccess: () => { showEditModal.value = false },
        onFinish: () => { processing.value = false },
      },
    )
  }

  const deleteReservation = (reservation) => {
    if (!confirm(`Delete reservation for ${reservation.name}?`)) return
    router.delete(`/frontdoor/reservations/${reservation.id}`)
  }

  const goToAdmission = (reservation) => {
    router.visit(route('frontdoor.table_admission'), {
      method: 'get',
      data: { reservation_id: reservation.id },
    })
  }

  const toDateTimeLocal = (value) => {
    if (!value) return ''
    const d = new Date(value)
    const pad = (n) => String(n).padStart(2, '0')
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
  }

  const statusClass = (status) => ({
    pending:   'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-green-100 text-green-800',
    seated:    'bg-blue-100 text-blue-800',
    completed: 'bg-gray-100 text-gray-700',
    cancelled: 'bg-red-100 text-red-700',
  }[status] ?? 'bg-gray-100 text-gray-600')

  const formatCurrency = (value) =>
    Number(value ?? 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })

  return {
    // state
    search, statusFilter, processing,
    showAddModal, showEditModal, showArrivalModal,
    newForm, editForm, arrivalForm,
    // computed
    filteredReservations,
    // helpers
    totalPax, totalEstimated, onQtyChange,
    selectPackage, clearPackage,
    // actions
    openAddModal, openEditModal, openArrivalModal,
    saveReservation, updateReservation, deleteReservation, goToAdmission,
    // formatting
    formatDateTime, statusClass, formatCurrency,
  }
}