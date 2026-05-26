<template>
  <div class="p-4 bg-gray-50 min-h-screen">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-3xl font-bold text-black-600">Orders</h1>
        <OperationLinks />
    </div>

    <!-- Filters -->
    <div class="mb-4 flex flex-wrap gap-4 items-center">
      <!-- Date filter -->
      <div class="flex items-center gap-2">
        <label class="text-sm font-medium text-gray-700">Date:</label>
        <input
          type="date"
          v-model="selectedDate"
          @change="filterByDate"
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

      <!-- Status filter -->
      <div class="flex items-center gap-2">
        <label class="text-sm font-medium text-gray-700">Status:</label>
        <select
          v-model="selectedStatus"
          @change="filterByStatus"
          class="border rounded px-2 py-1 text-sm"
        >
          <option value="">All</option>
          <option value="pending">Pending</option>
          <option value="paid">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
    </div>

    <!-- Flash messages -->
    <div v-if="$page.props.flash.success" class="mb-4 p-2 bg-green-100 text-green-700 rounded">
      {{ $page.props.flash.success }}
    </div>
    <div v-if="$page.props.flash.error" class="mb-4 p-2 bg-red-100 text-red-700 rounded">
      {{ $page.props.flash.error }}
    </div>

    <!-- Table -->
    <div class="bg-white rounded shadow overflow-hidden">
      <!-- Scroll wrapper -->
      <div class="max-h-[60vh] overflow-y-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-100 sticky top-0 z-10">
            <tr class="text-left">
              <th class="px-4 py-2">#</th>
              <th class="px-4 py-2">Order #</th>
              <th class="px-4 py-2">Table #</th>
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
              <!-- Order No + Status -->
              <td class="px-4 py-2 font-medium">
                {{ index+1 }}
              </td>
              <td class="px-4 py-2 font-medium">
                <div class="flex items-center gap-2">
                  <span>{{ order.order_no }}</span>

                  <span
                    v-if="order.status === 'cancelled'"
                    class="text-xs bg-red-200 text-red-800 px-2 py-0.5 rounded"
                  >
                    Status: Cancelled
                  </span>
                </div>
              </td>

              <!-- table number -->
              <td class="px-4 py-2 text-gray-600">
                {{ order.table_session?.table?.name || 'N/A' }} 
                ({{ order.table_session?.ref_no || 'N/A' }}) - 
                ({{ order.table_session?.pax || 'N/A' }} pax)
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
                ₱{{ Number(order.total).toFixed(2) }}
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
                  Print
                </button>

                <button
                  @click="openModal(order)"
                  class="px-2 py-1 text-xs bg-gray-200 rounded hover:bg-gray-300"
                >
                  View
                </button>

                <button
                  v-if="order.status !== 'cancelled'"
                  @click="openCancelOrder(order)"
                  class="px-2 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600"
                >
                  Cancel
                </button>
              </td>
            </tr>

            <!-- Empty state -->
            <tr v-if="filteredOrders.length === 0">
              <td colspan="5" class="text-center py-6 text-gray-400">
                No orders found
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Manager Approval Cancel Modal -->
    <div v-if="showCancelModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center">
      <div class="bg-white rounded shadow-lg w-96 p-5">
        
        <h2 class="text-lg font-bold mb-2 text-red-600">
          Manager Approval Required
        </h2>

        <p class="text-sm text-gray-600 mb-3">
          Cancelling Order #{{ selectedOrder?.order_no }} requires manager authorization.
        </p>

        <!-- Reason -->
        <label class="text-xs font-medium text-gray-700">Reason</label>
        <textarea
          v-model="cancelReason"
          placeholder="Reason for cancellation"
          class="w-full border rounded px-3 py-2 mb-3 text-sm"
        ></textarea>

        <div 
          v-for="manager in managers" 
          :key="manager.id"
          class="flex items-center gap-2 border-b py-2"
        >
          <input
            type="checkbox"
            :id="'manager-' + manager.id"
            :value="manager.id"
            v-model="selectedManagers"
            class="h-4 w-4 text-indigo-600 border-gray-300 rounded"
          />

          <label
            :for="'manager-' + manager.id"
            class="text-sm text-gray-800 cursor-pointer"
          >
            {{ manager.name }} ({{ manager.email }})
          </label>
        </div>

        <!-- Manager Password -->
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

    <!-- Manager Approval Cancel Modal -->
    <div v-if="showCancelItemModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center">
      <div class="bg-white rounded shadow-lg w-96 p-5">
        
        <h2 class="text-lg font-bold mb-2 text-red-600">
          Manager Approval Required
        </h2>

        <p class="text-sm text-gray-600 mb-3">
          Cancelling Ordered Item <b>{{ itemToCancel?.menu.name }}</b> requires manager authorization.
        </p>

        <!-- Reason -->
        <label class="text-xs font-medium text-gray-700">Reason</label>
        <textarea
          v-model="cancelReason"
          placeholder="Reason for cancellation"
          class="w-full border rounded px-3 py-2 mb-3 text-sm"
        ></textarea>

        <div 
          v-for="manager in managers" 
          :key="manager.id"
          class="flex items-center gap-2 border-b py-2"
        >
          <input
            type="checkbox"
            :id="'manager-' + manager.id"
            :value="manager.id"
            v-model="selectedManagers"
            class="h-4 w-4 text-indigo-600 border-gray-300 rounded"
          />

          <label
            :for="'manager-' + manager.id"
            class="text-sm text-gray-800 cursor-pointer"
          >
            {{ manager.name }} ({{ manager.email }})
          </label>
        </div>

        <!-- Manager Password -->
        <label class="text-xs font-medium text-gray-700">Manager Password</label>
        <input
          type="password"
          v-model="managerPassword"
          placeholder="Enter manager password"
          class="w-full border rounded px-3 py-2 mb-4 text-sm"
        />

        <div class="flex justify-end gap-2">
          <button
            @click="closeCancelItemModal"
            class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300"
          >
            Close
          </button>

          <button
            @click="confirmCancelItem"
            class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700"
          >
            Approve & Cancel
          </button>
        </div>

      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center">
      <div class="bg-white rounded shadow-lg w-96 p-4">
        <h2 class="text-lg font-bold mb-2">
          Order #{{ selectedOrder?.order_no }}
        </h2>

        <!-- Barcode -->
        <div class="mb-4 flex justify-center">
          <svg id="barcode"></svg>
        </div>

        <!-- Cancelled notice -->
        <div v-if="selectedOrder.status === 'cancelled'">
          <p class="text-sm text-red-600 mb-4">
            This order was cancelled by {{ selectedOrder.user.name }}.
            Reason: {{ selectedOrder.cancellation_remarks }}
          </p>
        </div>
        <div v-else>
          <p class="text-sm text-gray-600 mb-4">
            Placed by {{ selectedOrder.user.name }} on
            {{ new Date(selectedOrder.created_at).toLocaleString() }}
          </p>
        </div>



        <!-- Items -->
        <ul>
          <li
            v-for="item in selectedOrder.items"
            :key="item.id"
            class="flex justify-between items-center border-b py-1"
          >
            <span>
              {{ item.menu.name }} (x{{ item.quantity }})
              — ₱{{ Number(item.price).toFixed(2) }}
              <span v-if="item.discount && item.discount > 0" class="text-xs text-green-600">
                (Discount: ₱{{ Number(item.discount * item.quantity).toFixed(2) }})
              </span>
            </span>

            <!-- Cancel button -->
            <button
              @click="cancelItem(item)"
              :disabled="selectedOrder.status === 'cancelled' || item.cancelled"
              class="text-xs"
              :class="selectedOrder.status === 'cancelled' || item.cancelled
                ? 'text-gray-400 cursor-not-allowed'
                : 'text-red-600 hover:underline'"
            >
              Cancel
            </button>
          </li>
        </ul>

        <!-- Order totals -->
        <div class="mt-4 border-t pt-2 text-right">
          <p class="text-sm">
            Subtotal: ₱{{ Number(orderTotals.subtotal).toFixed(2) }}
          </p>
          <p v-if="orderTotals.total_discount > 0" class="text-sm text-green-600">
            Discount
            <span v-if="selectedOrder.voucher_no_used">
              (Voucher: {{ selectedOrder.voucher_no_used }})
            </span>
            <span v-else-if="selectedOrder.discount_type">
              ({{ selectedOrder.discount_type }})
            </span>
            :
            -₱{{ Number(orderTotals.total_discount).toFixed(2) }}
          </p>

          <p class="text-lg font-bold">
            Total: ₱{{ Number(orderTotals.total).toFixed(2) }}
          </p>
        </div>

        <div class="flex justify-end mt-4">
          <button
            @click="showModal = false"
            class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300"
          >
            Close
          </button>
        </div>
      </div>
    </div>

  </div>
