import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import useRolePrefix from '@/Composables/useRolePrefix'

export function useExpenseReport() {
  const { prefix } = useRolePrefix()
  const page = usePage()

  const categories = ref(page.props.categories ?? [])

  const selectedCategory = ref('')
  const startDate = ref('')
  const endDate = ref('')

  const isLoading = ref(false)
  const expenses = ref([])

  const grandTotal = computed(() => {
    return expenses.value.reduce((sum, expense) => {
      return sum + Number(expense.amount || 0)
    }, 0)
  })

  const fetchExpenses = async () => {
    try {
      isLoading.value = true

      const response = await axios.get(
        route(`${prefix.value}.expenses.fetch-expense-report`),
        {
          params: {
            start_date: startDate.value,
            end_date: endDate.value,
            category_id: selectedCategory.value || '',
          },
        }
      )

      expenses.value = response.data.data ?? []
    } catch (error) {
      console.error(error)
      alert('Failed to fetch expense report.')
    } finally {
      isLoading.value = false
    }
  }

  const generatePdfReport = () => {
    if (expenses.value.length === 0) {
      alert('No data to print. Please fetch data first.')
      return
    }

    const url = route(`${prefix.value}.expenses.print-expense-report-pdf`, {
      start_date: startDate.value,
      end_date: endDate.value,
      category_id: selectedCategory.value || '',
    })

    window.open(url, '_blank')
  }

  return {
    categories,
    selectedCategory,
    startDate,
    endDate,
    isLoading,
    expenses,
    grandTotal,
    fetchExpenses,
    generatePdfReport,
  }
}