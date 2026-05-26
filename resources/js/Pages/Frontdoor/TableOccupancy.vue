<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50 min-h-screen">

        <!-- Header -->
        <div class="flex justify-between items-center mb-4">
          <h1 class="text-2xl font-semibold text-gray-900">Table Sessions</h1>
          <!-- <button @click="openAssignModal()" class="px-4 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
            <span class="fa fa-plus"></span>
            Assign Table
          </button> -->
          <Link
            :href="route('frontdoor.table_admission')"
            class="px-4 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"
          >
            Assign Table <i class="fa fa-arrow-right"></i>
          </Link>
        </div>

        <!-- TableSession Table -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
          <div class="p-4 border-b bg-gray-50 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
              <!-- <h2 class="text-lg font-semibold text-gray-800">Current Table Sessions</h2> -->
              <p class="text-sm text-gray-500">Monitor occupied, paid, cancelled, and closed tables</p>
            </div>

            <input
              v-model="search"
              type="text"
              placeholder="Search table or ref no..."
              class="w-full md:w-72 border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-600 focus:border-blue-600"
            />
          </div>

          <div class="overflow-auto max-h-[65vh]">
            <table class="min-w-full text-sm">
              <thead class="sticky top-0 bg-gray-100 z-10 text-gray-700">
                <tr>
                  <th class="px-4 py-3 text-left border-b">Table Info</th>
                  <th class="px-4 py-3 text-left border-b">Session / Payment</th>
                  <th class="px-4 py-3 text-center border-b">Pax</th>
                  <th class="px-4 py-3 text-left border-b">Time Open</th>
                  <th class="px-4 py-3 text-left border-b">Stay Alert</th>
                  <th class="px-4 py-3 text-left border-b">Remarks</th>
                  <th class="px-4 py-3 text-left border-b">Actions</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="session in filteredTableSessions"
                  :key="session.id"
                  class="hover:bg-gray-50 align-top"
                  :class="getRowAlertClass(session)"
                >
                  <!-- Table Info -->
                  <td class="px-4 py-3 border-b">
                    <div class="font-semibold text-gray-900">{{ session.table?.name ?? '-' }} - {{ session.customer_name }}</div>
                    <div class="text-xs text-gray-500 mt-1">
                      Ref No:
                      <span class="font-medium text-gray-700">{{ session.ref_no ?? '-' }}</span>
                    </div>
                  </td>

                  <!-- Session / Payment -->
                  <td class="px-4 py-3 border-b">
                    <div class="flex flex-col gap-1">
                      <div class="flex items-center gap-2 flex-wrap">
                        <span
                          class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                          :class="getStatusBadgeClass(session.status)"
                        >
                          {{ formatSessionStatus(session.status) }}
                        </span>

                        <span
                          class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                          :class="isPaid(session)
                            ? 'bg-green-100 text-green-700'
                            : 'bg-yellow-100 text-yellow-700'"
                        >
                          {{ isPaid(session) ? 'Paid' : 'Unpaid' }}
                        </span>
                      </div>

                      <div class="text-xs text-gray-600">
                        Order No:
                        <span class="font-medium text-gray-800">
                          {{ session.order?.order_no ?? '-' }}
                        </span>
                      </div>

                      <!-- <div class="text-xs text-gray-600">
                        Cashier:
                        <span class="font-medium text-gray-800">
                          {{ session.cashier?.name ?? '-' }}
                        </span>
                      </div> -->
                    </div>
                  </td>

                  <!-- Pax -->
                  <td class="px-4 py-3 border-b text-center">
                    <span class="inline-flex min-w-[36px] justify-center rounded-lg bg-blue-50 px-2 py-1 text-sm font-semibold text-blue-700">
                      {{ session.pax ?? 0 }}
                    </span>
                  </td>

                  <!-- Time Open -->
                  <td class="px-4 py-3 border-b">
                    <template v-if="session.status === 'open'">
                      <div :class="getDurationClass(session.created_at)" class="font-medium">
                        {{ formatDuration(session.created_at) }}
                      </div>
                      <div class="text-xs text-gray-500 mt-1">
                        Ongoing session
                      </div>
                    </template>

                    <template v-else>
                      <div class="text-sm text-gray-500">-</div>
                    </template>
                  </td>

                  <!-- Stay Alert -->
                  <td class="px-4 py-3 border-b text-center">
                    <template v-if="session.status === 'open'">
                      <div class="flex items-center justify-center gap-2">
                        <span
                          class="inline-flex items-center justify-center w-8 h-8 rounded-full"
                          :class="getStayAlertClass(session.created_at)"
                          :title="getStayAlertText(session.created_at)"
                        >
                          <i
                            class="fa"
                            :class="getStayAlertIcon(session.created_at)"
                          ></i>
                        </span>

                        <span
                          class="text-xs font-medium"
                          :class="getStayAlertTextClass(session.created_at)"
                        >
                          {{ getStayAlertText(session.created_at) }}
                        </span>
                      </div>
                    </template>

                    <template v-else>
                      <span class="text-sm text-gray-400">-</span>
                    </template>
                  </td>

                  <!-- Remarks -->
                  <td class="px-4 py-3 border-b">
                    <div
                      class="max-w-[220px] whitespace-pre-wrap break-words text-sm"
                      :class="session.remarks ? 'text-gray-700' : 'text-gray-400 italic'"
                    >
                      {{ session.remarks || 'No remarks' }}
                    </div>
                  </td>

                  <!-- Actions -->
                  <td class="px-4 py-3 border-b">
                    <div class="flex gap-1">
                      <button
                        class="px-3 py-2 bg-violet-600 text-white text-xs rounded-lg hover:bg-violet-700 flex items-center justify-center gap-2 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed"
                        @click="viewDettails(session)"
                      >
                        <i class="fa fa-eye"></i>
                        
                      </button>

                      <button
                        class="px-3 py-2 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700 flex items-center justify-center gap-2 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed"
                        @click="markVacant(session)"
                        :disabled="session.status !== 'open'"
                      >
                        <i class="fa fa-chair"></i>
                        Mark Vacant
                      </button>

                      <button
                        class="px-3 py-2 bg-orange-600 text-white text-xs rounded-lg hover:bg-orange-700 flex items-center justify-center gap-2 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed"
                        @click="checkPaid(session)"
                        :disabled="session.status !== 'open'"
                      >
                        <i class="fa fa-receipt"></i>
                        Check Payment
                      </button>

                      <button
                        class="px-3 py-2 bg-red-500 text-white text-xs rounded-lg hover:bg-red-600 flex items-center justify-center gap-2 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed"
                        @click="cancelTableSession(session)"
                        :disabled="session.status !== 'open' || hasCashier(session)"
                      >
                        <i class="fa fa-ban"></i>
                        Cancel Session
                      </button>
                    </div>
                  </td>
                </tr>

                <tr v-if="filteredTableSessions.length === 0">
                  <td colspan="7" class="text-center py-10 text-gray-400">
                    No table sessions found
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Assign Modal -->
        <div v-if="showAssignModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50 p-4">
          <div class="bg-white rounded-lg w-full max-w-4xl max-h-[80vh] flex flex-col">
            <div class="flex justify-between items-center p-4 border-b">
              <h2 class="text-lg font-semibold">Assign Table</h2>
              <button @click="closeAssignModal" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
            </div>
            <div class="p-4 border-b">
              <input v-model="modalSearch" type="text" placeholder="Search table..."
                class="w-full border rounded px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600"/>
            </div>

            <!-- Scrollable Grid -->
            <div class="overflow-auto flex-1 p-4 grid grid-cols-3 gap-3">
              <div v-for="table in filteredModalTables" :key="table.id"
                class="border rounded-lg p-3 flex flex-col justify-between items-center hover:bg-gray-50"
                :class="isTableOccupied(table) ? 'opacity-50 cursor-not-allowed' : ''">

                <div class="text-sm font-medium text-center">{{ table.name }}</div>
                <!-- <div class="text-xs text-gray-500">Capacity: {{ table.capacity }}</div> -->

                <template v-if="!isTableOccupied(table)">
                  <input type="text" v-model="customerName[table.id]" placeholder="Customer Name"
                    class="w-full border px-1 py-0.5 rounded text-sm focus:outline-none focus:ring-1 focus:ring-blue-600 focus:border-blue-600 mt-2"
                    />

                  <input type="number" v-model.number="pax[table.id]" min="1" placeholder="Total Pax"
                    class="w-full border px-1 py-0.5 rounded text-sm focus:outline-none focus:ring-1 focus:ring-blue-600 focus:border-blue-600 mt-2"
                    @input="updateTotalPax(table.id)"/>

                  <div class="mt-2 w-full">
                    <div v-for="rule in head_prices" :key="rule.id" class="flex justify-between items-center mb-1">
                      <label class="text-xs text-gray-700">{{ rule.label }} (₱{{ rule.price }})</label>
                      <input type="number" min="0" v-model.number="headCounts[table.id][rule.id]"
                        class="w-16 border px-1 py-0.5 rounded text-sm focus:outline-none focus:ring-1 focus:ring-blue-600 focus:border-blue-600"
                        @input="updateTotalPax(table.id)"/>
                    </div>
                  </div>

                  <button class="mt-2 w-full px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-xs"
                    @click="assignTable(table)" 
                    :disabled="!pax[table.id] || pax[table.id]<1">
                    <span class="fa fa-bookmark"></span>
                    Assign
                  </button>
                </template>

                <template v-else>
                  <div class="mt-2 w-full px-2 py-1 bg-red-500 text-white rounded text-xs text-center">Occupied</div>
                </template>

              </div>
              <div v-if="filteredModalTables.length===0" class="col-span-5 text-center py-4 text-gray-400">
                No tables available
              </div>
            </div>
          </div>
        </div>

        <!-- Cancel Table Modal -->
        <div
          v-if="showCancelModal"
          class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4"
        >
          <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-5">
            <h2 class="text-lg font-bold mb-2 text-red-600">Manager Approval Required</h2>

            <p class="text-sm text-gray-600 mb-3">
              Cancelling ongoing dining session for
              <span v-if="tableDetails">
                <strong>{{ tableDetails.table?.name }}</strong>
                <span class="block mt-1">
                  Ref No: <strong>{{ tableDetails.ref_no }}</strong>
                </span>
                <span class="block mt-1">
                  Order No: <strong>{{ tableDetails.order?.order_no ?? '-' }}</strong>
                </span>
              </span>
            </p>

            <label class="text-xs font-medium text-gray-700">Reason</label>
            <textarea
              v-model="cancelReason"
              placeholder="Reason for cancellation"
              class="w-full border rounded px-3 py-2 mb-3 text-sm"
              rows="4"
            ></textarea>

            <label class="text-xs font-medium text-gray-700 mb-2 block">Select Manager</label>
            <div class="max-h-40 overflow-y-auto border rounded mb-3">
              <div
                v-for="manager in managers"
                :key="manager.id"
                class="flex items-center gap-2 border-b last:border-b-0 px-3 py-2"
              >
                <input
                  type="radio"
                  :id="'manager-' + manager.id"
                  :value="manager.id"
                  v-model="selectedManager"
                  class="h-4 w-4 text-indigo-600 border-gray-300"
                />
                <label
                  :for="'manager-' + manager.id"
                  class="text-sm text-gray-800 cursor-pointer"
                >
                  {{ manager.name }} <span class="text-gray-500">({{ manager.email }})</span>
                </label>
              </div>

              <div v-if="!managers.length" class="px-3 py-2 text-sm text-gray-400">
                No managers found
              </div>
            </div>

            <label class="text-xs font-medium text-gray-700">Manager Password</label>
            <input
              type="password"
              v-model="managerPassword"
              placeholder="Enter manager password"
              class="w-full border rounded px-3 py-2 mb-4 text-sm"
            />

            <div class="flex justify-end gap-2">
              <button
                @click="closeCancelModal"
                class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300"
              >
                Close
              </button>
              <button
                @click="cancelOrder"
                class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700"
              >
                Approve & Cancel
              </button>
            </div>
          </div>
        </div>

        <!-- Table Details Modal -->
        <div
          v-if="showDetailModal && tableDetails"
          class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
        >
          <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b bg-green-50 flex items-start justify-between">
              <div>
                <h2 class="text-xl font-bold text-green-700">Table Details</h2>
                <p class="text-sm text-gray-600 mt-1">
                  View dining session information and head count breakdown.
                </p>
              </div>

              <button
                @click="closeDetailModal"
                class="text-gray-500 hover:text-gray-700 text-xl leading-none"
              >
                &times;
              </button>
            </div>

            <!-- Body -->
            <div class="p-6 overflow-y-auto max-h-[75vh] space-y-6">

              <!-- Top Summary -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-xl p-4 border">
                  <h3 class="text-sm font-semibold text-gray-700 mb-3">Session Info</h3>

                  <div class="space-y-2 text-sm text-gray-700">
                    <div class="flex justify-between gap-4">
                      <span class="text-gray-500">Table</span>
                      <span class="font-semibold">{{ tableDetails.table?.name ?? '-' }}</span>
                    </div>

                    <!-- <div class="flex justify-between gap-4">
                      <span class="text-gray-500">Capacity</span>
                      <span class="font-semibold">{{ tableDetails.table?.capacity ?? '-' }}</span>
                    </div> -->

                    <div class="flex justify-between gap-4">
                      <span class="text-gray-500">Customer Name</span>
                      <span class="font-semibold">{{ tableDetails.customer_name ?? '-' }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                      <span class="text-gray-500">Reference No.</span>
                      <span class="font-semibold">{{ tableDetails.ref_no ?? '-' }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                      <span class="text-gray-500">Order No.</span>
                      <span class="font-semibold">{{ tableDetails.order?.order_no ?? '-' }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                      <span class="text-gray-500">Pax</span>
                      <span class="font-semibold">{{ tableDetails.pax ?? 0 }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                      <span class="text-gray-500">Status</span>
                      <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                        :class="{
                          'bg-green-100 text-green-700': tableDetails.status === 'open',
                          'bg-red-100 text-red-700': tableDetails.status === 'cancelled',
                          'bg-gray-100 text-gray-700': tableDetails.status === 'closed',
                          'bg-yellow-100 text-yellow-700': tableDetails.status !== 'open' && tableDetails.status !== 'cancelled' && tableDetails.status !== 'closed',
                        }"
                      >
                        {{ tableDetails.status ?? '-' }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="bg-gray-50 rounded-xl p-4 border">
                  <h3 class="text-sm font-semibold text-gray-700 mb-3">Transaction Info</h3>

                  <div class="space-y-2 text-sm text-gray-700">
                    <div class="flex justify-between gap-4">
                      <span class="text-gray-500">Frontdoor</span>
                      <span class="font-semibold">{{ tableDetails.frontdoor?.name ?? '-' }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                      <span class="text-gray-500">Cashier</span>
                      <span class="font-semibold">{{ tableDetails.cashier?.name ?? '-' }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                      <span class="text-gray-500">Opened At</span>
                      <span class="font-semibold">{{ tableDetails.opened_at ?? '-' }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                      <span class="text-gray-500">Closed At</span>
                      <span class="font-semibold">{{ tableDetails.closed_at ?? '-' }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                      <span class="text-gray-500">Total Amount</span>
                      <span class="font-bold text-green-700">
                        ₱{{ Number(tableDetails.total_amount ?? 0).toFixed(2) }}
                      </span>
                    </div>

                    <div class="pt-2">
                      <span class="text-gray-500 block mb-1">Remarks</span>
                      <div class="bg-white border rounded-lg px-3 py-2 text-sm text-gray-700 min-h-[44px]">
                        {{ tableDetails.remarks ?? 'No remarks available.' }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Head Count Breakdown -->
              <div class="bg-white border rounded-xl overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b">
                  <h3 class="text-sm font-semibold text-gray-700">Head Count Breakdown</h3>
                </div>

                <div class="overflow-x-auto">
                  <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 text-gray-700">
                      <tr>
                        <th class="px-4 py-3 text-left font-semibold">Category</th>
                        <th class="px-4 py-3 text-center font-semibold">Qty</th>
                        <th class="px-4 py-3 text-right font-semibold">Price</th>
                        <th class="px-4 py-3 text-right font-semibold">Subtotal</th>
                      </tr>
                    </thead>

                    <tbody>
                      <tr
                        v-for="head in tableDetails.head_counts"
                        :key="head.id"
                        class="border-t"
                      >
                        <td class="px-4 py-3">
                          {{ head.head_rule?.label ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                          {{ head.qty ?? 0 }}
                        </td>
                        <td class="px-4 py-3 text-right">
                          ₱{{ Number(head.price_snapshot ?? 0).toFixed(2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-medium">
                          ₱{{ Number(head.subtotal ?? 0).toFixed(2) }}
                        </td>
                      </tr>

                      <tr v-if="!tableDetails.head_counts?.length">
                        <td colspan="4" class="px-4 py-4 text-center text-gray-500">
                          No head count records found.
                        </td>
                      </tr>
                    </tbody>

                    <tfoot v-if="tableDetails.head_counts?.length" class="bg-gray-50 border-t">
                      <tr>
                        <td colspan="3" class="px-4 py-3 text-right font-bold text-gray-700">
                          Total Head Count Amount
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-green-700">
                          ₱{{
                            tableDetails.head_counts
                              .reduce((sum, item) => sum + Number(item.subtotal ?? 0), 0)
                              .toFixed(2)
                          }}
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t bg-gray-50 flex justify-end">
              <button
                @click="closeDetailModal"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
              >
                Close
              </button>
            </div>
          </div>
        </div>
        <!-- Toast -->
        <div v-if="toast.show" class="fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded shadow-lg transition-opacity duration-300 z-[9999]">
          {{ toast.message }}
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>

import { Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useTableOccupancy } from '@/Composables/tables/useTableOccupancy'

const {
  search,
  cancelReason,
  showAssignModal,
  showDetailModal,
  showCancelModal,
  closeDetailModal,
  tableDetails,
  modalSearch,
  pax,
  headCounts,
  customerName,
  head_prices,
  toast,
  isPaid,
  formatSessionStatus,
  getStatusBadgeClass,
  checkPaid,
  openAssignModal,
  closeAssignModal,
  cancelTableSession,
  closeCancelModal,
  hasCashier,
  cancelOrder,
  filteredTableSessions,
  filteredModalTables,
  isTableOccupied,
  updateTotalPax,
  markVacant,
  viewDettails,
  assignTable,
  formatDuration,
  getDurationClass,

  managers,
  selectedManager,
  managerPassword,

  getStayAlertText,
  getStayAlertIcon,
  getStayAlertClass,
  getStayAlertTextClass,
  getRowAlertClass,
} = useTableOccupancy()
</script>