import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import useRolePrefix from '@/Composables/useRolePrefix'

export function useGiftVouchersIndex() {
  const { prefix } = useRolePrefix()
  const page = usePage()

  const vouchers = computed(() => page.props.vouchers ?? [])

  const search = ref('')
  const showAddModal = ref(false)
  const showEditModal = ref(false)

  const newForm = ref({
    control_no: '',
    type: '20%',
    validity: '',
  })

  const editForm = ref({
    id: null,
    control_no: '',
    type: '20%',
    status: 'available',
    validity: '',
  })

  const filteredVouchers = computed(() => {
    if (!search.value) return vouchers.value

    const keyword = search.value.toLowerCase()

    return vouchers.value.filter((voucher) =>
      (voucher.control_no || '').toLowerCase().includes(keyword) ||
      (voucher.type || '').toLowerCase().includes(keyword) ||
      (voucher.status || '').toLowerCase().includes(keyword)
    )
  })

  const resetNewForm = () => {
    newForm.value = {
      control_no: '',
      type: '20%',
      validity: '',
    }
  }

  const resetEditForm = () => {
    editForm.value = {
      id: null,
      control_no: '',
      type: '20%',
      status: 'available',
      validity: '',
    }
  }

  const createVoucher = () => {
    resetNewForm()
    showAddModal.value = true
  }

  const closeAddModal = () => {
    showAddModal.value = false
    resetNewForm()
  }

  const editVoucher = (voucher) => {
    editForm.value = {
      id: voucher.id,
      control_no: voucher.control_no ?? '',
      type: voucher.type ?? '20%',
      status: voucher.status ?? 'available',
      validity: voucher.validity ?? '',
    }

    showEditModal.value = true
  }

  const closeEditModal = () => {
    showEditModal.value = false
    resetEditForm()
  }

  const saveVoucher = () => {
    router.post(`/${prefix.value}/vouchers`, newForm.value, {
      onSuccess: () => {
        closeAddModal()
      },
    })
  }

  const updateVoucher = () => {
    router.put(`/${prefix.value}/vouchers/${editForm.value.id}`, editForm.value, {
      onSuccess: () => {
        closeEditModal()
      },
    })
  }

  const deleteVoucher = (voucher) => {
    if (confirm(`Are you sure you want to delete voucher ${voucher.control_no}?`)) {
      router.delete(`/${prefix.value}/vouchers/${voucher.id}`)
    }
  }

  const printVoucher = (voucher) => {
    window.open(`/${prefix.value}/vouchers/${voucher.id}/print`, '_blank')
  }

  const printAllVouchers = () => {
    window.open(`/${prefix.value}/vouchers/print-all`, '_blank')
  }

  const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleDateString('en-CA')
  }

  return {
    prefix,
    vouchers,
    search,
    showAddModal,
    showEditModal,
    newForm,
    editForm,
    filteredVouchers,
    createVoucher,
    closeAddModal,
    editVoucher,
    closeEditModal,
    saveVoucher,
    updateVoucher,
    deleteVoucher,
    printVoucher,
    printAllVouchers,
    formatDate,
  }
}