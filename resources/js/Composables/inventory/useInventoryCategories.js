import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import useRolePrefix from '@/Composables/useRolePrefix'

export function useInventoryCategories() {
  const { prefix } = useRolePrefix()
  const page = usePage()

  const categories = computed(() => page.props.categories ?? [])

  const search = ref('')
  const showAddModal = ref(false)
  const showEditModal = ref(false)

  const newForm = ref({
    name: '',
    description: '',
    status: 1,
  })

  const editForm = ref({
    id: null,
    name: '',
    description: '',
    status: 1,
  })

  const filteredCategories = computed(() => {
    if (!search.value) return categories.value

    const keyword = search.value.toLowerCase()

    return categories.value.filter((category) =>
      (category.name || '').toLowerCase().includes(keyword)
    )
  })

  const resetNewForm = () => {
    newForm.value = {
      name: '',
      description: '',
      status: 1,
    }
  }

  const resetEditForm = () => {
    editForm.value = {
      id: null,
      name: '',
      description: '',
      status: 1,
    }
  }

  const createCategory = () => {
    resetNewForm()
    showAddModal.value = true
  }

  const closeAddModal = () => {
    showAddModal.value = false
    resetNewForm()
  }

  const editCategory = (category) => {
    editForm.value = {
      id: category.id,
      name: category.name ?? '',
      description: category.description ?? '',
      status: category.status ? 1 : 0,
    }

    showEditModal.value = true
  }

  const closeEditModal = () => {
    showEditModal.value = false
    resetEditForm()
  }

  const saveCategory = () => {
    router.post(`/${prefix.value}/inventory/categories`, newForm.value, {
      onSuccess: () => {
        closeAddModal()
      },
    })
  }

  const updateCategory = () => {
    router.put(`/${prefix.value}/inventory/categories/${editForm.value.id}`, editForm.value, {
      onSuccess: () => {
        closeEditModal()
      },
    })
  }

  const deleteCategory = (category) => {
    if (confirm(`Delete ${category.name}?`)) {
      router.delete(`/${prefix.value}/inventory/categories/${category.id}`)
    }
  }

  return {
    search,
    showAddModal,
    showEditModal,
    newForm,
    editForm,
    filteredCategories,
    createCategory,
    closeAddModal,
    editCategory,
    closeEditModal,
    saveCategory,
    updateCategory,
    deleteCategory,
  }
}