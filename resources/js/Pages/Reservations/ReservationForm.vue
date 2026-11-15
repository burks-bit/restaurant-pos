<template>
  <form class="space-y-4" @submit.prevent="$emit('submit')">

    <!-- Reservation mode switch -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Reservation type</label>
      <div class="inline-flex rounded-lg border border-gray-300 overflow-hidden">
        <button
          type="button"
          class="px-4 py-2 text-sm flex items-center gap-2"
          :class="form.mode === 'scheme' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
          @click="form.mode = 'scheme'"
        >
          <i class="fa fa-percent"></i> Price Scheme
        </button>
        <button
          type="button"
          class="px-4 py-2 text-sm flex items-center gap-2 border-l border-gray-300"
          :class="form.mode === 'package' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
          @click="form.mode = 'package'"
        >
          <i class="fa fa-box-open"></i> Package
        </button>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-x-8 gap-y-4">

      <!-- LEFT COLUMN -->
      <div class="space-y-4">
        <!-- Shift -->
        <div>
          <!-- {{ form }} -->
          <label class="block text-sm font-medium text-gray-700 mb-1">Shift</label>
          <select
            v-model="form.shift_id"
            
            class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
          >
            <option value="" disabled>Select shift...</option>
            <option v-for="sh in shifts" :key="sh.id" :value="sh.id">
              {{ sh.name }} ({{ formatTime(sh.start_time) }}–{{ formatTime(sh.end_time) }})
            </option>
          </select>
        </div>
        
        <!-- <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Cashier</label>
          <select
            v-model="form.cashier_employee_id"
            required
            class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
          >
            <option value="" disabled>Select cashier...</option>
            <option v-for="cashier in cashiersOnDuty" :key="cashier.employee_id" :value="cashier.employee_id">
              {{ cashier.name }}
            </option>
          </select>
        </div> -->

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Cashier</label>
          <select
            v-model="form.cashier_user_id"
            
            class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
          >
            <option value="" disabled>Select cashier...</option>
            <option
              v-for="cashier in cashiersOnDuty"
              :key="cashier.user?.id"
              :value="cashier.user?.id"
            >
              {{ cashier.user?.name ?? cashier.name }}
            </option>
          </select>
        </div>

        <!-- Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Representative name</label>
          <input
            v-model="form.name"
            type="text"
            required
            class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
          />
        </div>

        <!-- Contact -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Contact number</label>
          <input
            v-model="form.contact_number"
            type="text"
            class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
          />
        </div>

        <!-- Pricing scheme (scheme mode only) -->
        <div v-if="form.mode === 'scheme'">
          <label class="block text-sm font-medium text-gray-700 mb-1">Pricing scheme</label>
          <select
            v-model="form.pricing_scheme_id"
            required
            class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
            @change="$emit('scheme-change', form.pricing_scheme_id)"
          >
            <option value="" disabled>Select scheme...</option>
            <option v-for="s in pricingSchemes" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>
      </div>

      <!-- RIGHT COLUMN -->
      <div class="space-y-4">
        <!-- Reservation date & time -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Reservation date &amp; time</label>
          <input
            v-model="form.reservation_datetime"
            type="datetime-local"
            required
            class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
          />
        </div>

        <!-- Reservation fee -->
        <div class="border rounded p-3 bg-gray-50">
          <p class="text-sm font-medium text-gray-700 mb-3">Reservation fee</p>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs text-gray-600 mb-1">Amount</label>
              <input
                v-model="form.reservation_fee"
                type="number"
                min="0"
                step="0.01"
                class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
              />
            </div>
            <div>
              <label class="block text-xs text-gray-600 mb-1">Payment method</label>
              <select
                v-model="form.fee_payment_method"
                class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
              >
                <option value="1">Cash</option>
                <option value="2">GCash</option>
                <option value="3">Maya</option>
                <option value="4">GrabPay</option>
                <option value="5">BDO</option>
                <option value="6">BPI</option>
                <option value="7">Metrobank</option>
                <option value="8">UnionBank</option>
                <option value="9">LandBank</option>
                <option value="10">Credit Card</option>
                <option value="11">Debit Card</option>
                <option value="12">Others</option>
              </select>
            </div>
            <div v-if="form.fee_payment_method !== 'cash'" class="col-span-2">
              <label class="block text-xs text-gray-600 mb-1">Reference no.</label>
              <input
                v-model="form.fee_reference_no"
                type="text"
                class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
              />
            </div>
          </div>
        </div>

        <!-- Remarks -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
          <textarea
            v-model="form.remarks"
            rows="2"
            class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
          ></textarea>
        </div>

        <!-- Status -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select
            v-model="form.status"
            class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
          >
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="seated">Seated</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Packages (package mode only) -->
    <div v-if="form.mode === 'package'" class="border rounded p-3 bg-gray-50">
      <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
          <p class="text-sm font-medium text-gray-700">Package</p>
          <p class="text-xs text-gray-500">
            <template v-if="selectedPackage">
              {{ selectedPackage.name }}
              <span v-if="form.package_pax"> — {{ form.package_pax }} pax</span>
              <span v-if="form.package_price !== null && form.package_price !== ''">
                · {{ formatCurrency(form.package_price) }}
              </span>
            </template>
            <template v-else>No package selected</template>
          </p>
        </div>

        <div class="flex gap-2">
          <button
            type="button"
            class="flex items-center gap-1 px-3 py-1.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
            @click="showPackageModal = true"
          >
            <i class="fa fa-box-open"></i> Select Packages
          </button>
          <button
            v-if="form.package_id"
            type="button"
            class="px-3 py-1.5 text-sm bg-gray-200 rounded hover:bg-gray-300"
            @click="$emit('clear-package')"
          >
            Clear
          </button>
        </div>
      </div>

      <!-- Selected package details -->
      <div v-if="selectedPackage" class="mt-3 border-t pt-3 text-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
          <div>
            <p class="text-gray-500 text-xs">Package name</p>
            <p class="font-medium text-gray-900">{{ selectedPackage.name }}</p>
          </div>
          <div v-if="form.package_pax">
            <p class="text-gray-500 text-xs">Pax</p>
            <p class="font-medium text-gray-900">{{ form.package_pax }}</p>
          </div>
          <div v-if="form.package_price !== null && form.package_price !== ''">
            <p class="text-gray-500 text-xs">Package price</p>
            <p class="font-medium text-gray-900">{{ formatCurrency(form.package_price) }}</p>
          </div>
          <div v-if="selectedPackage.choices" class="sm:col-span-2">
            <p class="text-gray-500 text-xs">Choices</p>
            <p class="font-medium text-gray-900">{{ selectedPackage.choices }}</p>
          </div>
          <div v-if="selectedPackage.description" class="sm:col-span-2">
            <p class="text-gray-500 text-xs">Description</p>
            <p class="text-gray-700">{{ selectedPackage.description }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Pax breakdown: full width, scheme mode with a selected scheme -->
    <div v-if="form.mode === 'scheme' && form.pricing_scheme_id && schemeFilteredBreakdown.length">
      <label class="block text-sm font-medium text-gray-700 mb-2">Pax per category</label>
      <div class="border rounded overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-gray-700">
            <tr>
              <th class="px-3 py-2 text-left">Category</th>
              <th class="px-3 py-2 text-right">Price</th>
              <th class="px-3 py-2 text-center w-24">Qty</th>
              <th class="px-3 py-2 text-right">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, i) in schemeFilteredBreakdown" :key="row.head_pricing_rule_id" class="border-t">
              <td class="px-3 py-1.5">{{ row.label }}</td>
              <td class="px-3 py-1.5 text-right text-gray-600">
                {{ row.price_snapshot > 0 ? formatCurrency(row.price_snapshot) : 'Free' }}
              </td>
              <td class="px-3 py-1.5 text-center">
                <input
                  v-model="row.qty"
                  type="number"
                  min="0"
                  class="w-16 border border-gray-300 rounded px-2 py-1 text-center text-sm focus:outline-none focus:ring-1 focus:ring-blue-600"
                  @input="$emit('qty-change', form.pax_breakdown.indexOf(row))"
                />
              </td>
              <td class="px-3 py-1.5 text-right font-medium">{{ formatCurrency(row.subtotal) }}</td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-50 border-t font-medium">
            <tr>
              <td class="px-3 py-2" colspan="2">Total pax: {{ totalPax }}</td>
              <td class="px-3 py-2 text-right" colspan="2">Est. total: {{ formatCurrency(totalEstimated) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
    <p v-else-if="form.mode === 'scheme' && !form.pricing_scheme_id" class="text-sm text-gray-400 italic">
      Select a pricing scheme to configure pax.
    </p>

    <div class="flex justify-end gap-2 pt-2">
      <button
        type="button"
        class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 text-sm"
        @click="$emit('cancel')"
      >
        Cancel
      </button>
      <button
        type="submit"
        :disabled="processing"
        class="flex items-center gap-1 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm disabled:opacity-60"
      >
        <i class="fa fa-save"></i> Save
      </button>
    </div>

    <!-- Select Packages dialog -->
    <Teleport to="body">
      <div
        v-if="showPackageModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      >
        <div class="bg-white rounded-lg w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <i class="fa fa-box-open"></i> Select Package
            </h2>
            <button class="text-gray-400 hover:text-gray-600" @click="showPackageModal = false">
              <i class="fa fa-times"></i>
            </button>
          </div>

          <p v-if="!packages.length" class="text-sm text-gray-400">
            No packages available.
          </p>

          <div class="space-y-3">
            <div v-for="pkg in packages" :key="pkg.id" class="border rounded p-3">
              <div class="flex justify-between items-start gap-2">
                <div>
                  <p class="font-medium text-gray-900">{{ pkg.name }}</p>
                  <p v-if="pkg.choices" class="text-xs text-gray-500">Choices: {{ pkg.choices }}</p>
                  <p v-if="pkg.description" class="text-xs text-gray-500">{{ pkg.description }}</p>
                </div>
                <span
                  v-if="form.package_id === pkg.id"
                  class="px-2 py-0.5 text-xs bg-green-100 text-green-700 rounded-full whitespace-nowrap"
                >
                  <i class="fa fa-check"></i> Selected
                </span>
              </div>

              <div class="flex flex-wrap gap-2 mt-2">
                <button
                  v-for="price in pkg.prices"
                  :key="price.id"
                  type="button"
                  class="px-3 py-1 text-xs rounded border"
                  :class="(form.package_id === pkg.id && form.package_pax === price.pax)
                    ? 'bg-blue-600 text-white border-blue-600'
                    : 'bg-white text-gray-700 border-gray-300 hover:bg-blue-50'"
                  @click="choosePackage(pkg, price)"
                >
                  {{ price.pax }} pax — {{ formatCurrency(price.price) }}
                </button>
                <span v-if="!pkg.prices?.length" class="text-xs text-gray-400">No prices</span>
              </div>
            </div>
          </div>

          <div class="flex justify-end mt-6">
            <button
              type="button"
              class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 text-sm"
              @click="showPackageModal = false"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </form>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  form: { type: Object, required: true },
  pricingSchemes: { type: Array, default: () => [] },
  pricingRules: { type: Array, default: () => [] },
  packages: { type: Array, default: () => [] },
  shifts: { type: Array, default: () => [] },
  cashiersOnDuty: { type: Array, default: () => [] },
  processing: { type: Boolean, default: false },
})

