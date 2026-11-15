import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import useRolePrefix from '@/Composables/useRolePrefix'

export function usePackages() {
  const { prefix } = useRolePrefix()
  const page = usePage()

  const packages = computed(() => page.props.packages ?? [])

  const search = ref('')
  const showModal = ref(false)
  const isEditing = ref(false)
  const editingId = ref(null)

  const emptyForm = () => ({
    name: '',
    choices: '',
    description: '',
    is_active: true,
    prices: [{ pax: '', price: '' }],
  })

  const form = ref(emptyForm())

  const filteredPackages = computed(() => {
    const keyword = search.value.trim().toLowerCase()

    if (!keyword) return packages.value

    return packages.value.filter((pkg) => {
      const name = (pkg.name ?? '').toLowerCase()
      const choices = (pkg.choices ?? '').toLowerCase()

      return name.includes(keyword) || choices.includes(keyword)
    })
  })

  const openCreate = () => {
    isEditing.value = false
    editingId.value = null
    form.value = emptyForm()
    showModal.value = true
  }

  const openEdit = (pkg) => {
    isEditing.value = true
    editingId.value = pkg.id

    form.value = {
      name: pkg.name ?? '',
      choices: pkg.choices ?? '',
      description: pkg.description ?? '',
      is_active: !!pkg.is_active,
      prices: (pkg.prices ?? []).length
        ? pkg.prices.map((row) => ({ pax: row.pax, price: row.price }))
        : [{ pax: '', price: '' }],
    }

    showModal.value = true
  }

  const closeModal = () => {
    showModal.value = false
    form.value = emptyForm()
  }

  const addPriceRow = () => {
    form.value.prices.push({ pax: '', price: '' })
  }

  const removePriceRow = (index) => {
    form.value.prices.splice(index, 1)
  }

  // A package can only have one price per pax count.
  const isPaxSelected = (pax, currentIndex) => {
    if (pax === '' || pax === null || pax === undefined) return false

    return form.value.prices.some(
      (row, index) => index !== currentIndex && String(row.pax) === String(pax)
    )
  }

  const hasDuplicatePax = computed(() => {
    const seen = new Set()

    for (const row of form.value.prices) {
      if (row.pax === '' || row.pax === null || row.pax === undefined) continue

      const key = String(row.pax)
      if (seen.has(key)) return true
      seen.add(key)
    }

    return false
  })

  const savePackage = () => {
    if (!form.value.name.trim()) {
      alert('Please enter a package name.')
      return
    }

    if (hasDuplicatePax.value) {
      alert('Each pax count can only appear once per package.')
      return
    }

    const payload = {
      name: form.value.name,
      choices: form.value.choices,
      description: form.value.description,
      is_active: form.value.is_active,
      prices: form.value.prices
        .filter((row) => row.pax !== '' && row.pax !== null)
        .map((row) => ({ pax: row.pax, price: row.price === '' ? 0 : row.price })),
    }

    const options = { onSuccess: () => closeModal() }

    if (isEditing.value) {
      router.put(route(`${prefix.value}.packages.update`, { package: editingId.value }), payload, options)
    } else {
      router.post(route(`${prefix.value}.packages.store`), payload, options)
    }
  }

  const deletePackage = (pkg) => {
    if (confirm(`Delete package "${pkg.name}"?`)) {
      router.delete(route(`${prefix.value}.packages.destroy`, { package: pkg.id }))
    }
  }

  const formatCurrency = (value) =>
    new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))

  return {
    packages,
    search,
    filteredPackages,

    showModal,
    isEditing,
    editingId,
    form,

    openCreate,
    openEdit,
    closeModal,
    addPriceRow,
    removePriceRow,
    isPaxSelected,
    hasDuplicatePax,
    savePackage,
    deletePackage,
    formatCurrency,
  }
}
