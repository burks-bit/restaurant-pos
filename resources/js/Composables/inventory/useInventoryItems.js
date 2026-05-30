import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import useRolePrefix from '@/Composables/useRolePrefix'

export function useInventoryItems() {
  const { prefix } = useRolePrefix()
  const page = usePage()

  const items = ref(page.props.items ?? [])
  const categories = ref(page.props.categories ?? [])

  const search = ref('')
  const selectedCategory = ref('')

  const showModal = ref(false)
  const mode = ref('in')
  const selected = ref({})
  const form = ref({
    quantity: '',
    unit_price: '',
    note: '',
  })

  const showAddItemModal = ref(false)
  const newItem = ref({
    name: '',
    category_id: '',
    unit: '',
    current_quantity: 0,
    unit_price: 0.00,
    orderable: 0,
    is_dry: 0,
    remarks: '',
  })

  const resetStockForm = () => {
    form.value = {
      quantity: '',
      unit_price: '',
      note: '',
    }
  }

  const resetNewItemForm = () => {
    newItem.value = {
      name: '',
      category_id: '',
      unit: '',
      current_quantity: 0,
      unit_price: 0.00,
      orderable: 0,
      is_dry: 0,
      remarks: '',
    }
  }

  // Add to form refs at the top
  const showPhysicalCountModal = ref(false)
  const physicalCountForm = ref({
    quantity: '',
    note: '',
    adjustment_type: 'physical_count', // fixed value
  })

  const resetPhysicalCountForm = () => {
    physicalCountForm.value = {
      quantity: '',
      note: '',
      adjustment_type: 'physical_count',
    }
  }

  // Add these functions
  const openPhysicalCount = (item) => {
    selected.value = item
    resetPhysicalCountForm()
    showPhysicalCountModal.value = true
  }

  const closePhysicalCountModal = () => {
    showPhysicalCountModal.value = false
    selected.value = {}
    resetPhysicalCountForm()
  }

  const submitPhysicalCount = () => {
    router.post(
      route(`${prefix.value}.inventory.physical-count`, selected.value.id),
      physicalCountForm.value,
      {
        onSuccess: () => {
          closePhysicalCountModal()
        },
      }
    )
  }

  const filteredItems = computed(() => {
    let filtered = items.value

    if (search.value) {
      const keyword = search.value.toLowerCase()
      filtered = filtered.filter((item) =>
        (item.name || '').toLowerCase().includes(keyword)
      )
    }

    if (selectedCategory.value) {
      filtered = filtered.filter(
        (item) => item.category_id === Number(selectedCategory.value)
      )
    }

    return filtered
  })

  const filteredItemsWithUsage = computed(() => {
    return filteredItems.value.map((item) => {
      const usedToday = (item.movements || [])
        .filter((movement) => movement.type === 'stockout')
        .reduce((sum, movement) => sum + Number(movement.quantity || 0), 0)

      return {
        ...item,
        usedToday,
      }
    })
  })

  const totalItems = computed(() => items.value.length)

  const totalStockQuantity = computed(() => {
    return items.value.reduce(
      (sum, item) => sum + Number(item.current_quantity || 0),
      0
    )
  })

  const lowStockItems = computed(() => {
    return items.value.filter((item) => Number(item.current_quantity || 0) <= 5).length
  })

  const openStockIn = (item) => {
    selected.value = item
    mode.value = 'in'
    resetStockForm()
    showModal.value = true
  }

  const openStockOut = (item) => {
    selected.value = item
    mode.value = 'out'
    resetStockForm()
    showModal.value = true
  }

  const closeStockModal = () => {
    showModal.value = false
    selected.value = {}
    resetStockForm()
  }

  const submitStock = () => {
    const routeName =
      mode.value === 'in'
        ? `${prefix.value}.inventory.stock-in`
        : `${prefix.value}.inventory.stock-out`

    router.post(route(routeName, selected.value.id), form.value, {
      onSuccess: () => {
        closeStockModal()
      },
    })
  }

  const openAddItemModal = () => {
    resetNewItemForm()
    showAddItemModal.value = true
  }

  const closeAddItemModal = () => {
    showAddItemModal.value = false
    resetNewItemForm()
  }

  const submitAddItem = () => {
    router.post(route(`${prefix.value}.inventory.items.store`), newItem.value, {
      onSuccess: () => {
        closeAddItemModal()
      },
    })
  }

  return {
    items,
    categories,
    search,
    selectedCategory,

    showModal,
    mode,
    selected,
    form,

    showAddItemModal,
    newItem,

    filteredItems,
    filteredItemsWithUsage,

    totalItems,
    totalStockQuantity,
    lowStockItems,

    openStockIn,
    openStockOut,
    closeStockModal,
    submitStock,

    openAddItemModal,
    closeAddItemModal,
    submitAddItem,

    showPhysicalCountModal,
    physicalCountForm,
    openPhysicalCount,
    closePhysicalCountModal,
    submitPhysicalCount,
  }
}