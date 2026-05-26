import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

export function useTablesIndex() {
  const page = usePage()
  const tables = computed(() => page.props.tables ?? [])

  const search = ref('')
  const showAddModal = ref(false)
  const showEditModal = ref(false)
  const expandedRows = ref([])

  const newForm = ref({
    name: '',
    capacity: 4,
    parent_id: null,
  })

  const editForm = ref({
    id: null,
    name: '',
    capacity: 4,
    parent_id: null,
    status: 'vacant',
  })

  const filteredTables = computed(() => {
    if (!search.value) return tables.value

    const keyword = search.value.toLowerCase()

    return tables.value.filter((table) => {
      const parentMatch =
        (table.name || '').toLowerCase().includes(keyword) ||
        (table.status || '').toLowerCase().includes(keyword)

      const childMatch = (table.children || []).some((child) =>
        (child.name || '').toLowerCase().includes(keyword) ||
        (child.status || '').toLowerCase().includes(keyword)
      )

      return parentMatch || childMatch
    })
  })

  const parentTableOptions = computed(() => {
    return tables.value.filter((table) => !table.parent_id)
  })

  const editableParentOptions = computed(() => {
    return tables.value.filter(
      (table) => !table.parent_id && table.id !== editForm.value.id
    )
  })

  const resetNewForm = () => {
    newForm.value = {
      name: '',
      capacity: 4,
      parent_id: null,
    }
  }

  const resetEditForm = () => {
    editForm.value = {
      id: null,
      name: '',
      capacity: 4,
      parent_id: null,
      status: 'vacant',
    }
  }

  const createTable = () => {
    resetNewForm()
    showAddModal.value = true
  }

  const closeAddModal = () => {
    showAddModal.value = false
    resetNewForm()
  }

  const editTable = (table) => {
    editForm.value = {
      id: table.id,
      name: table.name ?? '',
      capacity: table.capacity ?? 4,
      parent_id: table.parent_id ?? null,
      status: table.status ?? 'vacant',
    }

    showEditModal.value = true
  }

  const closeEditModal = () => {
    showEditModal.value = false
    resetEditForm()
  }

  const saveTable = () => {
    router.post('/admin/tables/store', newForm.value, {
      onSuccess: () => {
        closeAddModal()
      },
    })
  }

  const updateTable = () => {
    router.put(`/admin/tables/update/${editForm.value.id}`, editForm.value, {
      onSuccess: () => {
        closeEditModal()
      },
    })
  }

  const deleteTable = (table) => {
    if (confirm(`Delete ${table.name}?`)) {
      router.delete(`/admin/tables/delete/${table.id}`)
    }
  }

  const toggleExpand = (tableId) => {
    if (expandedRows.value.includes(tableId)) {
      expandedRows.value = expandedRows.value.filter((id) => id !== tableId)
    } else {
      expandedRows.value.push(tableId)
    }
  }

  const totalChildrenCount = (table) => {
    return table.children?.length ?? 0
  }

  const occupiedChildrenCount = (table) => {
    if (!table.children?.length) {
      return table.status === 'occupied' ? 1 : 0
    }

    return table.children.filter((child) => child.status === 'occupied').length
  }

  const availableChildrenCount = (table) => {
    if (!table.children?.length) {
      return table.status === 'vacant' ? 1 : 0
    }

    return table.children.filter((child) => child.status === 'vacant').length
  }

  const getParentStatus = (table) => {
    if (!table.children?.length) {
      return table.status ?? 'vacant'
    }

    const total = table.children.length
    const occupied = occupiedChildrenCount(table)

    if (occupied === 0) return 'vacant'
    if (occupied === total) return 'full'
    return 'partially occupied'
  }

  const getParentStatusClass = (table) => {
    const status = getParentStatus(table)

    if (status === 'vacant') return 'bg-green-100 text-green-700'
    if (status === 'full') return 'bg-red-100 text-red-700'
    return 'bg-yellow-100 text-yellow-700'
  }

  return {
    search,
    showAddModal,
    showEditModal,
    expandedRows,
    newForm,
    editForm,
    filteredTables,
    parentTableOptions,
    editableParentOptions,
    createTable,
    closeAddModal,
    editTable,
    closeEditModal,
    saveTable,
    updateTable,
    deleteTable,
    toggleExpand,
    totalChildrenCount,
    occupiedChildrenCount,
    availableChildrenCount,
    getParentStatus,
    getParentStatusClass,
  }
}