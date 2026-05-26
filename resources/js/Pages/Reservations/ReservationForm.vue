<template>
  <form class="space-y-4" @submit.prevent="$emit('submit')">

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

    <!-- Pricing scheme -->
    <div>
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

    <!-- Pax breakdown -->
    <div v-if="form.pax_breakdown.length">
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
            <tr v-for="(row, i) in form.pax_breakdown" :key="row.head_pricing_rule_id" class="border-t">
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
                  @input="$emit('qty-change', i)"
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
    <p v-else class="text-sm text-gray-400 italic">Select a pricing scheme to configure pax.</p>

    <!-- Date & time -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Reservation date &amp; time</label>
      <input
        v-model="form.reservation_datetime"
        type="datetime-local"
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

    <!-- Reservation fee -->
    <div class="border rounded p-3 bg-gray-50 space-y-3">
      <p class="text-sm font-medium text-gray-700">Reservation fee</p>
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
      </div>
      <div v-if="form.fee_payment_method !== 'cash'">
        <label class="block text-xs text-gray-600 mb-1">Reference no.</label>
        <input
          v-model="form.fee_reference_no"
          type="text"
          class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
        />
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
        <option value="cancelled">Cancelled</option>
      </select>
    </div>

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
  </form>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  form: { type: Object, required: true },
  pricingSchemes: { type: Array, default: () => [] },
  pricingRules: { type: Array, default: () => [] },
  processing: { type: Boolean, default: false },
})

// console.log(props.pricingRules)

defineEmits(['submit', 'cancel', 'qty-change', 'scheme-change'])

const totalPax = computed(() =>
  props.form.pax_breakdown.reduce((s, r) => s + (parseInt(r.qty) || 0), 0),
)
const totalEstimated = computed(() =>
  props.form.pax_breakdown.reduce((s, r) => s + (parseFloat(r.subtotal) || 0), 0),
)
const formatCurrency = (v) =>
  Number(v ?? 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })
</script>