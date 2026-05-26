import useRolePrefix from '@/Composables/useRolePrefix'

export function usePayrollList() {
  const { prefix } = useRolePrefix()

  const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
  }

  const statusBadge = (status) => {
    if (status === 'draft') {
      return 'bg-yellow-100 text-yellow-700'
    }

    if (status === 'posted') {
      return 'bg-blue-100 text-blue-700'
    }

    if (status === 'finalized') {
      return 'bg-green-100 text-green-700'
    }

    return ''
  }

  return {
    prefix,
    formatDate,
    statusBadge,
  }
}