</template>


<script setup>
import { ref, computed, reactive, watch } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import { Inertia } from '@inertiajs/inertia'
import axios from 'axios'
import JsBarcode from 'jsbarcode'
import OperationLinks from '@/Components/NavLinks/OperationLinks.vue'
import useRolePrefix from '@/Composables/useRolePrefix'
const { prefix } = useRolePrefix()

const { props } = usePage()
const orders = props.orders ?? []
const managers = props.managers ?? []

console.log(orders)

const showModal = ref(false)
const showCancelModal = ref(false)
const showCancelItemModal = ref(false)
const selectedOrder = ref(null)
const selectedDate = ref(props.selectedDate ?? new Date().toISOString().slice(0, 10))
const search = ref('')
const cancelReason = ref('')
const selectedStatus = ref('')
const managerPassword = ref('')
const selectedManagers = ref([])
const itemToCancel = ref(null) // store the item being cancelled

// Reactive totals object for modal
const orderTotals = reactive({
  subtotal: 0,
  total_discount: 0,
  total: 0,
})

const filteredOrders = computed(() => {
  let result = orders

  // Filter by date
  if (selectedDate.value) {
    result = result.filter(order =>
      order.created_at.startsWith(selectedDate.value)
    )
  }

  // Filter by status
  if (selectedStatus.value) {
    result = result.filter(order => order.status === selectedStatus.value)
  }

  // Filter by search
  if (search.value) {
    const q = search.value.toLowerCase()
    result = result.filter(order =>
      order.order_no.toLowerCase().includes(q) ||
      String(order.total).includes(q)
    )
  }

  return result
})


