<template>
  <AuthenticatedLayout>
    <div
      v-if="isLoading"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60"
    >
      <div class="flex flex-col items-center rounded-xl bg-white p-8 shadow-xl">
        <div class="mb-4 h-12 w-12 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>
        <p class="text-lg font-semibold text-gray-700">
          Generating Sales Report. Please wait...
        </p>
      </div>
    </div>

    <div class="">
      <div class="bg-gray-50 p-4">
        <h1 class="mb-4 text-2xl font-semibold text-gray-900">
          Sales Report
        </h1>

        <div class="mb-6 flex flex-wrap items-end gap-3">
          <div class="flex flex-col">
            <label class="text-sm text-gray-600">Start Date</label>
            <input
              v-model="startDate"
              type="date"
              class="w-44 rounded border px-3 py-2 text-sm"
            />
          </div>

          <div class="flex flex-col">
            <label class="text-sm text-gray-600">End Date</label>
            <input
              v-model="endDate"
              type="date"
              class="w-44 rounded border px-3 py-2 text-sm"
            />
          </div>

          <div class="flex flex-col">
            <label class="text-sm text-gray-600">Cashier</label>
            <select
              v-model="selectedCashier"
              class="w-52 rounded border px-3 py-2 text-sm"
            >
              <option value="">All Cashiers</option>
              <option
                v-for="cashier in cashiers"
                :key="cashier.id"
                :value="cashier.id"
              >
                {{ cashier.name }}
              </option>
            </select>
          </div>

          <div class="flex flex-col">
            <label class="text-sm text-gray-600">Status</label>
            <select
              v-model="selectedStatus"
              class="w-30 rounded border px-3 py-2 text-sm"
            >
              <option value="paid">Paid</option>
              <option value="">All</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>

          <div class="flex flex-col">
            <label class="text-sm text-gray-600">Shift</label>
            <select
              v-model="selectedShift"
              class="w-80 rounded border px-3 py-2 text-sm"
            >
              <option value="All" selected>All Shift</option>
              <option v-for="shift in shifts" :key="shift.id" :value="shift.id">
                {{ shift.name }}
                ({{ new Date(`1970-01-01T${shift.start_time}`).toLocaleTimeString('en-PH', { 
                      hour: '2-digit', 
                      minute: '2-digit', 
                      hour12: true 
                  }) }} – 
                {{ new Date(`1970-01-01T${shift.end_time}`).toLocaleTimeString('en-PH', { 
                      hour: '2-digit', 
                      minute: '2-digit', 
                      hour12: true 
                  }) }})
              </option>
            </select>
          </div>

          <button
            @click="fetchSalesReport"
            class="rounded bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700"
          >
            <span class="fa fa-sync-alt mr-1"></span>
            Fetch Data
          </button>

          <button
            @click="generatePdfReport"
            :disabled="orders.length === 0"
            class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 disabled:opacity-50"
          >
            <span class="fa fa-file-pdf mr-1"></span>
            Generate Detailed
          </button>

          <button
            @click="exportToExcel"
            :disabled="orders.length === 0"
            class="rounded bg-emerald-600 px-4 py-2 text-sm text-white hover:bg-emerald-700 disabled:opacity-50"
          >
            <span class="fa fa-file-excel mr-1"></span>
            Export Excel
          </button>
          
          <button
            @click="exportSalesSummary"
            :disabled="orders.length === 0"
            class="rounded bg-orange-600 px-4 py-2 text-sm text-white hover:bg-orange-700 disabled:opacity-50"
          >
            <span class="fa fa-file-excel mr-1"></span>
            Export Summary
          </button>
        </div>

        <div class="mb-7 grid grid-cols-1 gap-4 md:grid-cols-5">

          <!-- Gross Sales -->
          <div class="rounded-lg bg-white p-4 shadow-sm border border-gray-100">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Gross Sales</p>
            <p class="mt-1 text-2xl font-bold text-gray-800">
              ₱{{ grossSales.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
            </p>
          </div>

          <!-- Total Expenses -->
          <div class="rounded-lg bg-white p-4 shadow-sm border border-gray-100">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Total Expenses</p>
            <p class="mt-1 text-2xl font-bold text-red-500">
              ₱{{ totalCashierExpenses.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
            </p>
          </div>

          <!-- Net Sales -->
          <div class="rounded-lg bg-white p-4 shadow-sm border border-gray-100">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Net Sales</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">
              ₱{{ netSales.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
            </p>
          </div>
          
          <!-- Payment Breakdown card -->
          <div class="rounded-lg bg-white p-4 shadow-sm border border-gray-100">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-400 mb-3">Payment Breakdown</p>
            <div class="space-y-2">
              <div
                v-for="(amount, method) in payments"
                :key="method"
                class="flex items-center justify-between"
              >
                <span class="text-sm text-gray-500">{{ method }}</span>
                <span class="text-sm font-semibold text-gray-800">
                  ₱{{ Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </span>
              </div>

              <div v-if="!payments || Object.keys(payments).length === 0" class="text-sm text-gray-400 italic">
                No payment data
              </div>

              <div class="border-t border-gray-100 pt-2 flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-600">Total</span>
                <span class="text-sm font-bold text-gray-800">
                  ₱{{ Object.values(payments).reduce((s, v) => s + Number(v), 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </span>
              </div>
            </div>
          </div>

          <div class="rounded-lg bg-white p-4 shadow-sm border border-gray-100">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
              Drinks Add-ons Sold
            </p>

            <ul class="mt-3 space-y-2">
              <li
                v-for="addon in consumedAddons"
                :key="addon.item_name"
                class="flex items-center justify-between border-b border-gray-100 pb-2 text-sm text-gray-700"
              >
                <div>
                  <span class="font-medium">
                    {{ addon.item_name }}
                  </span>

                  <span class="ml-2 text-gray-400">
                    x{{ addon.quantity }}
                  </span>
                </div>

                <span class="font-semibold text-red-500">
                  ₱{{
                    Number(addon.total).toLocaleString('en-US', {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2
                    })
                  }}
                </span>
              </li>

              <li
                v-if="!consumedAddons || consumedAddons.length === 0"
                class="text-sm text-gray-400 italic"
              >
                No add-ons sold
              </li>
            </ul>

            <div class="flex mt-3 items-center justify-between">
              <span class="text-md text-gray-700">
                Totals:
              </span>

              <span class="text-right font-bold text-red-500">
                ₱{{
                  consumedAddons
                    .reduce((sum, addon) => sum + Number(addon.total), 0)
                    .toLocaleString('en-US', {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2
                    })
                }}
              </span>
            </div>
          </div>
        </div>

        <div class="max-h-[55vh] overflow-auto rounded-lg bg-white shadow">
          <table class="min-w-max table-auto border-collapse text-sm">
            <thead class="sticky top-0 z-10 bg-gray-100">
              <tr class="text-left font-semibold">
                <th class="border px-2 py-2 sticky left-0 bg-gray-100 z-20">Date</th>
                <th class="border px-2 py-2">Cashier</th>
                <th class="border px-2 py-2">Table #</th>
                <th class="border px-2 py-2">Customer Name</th>
                <th class="border px-2 py-2">No. of Pax</th>
                <th class="border px-2 py-2">Amount</th>

                <!-- Dynamic payment method columns — no hardcoded GCash/Cash/Maya -->
                <th
                  v-for="methodName in paymentMethodColumns"
                  :key="methodName"
                  class="border px-2 py-2 whitespace-nowrap"
                >
                  {{ methodName }}
                </th>

                <th class="border px-2 py-2">Total Gross Sales</th>
                <th class="border px-2 py-2">Less Discount</th>
                <th class="border px-2 py-2">Total Net Sales</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="order in orders" :key="order.id"
                :class="[
                  'border-t',
                  order.status === 'cancelled'
                    ? 'bg-red-50 text-gray-400'
                    : isReservationOrder(order)
                      ? 'bg-blue-50 hover:bg-blue-100'
                      : isSingleOrder(order)
                        ? 'bg-yellow-100 hover:bg-yellow-100'
                        : 'hover:bg-gray-50'
                ]">

                <!-- DATE -->
                <td class="border px-2 py-2 sticky left-0 bg-white whitespace-nowrap">{{ formatDate(order.created_at) }}</td>

                <!-- CASHIER -->
                <td class="border px-2 py-2 whitespace-nowrap">{{ order.cashier?.name || order.user?.name || '-' }}</td>

                <!-- TABLE # / TYPE LABEL -->
                <template v-if="isReservationOrder(order)">
                  <td class="border px-2 py-2 font-semibold bg-blue-50 whitespace-nowrap" colspan="3">
                    <p class="bg-blue-100 rounded text-blue-500 px-2 py-0.5 inline-block">Reservation Fee</p>
                    <p><small class="text-gray-500">({{ order.order_no || 'N/A' }})</small></p>
                  </td>
                </template>
                <template v-else-if="isSingleOrder(order)">
                  <td class="border px-2 py-2 font-semibold bg-yellow-100 whitespace-nowrap" colspan="3">
                    <p class="bg-yellow-200 rounded text-orange-400 px-2 py-0.5 inline-block">Single Order</p>
                  </td>
                </template>
                <template v-else>
                  <!-- TABLE -->
                  <td class="border px-2 py-2 whitespace-nowrap">
                    {{ order.table_session?.table?.name || order.table_number || 'N/A' }}
                  </td>

                  <!-- CUSTOMER NAME -->
                  <td class="border px-2 py-2 whitespace-nowrap">{{ order.table_session?.customer_name || '-' }}</td>

                  <!-- NO. OF PAX -->
                  <td class="border px-2 py-2 whitespace-nowrap">{{ order.table_session?.pax || '-' }}</td>
                </template>

                <!-- AMOUNT -->
                <td class="border px-2 py-2 whitespace-nowrap">₱{{ Number(order.subtotal || 0).toFixed(2) }}</td>

                <!-- DYNAMIC PAYMENT METHOD CELLS -->
                <td
                  v-for="methodName in paymentMethodColumns"
                  :key="methodName"
                  class="border px-2 py-2 whitespace-nowrap"
                >
                  ₱{{ getOrderPaymentAmount(order, methodName).toFixed(2) }}
                </td>

                <!-- TOTAL GROSS SALES -->
                <td class="border px-2 py-2 whitespace-nowrap">₱{{ Number(order.subtotal || 0).toFixed(2) }}</td>

                <!-- LESS DISCOUNT -->
                <td class="border px-2 py-2 text-green-600 whitespace-nowrap">₱{{ Number(order.total_discount || 0).toFixed(2) }}</td>

                <!-- TOTAL NET SALES -->
                <td class="border px-2 py-2 font-semibold whitespace-nowrap">₱{{ Number(order.total || 0).toFixed(2) }}</td>
              </tr>

              <tr v-if="orders.length === 0">
                <td :colspan="6 + paymentMethodColumns.length + 3" class="py-6 text-center text-gray-400">No sales records found</td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useSalesReport } from '@/Composables/reports/useSalesReport'

const {
  orders,
  startDate,
  endDate,
  cashiers,
  selectedCashier,
  selectedStatus,
  shifts,
  selectedShift,
  isLoading,
  grossSales,
  totalCashierExpenses,
  netSales,
  transactionCount,
  voidedCount,
  totalCashSales,
  totalGcashSales,
  totalReservationFees,
  fetchSalesReport,
  generatePdfReport,
  exportSalesSummary,
  formatDate,
  consumedAddons,
  totalMayaSales,
  paymentBreakdown,
  exportToExcel,
  payments,
  paymentMethodColumns,
  getOrderPaymentAmount
} = useSalesReport()

const isReservationOrder = (order) => {
  return order.order_no?.startsWith('RSVP')
}

const isSingleOrder = (order) => {
  return !order.table_session && !order.table_number && !isReservationOrder(order)
}
</script>