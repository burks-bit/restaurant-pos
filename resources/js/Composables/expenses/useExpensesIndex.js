import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import useRolePrefix from '@/Composables/useRolePrefix'
import axios from 'axios'

export function useExpensesIndex() {
  const { prefix } = useRolePrefix()
  const page = usePage()

  const expenses = ref(page.props.expenses ?? [])
  const categories = ref(page.props.categories ?? [])

  const search = ref('')
  const selectedCategory = ref('')
  const startDate = ref('')
  const endDate = ref('')

  const isLoading = ref(false)
  const showAddExpenseModal = ref(false)

  const newExpense = ref({
    expense_category_id: '',
    description: '',
    amount: 0,
    expense_date: '',
  })

  const totalExpenses = computed(() => {
    return expenses.value.reduce((sum, expense) => sum + Number(expense.amount || 0), 0)
  })

  const resetNewExpense = () => {
    newExpense.value = {
      expense_category_id: '',
      description: '',
      amount: 0,
      expense_date: '',
    }
  }

  const openAddExpenseModal = () => {
    resetNewExpense()
    showAddExpenseModal.value = true
  }

  const closeAddExpenseModal = () => {
    showAddExpenseModal.value = false
    resetNewExpense()
  }

  const submitExpense = () => {
    router.post(route(`${prefix.value}.expenses.store`), newExpense.value, {
      onSuccess: () => {
        closeAddExpenseModal()
      },
    })
  }

  const applyFilters = async () => {
    try {
      isLoading.value = true

      const response = await axios.post(
        route(`${prefix.value}.expenses.fetch-filtered-expenses`),
        {
          search: search.value,
          category: selectedCategory.value,
          start_date: startDate.value,
          end_date: endDate.value,
        }
      )

      expenses.value = response.data.expenses ?? []
    } catch (error) {
      console.error(error)
    } finally {
      isLoading.value = false
    }
  }

  const deleteExpense = (id) => {
    if (confirm('Are you sure?')) {
      router.delete(route('expenses.destroy', id))
    }
  }

  return {
    prefix,
    expenses,
    categories,

    search,
    selectedCategory,
    startDate,
    endDate,

    isLoading,
    showAddExpenseModal,
    newExpense,

    totalExpenses,

    openAddExpenseModal,
    closeAddExpenseModal,
    submitExpense,
    applyFilters,
    deleteExpense,
  }
}