// Optional: if you want server-side filter
function filterOrders() {
  Inertia.get(`/${prefix.value}/orders`, {
    date: selectedDate.value,
    status: selectedStatus.value,
    search: search.value
  }, { preserveState: true, replace: true })
}

function openModal(order) {
  // console.log('Open order details modal for:', order.user.name)
  // selectedOrder.value = order
  // showModal.value = true
  selectedOrder.value = JSON.parse(JSON.stringify(order)) // deep copy
  computeTotals()
  showModal.value = true
}


// Compute totals from current order items
function computeTotals() {
  if (!selectedOrder.value) return

  const activeItems = selectedOrder.value.items.filter(i => !i.cancelled)

  // 1️⃣ Subtotal
  const subtotal = activeItems.reduce((sum, i) => {
    return sum + (Number(i.price) * i.quantity)
  }, 0)

  let total_discount = 0

  // 2️⃣ If voucher exists → use voucher discount
  if (selectedOrder.value.voucher_no_used) {
    total_discount = Number(selectedOrder.value.voucher_discount_used || 0)

    // Prevent voucher from exceeding subtotal
    if (total_discount > subtotal) {
      total_discount = subtotal
    }
  }

  // 3️⃣ If no voucher → apply normal discount logic
  else if (activeItems.length && activeItems[0].discount != null) {
    total_discount = activeItems.reduce((sum, i) => {
      return sum + (Number(i.discount || 0) * i.quantity)
    }, 0)
  }

  else if (selectedOrder.value.discount_type === 'PWD/Senior') {
    total_discount = subtotal * 0.2
  }

  else if (selectedOrder.value.discount_type === 'Employee') {
    total_discount = subtotal * 0.1
  }

  // 4️⃣ Final total
  const total = Math.max(subtotal - total_discount, 0)

  orderTotals.subtotal = subtotal
  orderTotals.total_discount = total_discount
  orderTotals.total = total
}



