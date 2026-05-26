<template>
  <AuthenticatedLayout>

    <!-- ================= FULL SCREEN LOADER ================= -->
    <div
      v-if="isLoading"
      class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50"
    >
      <div class="bg-white p-8 rounded-xl shadow-xl flex flex-col items-center">
        <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
        <p class="text-lg font-semibold text-gray-700">
          Fetching orders. Please wait...
        </p>
      </div>
    </div>

    <div class="p-4 bg-gray-50">
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-black-600">Orders</h1>
      </div>

      <!-- to work on sales paid per cashier, running bill (all unbilled) and total sales (all cashier) -->
      <div v-if="!isCashier" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white p-4 rounded shadow">
          <p class="text-sm text-gray-500">Sales (Paid per Cashier)</p>
          <p class="text-xl font-bold text-gray-800">₱ {{ total_sales_per_cashier.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
        </div>

        <div class="bg-white p-4 rounded shadow">
          <p class="text-sm text-gray-500">Running Bill (Unpaid)</p>
          <p class="text-xl font-bold text-green-600">₱ {{ total_unbilled.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
        </div>

        <div class="bg-white p-4 rounded shadow">
          <p class="text-sm text-gray-500">Total Sales (All Cashier)</p>
          <p class="text-xl font-bold text-red-600">₱ {{ total_sales_all_cashier.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="mb-4 flex flex-wrap gap-4 items-end">
        <div class="flex items-center gap-2">
          <label class="text-sm font-medium text-gray-700">Date:</label>
          <input
            type="date"
            v-model="selectedDate"
            class="border rounded px-2 py-1 text-sm"
          />
        </div>

        <input
          v-model="search"
          type="text"
          placeholder="Search order # or amount…"
          class="border rounded px-3 py-1 text-sm w-64"
        />

        <div class="flex items-center gap-2">
          <label class="text-sm font-medium text-gray-700">Status:</label>
          <select
            v-model="selectedStatus"
            class="border rounded px-2 py-1 text-sm"
          >
            <option value="">All</option>
            <option value="pending">Pending</option>
            <option value="paid">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        <button
          @click="applyFilters"
          class="bg-green-600 text-white px-4 p-2 text-sm rounded hover:bg-green-700"
        >
          <i class="fa fa-filter" aria-hidden="true"></i> Filter
        </button>

        <button
          @click="refreshComputation"
          :disabled="isRefreshLoading"
          class="bg-violet-600 text-white px-4 py-2 text-sm rounded hover:bg-violet-700 flex items-center gap-2 disabled:opacity-60"
        >
          <i class="fa fa-refresh"></i>
          <span>Refresh Computation</span>
        </button>
      </div>

      <!-- Flash messages -->
      <div v-if="$page.props.flash.success" class="mb-4 p-2 bg-green-100 text-green-700 rounded">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash.error" class="mb-4 p-2 bg-red-100 text-red-700 rounded">
        {{ $page.props.flash.error }}
      </div>

      <!-- Orders Table -->
      <div class="bg-white rounded shadow overflow-hidden">
        <div class="max-h-[60vh] overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-100 sticky top-0 z-10">
              <tr class="text-left">
                <th class="px-4 py-2">#</th>
                <th class="px-4 py-2">Order #</th>
                <th class="px-4 py-2">Ref #</th>
                <th class="px-4 py-2">Customer</th>
                <th class="px-4 py-2">Table #</th>
                <th class="px-4 py-2">Items</th>
                <th class="px-4 py-2">Total</th>
                <th class="px-4 py-2 text-right">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(order, index) in orders"
                :key="order.id"
                :class="[
                  'border-t',
                  order.status === 'cancelled'
                    ? 'bg-red-50 text-gray-400'
                    : isSingleOrder(order)
                      ? 'bg-yellow-50 hover:bg-yellow-100'
                      : 'hover:bg-gray-50'
                ]"
              >
                <td class="px-4 py-2 font-medium">{{ index + 1 }}</td>

                <td class="px-4 py-2 font-medium">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span>{{ order.order_no }}</span>

                    <span
                      v-if="order.status === 'cancelled'"
                      class="text-xs bg-red-200 text-red-800 px-2 py-0.5 rounded"
                    >
                      Status: Cancelled
                    </span>

                    <span
                      v-if="isSingleOrder(order)"
                      class="text-xs bg-yellow-200 text-yellow-800 px-2 py-0.5 rounded"
                    >
                      Single Ordered Item
                    </span>
                  </div>
                </td>

                <td class="px-4 py-2 font-medium">
                  {{ order.table_session?.ref_no || (isSingleOrder(order) ? '-' : 'N/A') }}
                </td>

                <td class="px-4 py-2 font-medium">
                  {{ order.table_session?.customer_name || (isSingleOrder(order) ? 'Walk-in' : 'N/A') }}
                </td>

                <td class="px-4 py-2 text-gray-600">
                  {{
                    order.table_session?.table?.name ||
                    order.table_number ||
                    (isSingleOrder(order) ? '—' : 'N/A')
                  }}
                </td>

                <td class="px-4 py-2 text-gray-600">
                  {{ getDisplayedItemCount(order) }}
                </td>

                <td class="px-4 py-2" :class="order.status === 'cancelled' ? 'line-through' : ''">
                  ₱{{ Number(getDisplayedOrderTotal(order)).toFixed(2) }}
                </td>

                <td class="px-4 py-2">
                  <div class="flex justify-end items-center gap-2">
                    <button
                      @click="openModal(order)"
                      class="px-3 py-1.5 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200 flex items-center gap-1"
                    >
                      <span class="fa fa-eye"></span>
                      View
                    </button>

                    <button
                      @click="printReceipt(order)"
                      :disabled="order.status === 'cancelled'"
                      class="px-3 py-1.5 text-xs text-white rounded flex items-center gap-1"
                      :class="order.status === 'cancelled'
                        ? 'bg-blue-300 cursor-not-allowed'
                        : 'bg-blue-500 hover:bg-blue-600'"
                    >
                      <span class="fa fa-print"></span>
                      Print
                    </button>

                    <button
                      v-if="order.status !== 'cancelled'"
                      @click="openCancelOrder(order)"
                      class="px-3 py-1.5 text-xs bg-red-500 text-white rounded hover:bg-red-600 flex items-center gap-1"
                    >
                      <span class="fa fa-times"></span>
                      Cancel
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="orders.length === 0">
                <td colspan="8" class="text-center py-6 text-gray-400">No orders found</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- View Order Modal -->
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4"
      >
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl max-h-[90vh] overflow-hidden">
          <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50">
            <div>
              <h2 class="text-2xl font-bold text-gray-800">
                Order #{{ selectedOrder?.order_no }}
              </h2>
              <p class="text-sm text-gray-500 mt-1">
                Table:
                <span class="font-medium">
                  {{ selectedOrder?.table_session?.table?.name || selectedOrder?.table_number || 'N/A' }}
                </span>
                <span v-if="selectedOrder?.table_session?.customer_name" class="ml-3">
                  Customer:
                  <span class="font-medium">{{ selectedOrder.table_session.customer_name }}</span>
                </span>
              </p>
            </div>

            <button
              @click="closeModal"
              class="px-3 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 text-sm"
            >
              Close
            </button>
          </div>

          <div class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]">
            <!-- Barcode + meta -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
              <div class="lg:col-span-1 bg-gray-50 border rounded-xl p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Order Barcode</h3>
                <div class="flex justify-center">
                  <svg id="barcode"></svg>
                </div>
              </div>

              <div class="lg:col-span-2 bg-gray-50 border rounded-xl p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">
                  Order Details
                </h3>

                <div v-if="selectedOrder?.status === 'cancelled'">
                  <p class="text-sm text-red-600">
                    Cancelled by {{ selectedOrder?.user?.name }}.
                  </p>
                  <p class="text-sm text-red-600 mt-1">
                    Reason: {{ selectedOrder?.cancellation_remarks || 'N/A' }}
                  </p>
                </div>

                <div v-else class="space-y-4 text-sm text-gray-700">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <p>
                      <span class="font-semibold">Placed by:</span>
                      {{ selectedOrder?.user?.name || 'N/A' }}
                    </p>

                    <p>
                      <span class="font-semibold">Date:</span>
                      {{ formatDateTime(selectedOrder?.created_at) }}
                    </p>

                    <p>
                      <span class="font-semibold">Status:</span>
                      <span class="capitalize">{{ selectedOrder?.status || 'N/A' }}</span>
                    </p>

                    <p>
                      <span class="font-semibold">Order No:</span>
                      {{ selectedOrder?.order_no || 'N/A' }}
                    </p>

                    <p>
                      <span class="font-semibold">Subtotal:</span>
                      ₱{{ Number(selectedOrder?.subtotal || 0).toFixed(2) }}
                    </p>

                    <p>
                      <span class="font-semibold">Discount:</span>
                      ₱{{ Number(selectedOrder?.total_discount || 0).toFixed(2) }}
                    </p>

                    <p>
                      <span class="font-semibold">Total:</span>
                      ₱{{ Number(selectedOrder?.total || 0).toFixed(2) }}
                    </p>

                    <p v-if="selectedOrder?.change_amount !== null">
                      <span class="font-semibold">Change:</span>
                      ₱{{ Number(selectedOrder?.change_amount || 0).toFixed(2) }}
                    </p>
                  </div>

                  <div class="border-t pt-3">
                    <div class="flex items-center justify-between mb-2">
                      <h4 class="text-sm font-semibold text-gray-700">Payment Breakdown</h4>
                      <span class="text-xs text-gray-500">
                        {{ selectedOrder?.payments?.length || 0 }} method(s)
                      </span>
                    </div>

                    <div v-if="selectedOrder?.payments?.length" class="space-y-2">
                      <div
                        v-for="payment in selectedOrder.payments"
                        :key="payment.id"
                        class="rounded-lg border bg-white px-3 py-2"
                      >
                        <div class="flex items-start justify-between gap-3">
                          <div>
                            <p class="font-medium text-gray-900">
                              {{ payment?.payment_method?.name || 'Unknown Payment Method' }}
                            </p>

                            <p v-if="payment?.reference_no" class="text-xs text-gray-500 mt-1">
                              Ref No: {{ payment.reference_no }}
                            </p>

                            <p v-if="payment?.remarks" class="text-xs text-gray-500 mt-1">
                              Remarks: {{ payment.remarks }}
                            </p>
                          </div>

                          <div class="text-right">
                            <p class="font-semibold text-gray-900">
                              ₱{{ Number(payment?.amount || 0).toFixed(2) }}
                            </p>
                            <p
                              v-if="payment?.is_void"
                              class="text-xs font-medium text-red-600"
                            >
                              Voided
                            </p>
                          </div>
                        </div>
                      </div>

                      <!-- <div class="mt-3 rounded-lg bg-gray-100 px-3 py-2">
                        <div class="flex items-center justify-between font-semibold text-gray-800">
                          <span>Total Paid</span>
                          <span>
                            ₱{{
                              selectedOrder.payments
                                .filter(payment => !payment.is_void)
                                .reduce((sum, payment) => sum + Number(payment.amount || 0), 0)
                                .toFixed(2)
                            }}
                          </span>
                        </div>
                      </div> -->
                      <div class="mt-3 rounded-lg bg-gray-100 px-3 py-2">
                        <div class="flex items-center justify-between font-semibold text-gray-800">
                          <span>Total Paid</span>
                          <span>₱{{ Number(totalPaidForSelectedOrder || 0).toFixed(2) }}</span>
                        </div>
                      </div>
                    </div>

                    <p v-else class="text-sm text-gray-500">
                      No payment records found.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Summary cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
              <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wide text-blue-700 font-semibold">Base Dining</p>
                <p class="text-2xl font-bold text-blue-900 mt-1">
                  ₱{{ Number(orderTotals.heads_subtotal).toFixed(2) }}
                </p>
              </div>

              <div class="bg-amber-50 border border-amber-100 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wide text-amber-700 font-semibold">Add-ons</p>
                <p class="text-2xl font-bold text-amber-900 mt-1">
                  ₱{{ Number(orderTotals.addons_subtotal).toFixed(2) }}
                </p>
              </div>

              <div class="bg-green-50 border border-green-100 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wide text-green-700 font-semibold">Discount</p>
                <p class="text-2xl font-bold text-green-900 mt-1">
                  ₱{{ Number(orderTotals.total_discount).toFixed(2) }}
                </p>
              </div>

              <div class="bg-gray-100 border border-gray-200 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wide text-gray-700 font-semibold">Grand Total</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">
                  ₱{{ Number(orderTotals.total).toFixed(2) }}
                </p>
              </div>
            </div>

            <!-- Leftover Charge Summary -->
            <div
              v-if="selectedOrder?.leftover"
              class="mb-6 border border-red-200 bg-red-50 rounded-2xl overflow-hidden"
            >
              <div class="px-4 py-3 border-b border-red-200">
                <h3 class="text-lg font-semibold text-red-800">Leftover Charge</h3>
                <p class="text-xs text-red-600 mt-1">
                  Additional charge for uneaten food based on recorded weight
                </p>
              </div>

              <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                  <p class="text-gray-600">Weight</p>
                  <p class="text-lg font-bold text-gray-800">
                    {{ Number(selectedOrder.leftover.weight).toFixed(2) }} g
                  </p>
                </div>

                <div>
                  <p class="text-gray-600">Charge Amount</p>
                  <p class="text-lg font-bold text-red-700">
                    ₱{{ Number(selectedOrder.leftover.amount).toFixed(2) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Base dining charges and add-ons -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
              <!-- Base Dining Charges -->
              <div class="border rounded-2xl overflow-hidden">
                <div class="px-4 py-3 bg-gray-100 border-b">
                  <h3 class="text-lg font-semibold text-gray-800">Base Dining Charges</h3>
                  <p class="text-xs text-gray-500 mt-1">
                    Headcount and pricing rules applied to the table
                  </p>
                </div>

                <div class="overflow-x-auto">
                  <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                      <tr class="text-left">
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Qty</th>
                        <th class="px-4 py-3">Price</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="head in selectedOrder?.order_heads || []"
                        :key="head.id"
                        class="border-t"
                      >
                        <td class="px-4 py-3">
                          {{ head.head_pricing_rule?.label || `Head Rule ID: ${head.head_pricing_rule_id}` }}
                        </td>
                        <td class="px-4 py-3">{{ Number(head.quantity).toFixed(0) }}</td>
                        <td class="px-4 py-3">₱{{ Number(head.price_snapshot).toFixed(2) }}</td>
                        <td class="px-4 py-3 text-right font-medium">
                          ₱{{ Number(head.subtotal).toFixed(2) }}
                        </td>
                      </tr>

                      <tr v-if="(selectedOrder?.order_heads || []).length === 0">
                        <td colspan="4" class="px-4 py-6 text-center text-gray-400">
                          No base dining charges found.
                        </td>
                      </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 border-t">
                      <tr>
                        <td colspan="3" class="px-4 py-3 text-right font-semibold">Subtotal</td>
                        <td class="px-4 py-3 text-right font-bold">
                          ₱{{ Number(orderTotals.heads_subtotal).toFixed(2) }}
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>

              <!-- Add-ons -->
              <div class="border rounded-2xl overflow-hidden">
                <div class="px-4 py-3 bg-amber-50 border-b">
                  <h3 class="text-lg font-semibold text-amber-900">Add-ons / Extra Orders</h3>
                  <p class="text-xs text-amber-700 mt-1">
                    Additional items posted to this table after the main dining order
                  </p>
                </div>

                <div class="overflow-x-auto">
                  <table class="w-full text-sm">
                    <thead class="bg-amber-50">
                      <tr class="text-left">
                        <th class="px-4 py-3">Item</th>
                        <th class="px-4 py-3">Unit</th>
                        <th class="px-4 py-3">Qty</th>
                        <th class="px-4 py-3">Price</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="addon in selectedOrder?.addons || []"
                        :key="addon.id"
                        class="border-t"
                      >
                        <td class="px-4 py-3 font-medium text-gray-800">
                          {{ addon.item_name }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                          {{ addon.unit || 'N/A' }}
                        </td>
                        <td class="px-4 py-3">
                          {{ Number(addon.quantity).toFixed(2) }}
                        </td>
                        <td class="px-4 py-3">
                          ₱{{ Number(addon.unit_price).toFixed(2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-medium">
                          ₱{{ Number(addon.subtotal).toFixed(2) }}
                        </td>
                      </tr>

                      <tr v-if="(selectedOrder?.addons || []).length === 0">
                        <td colspan="5" class="px-4 py-6 text-center text-gray-400">
                          No add-ons posted for this order.
                        </td>
                      </tr>
                    </tbody>
                    <tfoot class="bg-amber-50 border-t">
                      <tr>
                        <td colspan="4" class="px-4 py-3 text-right font-semibold">Subtotal</td>
                        <td class="px-4 py-3 text-right font-bold text-amber-900">
                          ₱{{ Number(orderTotals.addons_subtotal).toFixed(2) }}
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>

            <!-- Final totals -->
            <div class="mt-6 border-t pt-4">
              <div class="max-w-md ml-auto space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-600">Base Dining Charges</span>
                  <span class="font-medium">₱{{ Number(orderTotals.heads_subtotal).toFixed(2) }}</span>
                </div>

                <div class="flex justify-between">
                  <span class="text-gray-600">Add-ons</span>
                  <span class="font-medium">₱{{ Number(orderTotals.addons_subtotal).toFixed(2) }}</span>
                </div>

                <div
                  v-if="orderTotals.total_discount > 0"
                  class="flex justify-between text-green-600"
                >
                  <span>
                    Discount
                    <span v-if="selectedOrder?.voucher_no_used">
                      (Voucher: {{ selectedOrder.voucher_no_used }})
                    </span>
                    <span v-else-if="selectedOrder?.discount_type">
                      ({{ selectedOrder.discount_type }})
                    </span>
                  </span>
                  <span class="font-medium">-₱{{ Number(orderTotals.total_discount).toFixed(2) }}</span>
                </div>

                <div v-if="orderTotals.leftover_amount > 0" class="flex justify-between">
                  <span class="text-gray-600">Leftover Charge</span>
                  <span class="font-medium text-red-600">
                    ₱{{ Number(orderTotals.leftover_amount).toFixed(2) }}
                  </span>
                </div>

                <div class="flex justify-between text-lg font-bold border-t pt-2">
                  <span>Total</span>
                  <span>₱{{ Number(orderTotals.total).toFixed(2) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Post Leftover Charge Modal -->
      <div
        v-if="showLeftoverModal"
        class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4"
      >
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
          <div class="px-6 py-4 border-b bg-red-50 flex items-center justify-between">
            <div>
              <h2 class="text-xl font-bold text-red-800">Post Leftover Charge</h2>
              <p class="text-sm text-red-600 mt-1">
                Order #{{ selectedOrder?.order_no }}
              </p>
            </div>

            <button
              @click="closeLeftoverModal"
              class="px-3 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 text-sm"
            >
              Close
            </button>
          </div>

          <div class="p-6 space-y-4">
            <div class="bg-gray-50 border rounded-xl p-4 text-sm">
              <p>
                <span class="font-semibold">Table:</span>
                {{ selectedOrder?.table_session?.table?.name || selectedOrder?.table_number || 'N/A' }}
              </p>
              <p v-if="selectedOrder?.table_session?.customer_name" class="mt-1">
                <span class="font-semibold">Customer:</span>
                {{ selectedOrder.table_session.customer_name }}
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Leftover Weight (grams)
              </label>
              <input
                v-model="leftoverForm.weight"
                type="number"
                step="0.01"
                min="0"
                class="w-full border rounded-lg px-3 py-2"
                placeholder="Enter weight in grams"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Charge Amount
              </label>
              <input
                v-model="leftoverForm.amount"
                type="number"
                step="0.01"
                min="0"
                class="w-full border rounded-lg px-3 py-2"
                placeholder="Enter computed amount"
              />
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm text-amber-800">
              Example: if 100g = ₱250, enter weight = 100 and amount = 250.
            </div>
          </div>

          <div class="px-6 py-4 border-t bg-gray-50 flex justify-end gap-2">
            <button
              @click="closeLeftoverModal"
              class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300"
            >
              Cancel
            </button>

            <button
              @click="submitLeftoverCharge"
              :disabled="isPostingLeftover"
              class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-60"
            >
              {{ isPostingLeftover ? 'Saving...' : 'Save Leftover Charge' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Cancel Order Modal -->
      <div v-if="showCancelModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded shadow-lg w-96 p-5">
          <h2 class="text-lg font-bold mb-2 text-red-600">Manager Approval Required</h2>
          <p class="text-sm text-gray-600 mb-3">
            Cancelling Order #{{ selectedOrder?.order_no }} requires manager authorization.
          </p>

          <label class="text-xs font-medium text-gray-700">Reason</label>
          <textarea
            v-model="cancelReason"
            placeholder="Reason for cancellation"
            class="w-full border rounded px-3 py-2 mb-3 text-sm"
          ></textarea>

          <div v-for="manager in managers" :key="manager.id" class="flex items-center gap-2 border-b py-2">
            <input
              type="checkbox"
              :id="'manager-' + manager.id"
              :value="manager.id"
              v-model="selectedManagers"
              class="h-4 w-4 text-indigo-600 border-gray-300 rounded"
            />
            <label :for="'manager-' + manager.id" class="text-sm text-gray-800 cursor-pointer">
              {{ manager.name }} ({{ manager.email }})
            </label>
          </div>

          <label class="text-xs font-medium text-gray-700">Manager Password</label>
          <input
            type="password"
            v-model="managerPassword"
            placeholder="Enter manager password"
            class="w-full border rounded px-3 py-2 mb-4 text-sm"
          />

          <div class="flex justify-end gap-2">
            <button @click="closeCancelModal" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Close</button>
            <button @click="cancelOrder" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">
              Approve & Cancel
            </button>
          </div>
        </div>
      </div>

      
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useOrdersIndex } from '@/Composables/orders/useOrdersIndex'

const {
  orders,
  total_sales_per_cashier,
  total_sales_all_cashier,
  total_unbilled,
  managers,

  showModal,
  showCancelModal,
  selectedOrder,

  selectedDate,
  search,
  selectedStatus,

  cancelReason,
  managerPassword,
  selectedManagers,

  isLoading,
  isRefreshLoading,

  showLeftoverModal,
  leftoverForm,
  isPostingLeftover,

  orderTotals,

  applyFilters,
  openModal,
  closeModal,
  getDisplayedOrderTotal,
  getDisplayedItemCount,

  openLeftoverModal,
  closeLeftoverModal,
  submitLeftoverCharge,

  formatDateTime,
  printReceipt,

  openCancelOrder,
  closeCancelModal,
  cancelOrder,

  refreshComputation,
  totalPaidForSelectedOrder,
  isCashier
} = useOrdersIndex()

const isSingleOrder = (order) => {
  return !order.table_session && !order.table_number
}


</script>