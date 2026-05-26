import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

export function useBranches() {
  const page = usePage()

  const branches = computed(() => page.props.branches ?? [])
  const search = ref('')
  const showAddModal = ref(false)
  const showEditModal = ref(false)

  const newForm = ref({
    name: '',
    code: '',
    address: '',
    contact: '',
    main: 1,
  })

  const editForm = ref({
    id: null,
    name: '',
    code: '',
    address: '',
    contact: '',
    main: 1,
  })

  const filteredBranches = computed(() => {
    if (!search.value) return branches.value

    const keyword = search.value.toLowerCase()

    return branches.value.filter((branch) =>
      (branch.name || '').toLowerCase().includes(keyword) ||
      (branch.code || '').toLowerCase().includes(keyword) ||
      (branch.address || '').toLowerCase().includes(keyword) ||
      (branch.contact || '').toLowerCase().includes(keyword)
    )
  })

  const resetNewForm = () => {
    newForm.value = {
      name: '',
      code: '',
      address: '',
      contact: '',
      main: 1,
    }
  }

  const createBranch = () => {
    resetNewForm()
    showAddModal.value = true
  }

  const closeAddModal = () => {
    showAddModal.value = false
    resetNewForm()
  }

  const editBranch = (branch) => {
    editForm.value = {
      id: branch.id,
      name: branch.name ?? '',
      code: branch.code ?? '',
      address: branch.address ?? '',
      contact: branch.contact ?? '',
      main: branch.main ? 1 : 0,
    }

    showEditModal.value = true
  }

  const closeEditModal = () => {
    showEditModal.value = false
  }

  const saveBranch = () => {
    router.post('/branches', newForm.value, {
      onSuccess: () => {
        closeAddModal()
      },
    })
  }

  const updateBranch = () => {
    router.put(`/branches/${editForm.value.id}`, editForm.value, {
      onSuccess: () => {
        closeEditModal()
      },
    })
  }

  const deleteBranch = (branch) => {
    if (confirm(`Are you sure you want to delete ${branch.name}?`)) {
      router.delete(`/branches/${branch.id}`)
    }
  }

  return {
    branches,
    search,
    showAddModal,
    showEditModal,
    newForm,
    editForm,
    filteredBranches,
    createBranch,
    closeAddModal,
    editBranch,
    closeEditModal,
    saveBranch,
    updateBranch,
    deleteBranch,
  }
}