// Trigger modal to cancel single item
function cancelItem(item) {
  if (selectedOrder.value.status === 'cancelled' || item.cancelled) return
  itemToCancel.value = item
  cancelReason.value = ''
  managerPassword.value = ''
  selectedManagers.value = []
  showModal.value = false
  showCancelItemModal.value = true
}

// Actually cancel the item after manager approval
async function confirmCancelItem() {
  console.log('Selected managers for approval:', selectedManagers.value)
  console.log('Manager password entered:', managerPassword.value)
  console.log('Reason for cancellation:', cancelReason.value)
  console.log('Item to cancel:', itemToCancel.value)
  if (!cancelReason.value.trim()) {
    alert('Please provide a reason for cancellation.')
    return
  }

  if (!managerPassword.value.trim()) {
    alert('Manager password is required.')
    return
  }

  try {
    await axios.post(
      route(`${prefix.value}.orders.cancel_item`, itemToCancel.value.id),
      {
        reason: cancelReason.value,
        manager_id: selectedManagers.value?.[0], // pick first selected manager
        manager_password: managerPassword.value
      }
    )

    // Update local state instantly
    const index = selectedOrder.value.items.findIndex(i => i.id === itemToCancel.value.id)
    if (index > -1) {
      selectedOrder.value.items[index].cancelled = 1
      computeTotals()
    }

    closeCancelItemModal()
    itemToCancel.value = null

  } catch (err) {
    console.error(err)
    alert(err.response?.data?.message || 'Failed to cancel item.')
  }
}

function openCancelOrder(order) {
  showCancelModal.value = true
  selectedOrder.value = order
}

// const cancelOrder = () => {
//   if (!cancelReason.value.trim()) {
//     alert('Please provide a reason for cancellation.')
//     return
//   }

//   Inertia.post(
//     route(`${prefix.value}.orders.cancel`, selectedOrder.value.id),
//     { reason: cancelReason.value },
//     {
//       preserveState: true,
//       replace: true,
//       onSuccess: () => {
//         showCancelModal.value = false
//         cancelReason.value = ''
//       }
//     }
//   )
// }

const cancelOrder = () => {
  console.log('Selected managers for approval:', selectedManagers.value)
  console.log('Manager password entered:', managerPassword.value)
  if (!cancelReason.value.trim()) {
    alert('Please provide a reason.')
    return
  }

  if (!managerPassword.value.trim()) {
    alert('Manager password is required.')
    return
  }

  try {
    Inertia.post(
      route(`${prefix.value}.orders.cancel`, selectedOrder.value.id),
      {
        reason: cancelReason.value,
        manager: selectedManagers.value,
        manager_password: managerPassword.value
      },
      {
        preserveState: true,
        replace: true,
        onSuccess: () => {
          closeCancelModal()
        }
      }
    )
  } catch (err) {
    alert('Manager authorization failed.')
  }
}

function closeCancelModal() {
  showCancelModal.value = false
  cancelReason.value = ''
  managerPassword.value = ''
}

// Close item cancel modal
function closeCancelItemModal() {
  showCancelItemModal.value = false
  cancelReason.value = ''
  managerPassword.value = ''
  selectedManagers.value = []
  itemToCancel.value = null
}

function printReceipt(order) {
  console.log(prefix.value)
  // window.print()
  window.open(route(`${prefix.value}.orders.pdf`, order.order_no), '_blank');
}

function filterByDate() {
  Inertia.get(`/${prefix.value}/orders`, { date: selectedDate.value }, {
    preserveState: true,
    replace: true
  })
}

watch(selectedOrder, (order) => {
  if (order && showModal.value) {
    // Generate barcode
    setTimeout(() => {
      JsBarcode('#barcode', order.order_no, {
        format: 'CODE128', // Code 128 works for numbers & letters
        width: 2,
        height: 50,
        displayValue: true
      });
    }, 50); // Slight delay to ensure SVG is in DOM
  }
});
</script>
