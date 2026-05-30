import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import useRolePrefix from '@/Composables/useRolePrefix'

export function useInventorySummary() {
  const { prefix } = useRolePrefix()
  const page = usePage()

  const items = ref(page.props.items ?? [])
  const inventoryMovements = ref(page.props.inventoryMovements ?? [])
  const categories = ref(page.props.categories ?? [])

  const selectedCategory = ref('')
  const InventoryRpType = ref('')
  const search = ref(page.props.filters?.search ?? '')
  const startDate = ref('')
  const endDate = ref('')

  const filteredItems = computed(() => {
    return items.value.filter((item) => {
      const matchesSearch =
        !search.value ||
        (item.name || '').toLowerCase().includes(search.value.toLowerCase())

      const matchesCategory =
        !selectedCategory.value || item.category_id == selectedCategory.value

      return matchesSearch && matchesCategory
    })
  })

  const itemsWithMovement = computed(() => {
    return filteredItems.value.map((item) => {
      const filteredMovements = inventoryMovements.value.filter((movement) => {
        const movementDate = movement.created_at?.split('T')[0] ?? ''
        const withinStart = !startDate.value || movementDate >= startDate.value
        const withinEnd = !endDate.value || movementDate <= endDate.value

        return (
          movement.inventory_item_id === item.id &&
          withinStart &&
          withinEnd
        )
      })

      const stockInRange = filteredMovements
        .filter((m) => m.type === 'stockin')
        .reduce((sum, m) => sum + Number(m.quantity || 0), 0)

      const stockOutRange = filteredMovements
        .filter((m) => m.type === 'stockout')
        .reduce((sum, m) => sum + Number(m.quantity || 0), 0)

      // ✅ These must be INSIDE the .map() callback
      const actualCount = filteredMovements
        .filter((m) => m.type === 'adjustment' && m.adjustment_type === 'physical_count')
        .reduce((sum, m) => sum + Number(m.quantity || 0), 0)

      const finalCount = actualCount + stockInRange - stockOutRange

      return {
        ...item,
        stockInRange,
        stockOutRange,
        actualCount,   // ✅
        finalCount,    // ✅
      }
    })
  })

  const filteredInventoryMovements = computed(() => {
    return inventoryMovements.value.filter((movement) => {
      const item = movement.item
      const movementDate = movement.created_at?.split('T')[0] ?? ''

      const matchesSearch =
        !search.value ||
        (item?.name || '').toLowerCase().includes(search.value.toLowerCase())

      const matchesCategory =
        !selectedCategory.value ||
        item?.category_id == selectedCategory.value

      const withinStart = !startDate.value || movementDate >= startDate.value
      const withinEnd = !endDate.value || movementDate <= endDate.value

      return matchesSearch && matchesCategory && withinStart && withinEnd
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

  const generatePdfReport = () => {
    if (!InventoryRpType.value) {
      alert('Please select a report type first.')
      return
    }

    const url = route(`${prefix.value}.inventory.general-report`, {
      type: InventoryRpType.value,
      start_date: startDate.value,
      end_date: endDate.value,
      category_id: selectedCategory.value || '',
      search: search.value || '',
    })

    window.open(url, '_blank')
  }

  return {
    items,
    inventoryMovements,
    categories,
    selectedCategory,
    InventoryRpType,
    search,
    startDate,
    endDate,
    filteredItems,
    itemsWithMovement,
    filteredInventoryMovements,
    totalItems,
    totalStockQuantity,
    lowStockItems,
    generatePdfReport,
  }
}