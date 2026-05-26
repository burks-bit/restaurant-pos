import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import useRolePrefix from '@/Composables/useRolePrefix'

export function useMenusIndex() {
  const { prefix } = useRolePrefix()
  const page = usePage()

  const categories = computed(() => page.props.categories ?? [])
  const menus = computed(() => page.props.menus ?? [])

  const search = ref('')
  const showModal = ref(false)
  const showAddModal = ref(false)

  const addImagePreview = ref(null)
  const editImagePreview = ref(null)

  const newForm = ref({
    name: '',
    category_id: '',
    price: '',
    is_available: 1,
    image: null,
  })

  const editForm = ref({
    id: null,
    name: '',
    category_id: '',
    price: '',
    is_available: 1,
    image: null,
  })

  const filteredMenus = computed(() => {
    if (!search.value) return menus.value

    const keyword = search.value.toLowerCase()

    return menus.value.filter((menu) =>
      (menu.name || '').toLowerCase().includes(keyword)
    )
  })

  const getCategoryName = (id) => {
    const category = categories.value.find((item) => item.id === id)
    return category ? category.name : ''
  }

  const resetNewForm = () => {
    newForm.value = {
      name: '',
      category_id: '',
      price: '',
      is_available: 1,
      image: null,
    }
    addImagePreview.value = null
  }

  const resetEditForm = () => {
    editForm.value = {
      id: null,
      name: '',
      category_id: '',
      price: '',
      is_available: 1,
      image: null,
    }
    editImagePreview.value = null
  }

  const onImageChange = (event, type) => {
    const file = event.target.files?.[0]
    if (!file) return

    const reader = new FileReader()
    reader.onload = (e) => {
      if (type === 'add') {
        addImagePreview.value = e.target.result
        newForm.value.image = file
      } else {
        editImagePreview.value = e.target.result
        editForm.value.image = file
      }
    }

    reader.readAsDataURL(file)
  }

  const createMenu = () => {
    resetNewForm()
    showAddModal.value = true
  }

  const closeAddModal = () => {
    showAddModal.value = false
    resetNewForm()
  }

  const addMenu = () => {
    const formData = new FormData()
    formData.append('name', newForm.value.name)
    formData.append('category_id', newForm.value.category_id)
    formData.append('price', newForm.value.price)
    formData.append('is_available', newForm.value.is_available ? 1 : 0)

    if (newForm.value.image) {
      formData.append('image', newForm.value.image)
    }

    router.post(`/${prefix.value}/menus/store`, formData, {
      forceFormData: true,
      onSuccess: () => {
        closeAddModal()
      },
    })
  }

  const editMenu = (menu) => {
    editForm.value = {
      id: menu.id,
      name: menu.name ?? '',
      category_id: menu.category_id ?? '',
      price: menu.price ?? '',
      is_available: menu.is_available ? 1 : 0,
      image: null,
    }

    editImagePreview.value = menu.image_path ? `/storage/${menu.image_path}` : null
    showModal.value = true
  }

  const closeEditModal = () => {
    showModal.value = false
    resetEditForm()
  }

  const updateMenu = () => {
    const formData = new FormData()
    formData.append('name', editForm.value.name)
    formData.append('category_id', editForm.value.category_id)
    formData.append('price', editForm.value.price)
    formData.append('is_available', editForm.value.is_available ? 1 : 0)
    formData.append('_method', 'PUT')

    if (editForm.value.image) {
      formData.append('image', editForm.value.image)
    }

    router.post(`/${prefix.value}/menus/edit/${editForm.value.id}`, formData, {
      forceFormData: true,
      onSuccess: () => {
        closeEditModal()
      },
    })
  }

  const deleteMenu = (menu) => {
    if (confirm(`Delete ${menu.name}?`)) {
      router.delete(`/${prefix.value}/menus/delete/${menu.id}`)
    }
  }

  return {
    categories,
    menus,
    search,
    showModal,
    showAddModal,
    addImagePreview,
    editImagePreview,
    newForm,
    editForm,
    filteredMenus,
    getCategoryName,
    onImageChange,
    createMenu,
    closeAddModal,
    addMenu,
    editMenu,
    closeEditModal,
    updateMenu,
    deleteMenu,
  }
}