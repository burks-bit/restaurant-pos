import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export default function useRolePrefix() {
  const page = usePage()

  const ROLE_LABELS = {
    0: 'admin',
    1: 'manager',
    2: 'cashier',
    3: 'frontdoor',
    4: 'purchaser',
    5: 'kitchen',
    6: 'finance',
    7: 'hr',
    8: 'cook',
    9: 'linecook',
    10: 'waiter',
    11: 'waitress',
    12: 'dishwasher',
    13: 'headwaiter',
  }

  const role = computed(() => page.props.auth?.user?.role ?? null)

  const prefix = computed(() => {
    return ROLE_LABELS[role.value] ?? ''
  })

  return {
    prefix,
    role,
  }
}