// console.log('props.cashiersOnDuty')
// console.log(props.cashiersOnDuty)

const formatTime = (t) => {
  if (!t) return ''
  const [h, m] = t.split(':')
  const hour = parseInt(h)
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const hour12 = hour % 12 === 0 ? 12 : hour % 12
  return `${hour12}:${m} ${ampm}`
}

const emit = defineEmits(['submit', 'cancel', 'qty-change', 'scheme-change', 'select-package', 'clear-package'])

const showPackageModal = ref(false)

const selectedPackage = computed(() =>
  props.packages.find((pkg) => pkg.id === props.form.package_id) ?? null,
)

const choosePackage = (pkg, price) => {
  emit('select-package', pkg, price)
  showPackageModal.value = false
}

// Clear the fields of the mode being left so the two modes never mix.
watch(
  () => props.form.mode,
  (mode) => {
    if (mode === 'package') {
      props.form.pricing_scheme_id = ''
      props.form.pax_breakdown = []
    } else {
      emit('clear-package')
    }
  },
)

const totalPax = computed(() =>
  props.form.pax_breakdown.reduce((s, r) => s + (parseInt(r.qty) || 0), 0),
)
const totalEstimated = computed(() =>
  props.form.pax_breakdown.reduce((s, r) => s + (parseFloat(r.subtotal) || 0), 0),
)
const formatCurrency = (v) =>
  Number(v ?? 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })

const schemeFilteredBreakdown = computed(() => {
  if (!props.form.pricing_scheme_id) return []
  return props.form.pax_breakdown.filter((row) => {
    const rule = props.pricingRules.find((r) => r.id === row.head_pricing_rule_id)
    return rule && rule.pricing_scheme_id == props.form.pricing_scheme_id
  })
})
</script>