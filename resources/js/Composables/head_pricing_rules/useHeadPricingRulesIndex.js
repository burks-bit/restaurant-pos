import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

export function useHeadPricingRulesIndex() {
  const page = usePage()

  const pricingSchemes = computed(() => page.props.pricing_schemes ?? [])
  const search = ref('')

  const showAddSchemeModal = ref(false)
  const showEditSchemeModal = ref(false)
  const showAddRuleModal = ref(false)
  const showEditRuleModal = ref(false)

  const selectedScheme = ref(null)

  const newSchemeForm = ref({
    name: '',
    type: 'regular',
    description: '',
    is_active: 1,
  })

  const editSchemeForm = ref({
    id: null,
    name: '',
    type: 'regular',
    description: '',
    is_active: 1,
  })

  const newRuleForm = ref({
    pricing_scheme_id: null,
    label: '',
    min_age: null,
    max_age: null,
    min_height: null,
    max_height: null,
    price: 0,
    is_active: 1,
  })

  const editRuleForm = ref({
    id: null,
    pricing_scheme_id: null,
    label: '',
    min_age: null,
    max_age: null,
    min_height: null,
    max_height: null,
    price: 0,
    is_active: 1,
  })

  const filteredPricingSchemes = computed(() => {
    if (!search.value) return pricingSchemes.value

    const keyword = search.value.toLowerCase()

    return pricingSchemes.value
      .map((scheme) => {
        const schemeMatch =
          (scheme.name ?? '').toLowerCase().includes(keyword) ||
          (scheme.type ?? '').toLowerCase().includes(keyword) ||
          (scheme.description ?? '').toLowerCase().includes(keyword)

        const filteredRules = (scheme.head_rules ?? []).filter((rule) => {
          return (
            (rule.label ?? '').toLowerCase().includes(keyword) ||
            String(rule.price ?? '').includes(search.value)
          )
        })

        if (schemeMatch) {
          return scheme
        }

        if (filteredRules.length > 0) {
          return {
            ...scheme,
            head_rules: filteredRules,
          }
        }

        return null
      })
      .filter(Boolean)
  })

  const resetNewSchemeForm = () => {
    newSchemeForm.value = {
      name: '',
      type: 'regular',
      description: '',
      is_active: 1,
    }
  }

  const resetEditSchemeForm = () => {
    editSchemeForm.value = {
      id: null,
      name: '',
      type: 'regular',
      description: '',
      is_active: 1,
    }
  }

  const resetNewRuleForm = () => {
    newRuleForm.value = {
      pricing_scheme_id: null,
      label: '',
      min_age: null,
      max_age: null,
      min_height: null,
      max_height: null,
      price: 0,
      is_active: 1,
    }
  }

  const resetEditRuleForm = () => {
    editRuleForm.value = {
      id: null,
      pricing_scheme_id: null,
      label: '',
      min_age: null,
      max_age: null,
      min_height: null,
      max_height: null,
      price: 0,
      is_active: 1,
    }
  }

  const createScheme = () => {
    resetNewSchemeForm()
    showAddSchemeModal.value = true
  }

  const closeAddSchemeModal = () => {
    showAddSchemeModal.value = false
    resetNewSchemeForm()
  }

  const saveScheme = () => {
    router.post(route('admin.pricing-schemes.store'), newSchemeForm.value, {
      preserveScroll: true,
      onSuccess: () => {
        closeAddSchemeModal()
      },
    })
  }

  const editScheme = (scheme) => {
    selectedScheme.value = scheme

    editSchemeForm.value = {
      id: scheme.id,
      name: scheme.name ?? '',
      type: scheme.type ?? 'regular',
      description: scheme.description ?? '',
      is_active: Number(scheme.is_active ?? 1),
    }

    showEditSchemeModal.value = true
  }

  const closeEditSchemeModal = () => {
    showEditSchemeModal.value = false
    selectedScheme.value = null
    resetEditSchemeForm()
  }

  const updateScheme = () => {
    router.put(route('admin.pricing-schemes.update', editSchemeForm.value.id), editSchemeForm.value, {
      preserveScroll: true,
      onSuccess: () => {
        closeEditSchemeModal()
      },
    })
  }

  const createRule = (scheme) => {
    selectedScheme.value = scheme
    resetNewRuleForm()
    newRuleForm.value.pricing_scheme_id = scheme.id
    showAddRuleModal.value = true
  }

  const closeAddRuleModal = () => {
    showAddRuleModal.value = false
    selectedScheme.value = null
    resetNewRuleForm()
  }

  const saveRule = () => {
    router.post(route('admin.head-pricing-rules.store'), newRuleForm.value, {
      preserveScroll: true,
      onSuccess: () => {
        closeAddRuleModal()
      },
    })
  }

  const editRule = (scheme, rule) => {
    selectedScheme.value = scheme

    editRuleForm.value = {
      id: rule.id,
      pricing_scheme_id: scheme.id,
      label: rule.label ?? '',
      min_age: rule.min_age,
      max_age: rule.max_age,
      min_height: rule.min_height,
      max_height: rule.max_height,
      price: Number(rule.price ?? 0),
      is_active: Number(rule.is_active ?? 1),
    }

    showEditRuleModal.value = true
  }

  const closeEditRuleModal = () => {
    showEditRuleModal.value = false
    selectedScheme.value = null
    resetEditRuleForm()
  }

  const updateRule = () => {
    router.put(route('admin.head-pricing-rules.update', editRuleForm.value.id), editRuleForm.value, {
      preserveScroll: true,
      onSuccess: () => {
        closeEditRuleModal()
      },
    })
  }

  const deleteRule = (rule) => {
    if (confirm(`Are you sure you want to delete rule "${rule.label}"?`)) {
      router.delete(route('admin.head-pricing-rules.destroy', rule.id), {
        preserveScroll: true,
      })
    }
  }

  const formatAmount = (value) => {
    return Number(value ?? 0).toFixed(2)
  }

  const getRuleConditionText = (rule) => {
    const parts = []

    if (rule.min_age !== null || rule.max_age !== null) {
      parts.push(`Age: ${rule.min_age ?? '-'} to ${rule.max_age ?? '-'}`)
    }

    if (rule.min_height !== null || rule.max_height !== null) {
      parts.push(`Height: ${rule.min_height ?? '-'}ft to ${rule.max_height ?? '-'}ft`)
    }

    return parts.length ? parts.join(' | ') : 'No conditions'
  }

  return {
    search,
    filteredPricingSchemes,

    showAddSchemeModal,
    showEditSchemeModal,
    showAddRuleModal,
    showEditRuleModal,

    selectedScheme,
    newSchemeForm,
    editSchemeForm,
    newRuleForm,
    editRuleForm,

    createScheme,
    closeAddSchemeModal,
    saveScheme,

    editScheme,
    closeEditSchemeModal,
    updateScheme,

    createRule,
    closeAddRuleModal,
    saveRule,

    editRule,
    closeEditRuleModal,
    updateRule,
    deleteRule,

    formatAmount,
    getRuleConditionText,
  }
}