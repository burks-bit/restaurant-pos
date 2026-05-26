<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'

const page = usePage()
const orders = computed(() => page.props.orders ?? [])

// 🔹 Filters
const period = ref('daily') // daily | monthly
const startDate = ref('')
const endDate = ref('')
const search = ref('')
const selectedStatus = ref('')

// 🔹 Default: today on first visit
onMounted(() => {
  const today = new Date().toISOString().split('T')[0]
  startDate.value = today
  endDate.value = today

  fetchReports()
})

// 🔹 Auto refetch when filters change
watch([startDate, endDate, period], () => {
  fetchReports()
})

// 🔹 Fetch reports
const fetchReports = () => {
  router.get(
    route('cashier.reports'),
    {
      start_date: startDate.value,
      end_date: endDate.value,
      period: period.value,
    },
    {
      preserveState: true,
      replace: true,
    }
  )
}

// 🔹 Frontend filtering (search + status only)
const filteredOrders = computed(() => {
  return orders.value.filter(order => {
    const matchesStatus =
      !selectedStatus.value || order.status === selectedStatus.value

    const matchesSearch =
      !search.value ||
      order.order_no?.toLowerCase().includes(search.value.toLowerCase()) ||
      String(order.total_amount).includes(search.value)

    return matchesStatus && matchesSearch
  })
})

// Actions
const printReceipt = (order) => {
  window.open(route('cashier.orders.pdf', order.id), '_blank')
}

const openModal = (order) => {
  alert(`View order #${order.order_no}`)
}

const grandTotal = computed(() => {
  return filteredOrders.value.reduce((sum, order) => {
    if (order.status === 'cancelled') return sum
    return sum + Number(order.total_amount ?? 0)
  }, 0)
})

</script>


<template>
  <div class="p-4 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-2xl font-bold text-red-600">Reports</h1>

      <div class="flex gap-4">
        <Link href="/dashboard" class="text-sm text-gray-600 hover:underline">
          Dashboard
        </Link>
        <Link :href="route('cashier.orders')" class="text-sm text-gray-600 hover:underline">
          Orders
        </Link>
        <Link :href="route('cashier.reports')" class="text-sm font-semibold text-red-600">
          Reports
        </Link>
      </div>
    </div>

    <!-- Filters -->
    <div class="mb-4 flex flex-wrap gap-4 items-center">

        <!-- Period -->
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700">Period:</label>
            <select
            v-model="period"
            class="border rounded px-2 py-1 text-sm"
            >
            <option value="daily">Daily</option>
            <option value="monthly">Monthly</option>
            </select>
        </div>

        <!-- Start Date -->
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700">Start:</label>
            <input
            type="date"
            v-model="startDate"
            class="border rounded px-2 py-1 text-sm"
            />
        </div>

        <!-- End Date -->
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700">End:</label>
            <input
            type="date"
            v-model="endDate"
            class="border rounded px-2 py-1 text-sm"
            />
        </div>

        <!-- Search -->
        <input
            v-model="search"
            type="text"
            placeholder="Search order # or amount…"
            class="border rounded px-3 py-1 text-sm w-64"
        />

        <!-- Status -->
        <!-- <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700">Status:</label>
            <select
            v-model="selectedStatus"
            class="border rounded px-2 py-1 text-sm"
            >
            <option value="">All</option>
            <option value="pending">Pending</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
            </select>
        </div> -->
    </div>


    <!-- Flash messages -->
    <div
      v-if="$page.props.flash.success"
      class="mb-4 p-2 bg-green-100 text-green-700 rounded"
    >
      {{ $page.props.flash.success }}
    </div>

    <div
      v-if="$page.props.flash.error"
      class="mb-4 p-2 bg-red-100 text-red-700 rounded"
    >
      {{ $page.props.flash.error }}
    </div>

    <!-- Table -->
    <div class="bg-white rounded shadow overflow-hidden">
      <div class="max-h-[60vh] overflow-y-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-100 sticky top-0 z-10">
            <tr class="text-left">
              <th class="px-4 py-2">#</th>
              <th class="px-4 py-2">Order #</th>
              <th class="px-4 py-2">Items</th>
              <th class="px-4 py-2">Total</th>
              <th class="px-4 py-2 text-right">Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="(order, index) in filteredOrders"
              :key="order.id"
              :class="[
                'border-t',
                order.status === 'cancelled'
                  ? 'bg-red-50 text-gray-400'
                  : 'hover:bg-gray-50'
              ]"
            >
              <!-- Index -->
              <td class="px-4 py-2 font-medium">
                {{ index + 1 }}
              </td>

              <!-- Order No + Status -->
              <td class="px-4 py-2 font-medium">
                <div class="flex items-center gap-2">
                  <span>{{ order.order_no }}</span>

                  <span
                    v-if="order.status === 'cancelled'"
                    class="text-xs bg-red-200 text-red-800 px-2 py-0.5 rounded"
                  >
                    Cancelled
                  </span>
                </div>
              </td>

              <!-- Items -->
              <td class="px-4 py-2 text-gray-600">
                {{ order.items.length }}
              </td>

              <!-- Total -->
              <td
                class="px-4 py-2"
                :class="order.status === 'cancelled' ? 'line-through' : ''"
              >
                ₱{{ grandTotal.toFixed(2) }}
              </td>

              <!-- Actions -->
              <td class="px-4 py-2 text-right space-x-2">
                <button
                  @click="printReceipt(order)"
                  :disabled="order.status === 'cancelled'"
                  class="px-2 py-1 text-xs text-white rounded"
                  :class="order.status === 'cancelled'
                    ? 'bg-blue-300 cursor-not-allowed'
                    : 'bg-blue-500 hover:bg-blue-600'"
                >
                <span class="fa fa-print"></span>
                  Print Receipt
                </button>

                <!-- <button
                  @click="openModal(order)"
                  class="px-2 py-1 text-xs bg-gray-200 rounded hover:bg-gray-300"
                >
                  View
                </button> -->
              </td>
            </tr>

            <!-- Empty state -->
            <tr v-if="filteredOrders.length === 0">
              <td colspan="5" class="text-center py-6 text-gray-400">
                No reports found
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
