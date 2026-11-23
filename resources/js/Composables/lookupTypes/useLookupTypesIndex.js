import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

export function useLookupTypesIndex({ pageProp, basePath }) {
  const page = usePage()
  const items = computed(() => page.props[pageProp] ?? [])
  const flash = computed(() => page.props.flash ?? {})
  const errors = computed(() => page.props.errors ?? {})

  const search = ref('')
  const showAddModal = ref(false)
  const showEditModal = ref(false)

  const emptyForm = () => ({ name: '', description: '', status: 1 })

  const newForm = ref(emptyForm())
  const editForm = ref({ id: null, ...emptyForm() })

  const filteredItems = computed(() => {
    if (!search.value) return items.value
    const keyword = search.value.toLowerCase()
    return items.value.filter((item) =>
      (item.name || '').toLowerCase().includes(keyword) ||
      (item.description || '').toLowerCase().includes(keyword)
    )
  })

  const createItem = () => {
    newForm.value = emptyForm()
    showAddModal.value = true
  }

  const closeAddModal = () => {
    showAddModal.value = false
    newForm.value = emptyForm()
  }

  const editItem = (item) => {
    editForm.value = {
      id: item.id,
      name: item.name ?? '',
      description: item.description ?? '',
      status: item.status ? 1 : 0,
    }
    showEditModal.value = true
  }

  const closeEditModal = () => {
    showEditModal.value = false
    editForm.value = { id: null, ...emptyForm() }
  }

  const saveItem = () => {
    router.post(basePath, newForm.value, {
      onSuccess: () => closeAddModal(),
    })
  }

  const updateItem = () => {
    router.put(`${basePath}/${editForm.value.id}`, editForm.value, {
      onSuccess: () => closeEditModal(),
    })
  }

  const deleteItem = (item) => {
    if (confirm(`Delete ${item.name}?`)) {
      router.delete(`${basePath}/${item.id}`)
    }
  }

  return {
    search,
    showAddModal,
    showEditModal,
    newForm,
    editForm,
    filteredItems,
    flash,
    errors,
    createItem,
    closeAddModal,
    editItem,
    closeEditModal,
    saveItem,
    updateItem,
    deleteItem,
  }
}
