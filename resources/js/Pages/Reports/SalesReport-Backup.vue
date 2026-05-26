<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

/* =========================
   THEME CONFIG (EDIT HERE)
========================= */
const theme = {
  primary: 'blue',
  primaryBg: 'bg-blue-600',
  primaryHover: 'hover:bg-blue-700',
  primaryText: 'text-blue-600',

  accentBg: 'bg-slate-100',
  accentText: 'text-slate-700',

  successBg: 'bg-emerald-600',
  successHover: 'hover:bg-emerald-700',

  dangerBg: 'bg-rose-600',
  dangerHover: 'hover:bg-rose-700',
}
/* ========================= */

const props = defineProps({
  sales: Array,
  grandTotal: Number,
  filters: Object
})

const type = ref(props.filters.type)
const startDate = ref(props.filters.start_date)
const endDate = ref(props.filters.end_date)

watch(type, () => {
  router.get(route('cashier.reports.sales'), {
    type: type.value
  }, { preserveState: true })
})

const applyFilter = () => {
  router.get(route('cashier.reports.sales'), {
    type: type.value,
    start_date: startDate.value,
    end_date: endDate.value
  })
}
</script>

<template>
  <div class="p-6 min-h-screen bg-slate-50">

    <!-- NAVIGATION -->
    <div class="flex gap-4 mb-5">
      <Link 
        href="/dashboard" 
        :class="`text-sm text-gray-600 hover:text-${theme.primary}-600 transition`"
      >
        Dashboard
      </Link>
      <Link 
        href="/cashier/orders" 
        :class="`text-sm text-gray-600 hover:text-${theme.primary}-600 transition`"
      >
        View Orders
      </Link>
      <Link 
        href="/cashier/reports" 
        :class="`text-sm text-gray-600 hover:text-${theme.primary}-600 transition`"
      >
        Reports
      </Link>
    </div>

    <!-- SUMMARY CARD -->
    <div class="mb-5 bg-white rounded-lg shadow p-5 border">
      <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide">
        Total Sales
      </h2>
      <p :class="['text-3xl font-extrabold mt-1', theme.primaryText]">
        ₱{{ Number(grandTotal || 0).toLocaleString() }}
      </p>
    </div>

    <!-- FILTERS -->
    <div class="flex flex-wrap gap-3 mb-5 items-end">
      <select v-model="type"
        class="border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
        <option value="daily">Daily</option>
        <option value="monthly">Monthly</option>
      </select>

      <input type="date" v-model="startDate"
        class="border rounded-md px-3 py-2 text-sm" />

      <input type="date" v-model="endDate"
        class="border rounded-md px-3 py-2 text-sm" />

      <button
        @click="applyFilter"
        :class="[
          'px-4 py-2 text-sm font-medium rounded-md text-white transition',
          theme.primaryBg,
          theme.primaryHover
        ]"
      >
        Apply
      </button>

      <a
        :href="route('cashier.reports.sales.pdf', { start_date: startDate, end_date: endDate })"
        target="_blank"
        :class="[
          'px-4 py-2 text-sm rounded-md text-white font-medium transition',
          theme.dangerBg,
          theme.dangerHover
        ]"
      >
        PDF
      </a>

      <a
        :href="route('cashier.reports.sales.excel', { start_date: startDate, end_date: endDate })"
        :class="[
          'px-4 py-2 text-sm rounded-md text-white font-medium transition',
          theme.successBg,
          theme.successHover
        ]"
      >
        Excel
      </a>
    </div>

    <!-- TABLE -->
    <div class="bg-white rounded-lg shadow border overflow-x-auto max-h-[60vh]">
      <table class="w-full text-sm text-left">
        <thead :class="[theme.primaryBg, 'text-white sticky top-0 z-10']">
          <tr>
            <th class="p-3 font-semibold">Date</th>
            <th class="p-3 font-semibold text-right">Total</th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="row in sales"
            :key="row.date"
            class="border-b hover:bg-slate-50"
          >
            <td class="p-3 text-slate-700">{{ row.date }}</td>
            <td class="p-3 text-right font-medium">
              ₱{{ Number(row.total || 0).toLocaleString() }}
            </td>
          </tr>

          <!-- Empty state -->
          <tr v-if="!sales.length">
            <td colspan="2" class="text-center p-5 text-gray-400">
              No sales found
            </td>
          </tr>
        </tbody>

        <!-- TOTAL FOOTER -->
        <tfoot class="bg-slate-100 font-semibold">
          <tr>
            <td class="p-3">TOTAL</td>
            <td class="p-3 text-right" :class="theme.primaryText">
              ₱{{ Number(grandTotal || 0).toLocaleString() }}
            </td>
          </tr>
        </tfoot>
      </table>
    </div>

  </div>
</template>
