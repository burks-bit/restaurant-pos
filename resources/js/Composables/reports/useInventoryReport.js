import { ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import useRolePrefix from '@/Composables/useRolePrefix'

export function useInventoryReport() {
  const { prefix } = useRolePrefix()
  const page = usePage()

  const categories = ref(page.props.categories ?? [])

  const selectedCategory = ref('')
  const InventoryRpType = ref('')
  const InventoryItemType = ref('')
  const startDate = ref('')
  const endDate = ref('')

  const isLoading = ref(false)
  const inventoryMovements = ref([])

  const fetchInventoryMovements = async () => {
    if (!InventoryRpType.value) {
      alert('Please select a report type first.')
      return
    }
    if (!InventoryItemType.value) {
      alert('Please select an item type first.')
      return
    }

    try {
      isLoading.value = true

      const response = await axios.get(
        route(`${prefix.value}.inventory.fetch-inventory-report`),
        {
          params: {
            type: InventoryRpType.value,
            item_type: InventoryItemType.value,
            start_date: startDate.value,
            end_date: endDate.value,
            category_id: selectedCategory.value || '',
          },
        }
      )

      inventoryMovements.value = response.data.data ?? []
    } catch (error) {
      console.error(error)
      alert('Failed to fetch inventory data.')
    } finally {
      isLoading.value = false
    }
  }

  const generatePdfReport = () => {
    if (inventoryMovements.value.length === 0) {
      alert('No data to print. Please fetch data first.')
      return
    }

    const url = route(`${prefix.value}.inventory.print-inventory-report-pdf`, {
      type: InventoryRpType.value,
      item_type: InventoryItemType.value,
      start_date: startDate.value,
      end_date: endDate.value,
      category_id: selectedCategory.value || '',
    })

    window.open(url, '_blank')
  }

  const generateExcelReport = () => {
    if (inventoryMovements.value.length === 0) {
      alert('No data to export. Please fetch data first.')
      return
    }

    const url = route(`${prefix.value}.inventory.export-inventory-report-excel`, {
      type: InventoryRpType.value,
      item_type: InventoryItemType.value,
      start_date: startDate.value,
      end_date: endDate.value,
      category_id: selectedCategory.value || '',
    })

    window.location.href = url  // triggers file download
  }

  return {
    categories,
    selectedCategory,
    InventoryRpType,
    InventoryItemType,
    startDate,
    endDate,
    isLoading,
    inventoryMovements,
    fetchInventoryMovements,
    generatePdfReport,
    generateExcelReport
  }
}