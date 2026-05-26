<template>
  <AuthenticatedLayout>
  <div class="relative p-4 max-h-screen font-poppins">

    <!-- LOADER -->
    <div v-if="isLoading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-8 rounded-xl shadow-xl flex flex-col items-center">
        <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
        <p class="text-lg font-semibold text-gray-700">Processing Order...</p>
      </div>
    </div>

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-3">
      <div class="flex items-center gap-3">
        <!-- <BuildingStorefrontIcon class="w-8 h-8 text-green-600" /> -->
         <!-- <img 
            src="/storage/web_images/hsb1v2.jpg" 
            alt="Logo"
            class="h-10 w-auto object-contain"
          />
        <h1 class="text-3xl font-bold text-green-700">Hapag sa Balai</h1> -->
        <h1 class="text-3xl font-bold text-black-600">POS</h1>
        
      </div>
      <!-- <OperationLinks /> -->
    </div>

    <div class="flex flex-col lg:flex-row gap-6">

      <!-- LEFT COLUMN -->
      <div class="w-full lg:w-1/2 bg-white p-5 rounded-xl shadow-lg">
        <div class="h-[600px] overflow-y-auto">
        <h2 class="text-xl font-semibold mb-4">Table Sessions</h2>

        <div
          v-if="filteredTableSessions.length === 0"
          class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center shadow-sm"
        >
          <div class="mb-3">
            <span class="fa fa-utensils text-3xl text-gray-900"></span>
          </div>

          <p class="text-gray-700 font-semibold text-lg">
            No Reserved Tables Yet
          </p>

          <p class="text-gray-600 text-sm mb-4">
            No reservations from front door.
          </p>

          <button
            @click="refreshList"
            :disabled="loading"
            class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition text-sm disabled:bg-gray-300 disabled:cursor-not-allowed"
          >
            <span v-if="!loading">
              <span class="fa fa-refresh mr-1"></span>
              Refresh List
            </span>
            <span v-else>
              <span class="fa fa-spinner fa-spin mr-1"></span>
              Refreshing...
            </span>
          </button>
        </div>

        <div
          v-else
          v-for="session in filteredTableSessions"
          :key="session.id"
          class="border rounded mb-3"
        >

          <!-- HEADER -->
          <div
            class="flex justify-between items-center p-3 cursor-pointer hover:bg-gray-200"
            @click="toggleAccordion(session.id)"
          >
            <!-- LEFT SIDE -->
            <div>
              <p class="font-semibold">
                {{ session.table.name }} - {{ session.ref_no }} ({{ session.customer_name }})
              </p>
              <p class="text-sm text-gray-500">
                {{ session.pax }} pax
              </p>
            </div>

            <!-- RIGHT SIDE -->
            <div class="flex items-center gap-3">

              <!-- Small Select Button -->
              <button
                class="text-xs bg-gray-700 text-white px-3 py-1 rounded hover:bg-gray-800"
                @click.stop="selectTable(session)"
              >
                Select
                <span class="fa fa-arrow-circle-right"></span>
              </button>

              <!-- Arrow -->
              <span class="text-sm">
                {{ openAccordion === session.id ? '▲' : '▼' }}
              </span>

            </div>
          </div>

          <!-- BODY -->
          <div
            v-if="openAccordion === session.id"
            class="p-3 bg-gray-50 border-t"
          >
            <div
              v-for="head in session.head_counts"
              :key="head.id"
              class="flex justify-between mb-2"
            >
              <div>
                <p class="font-medium">
                  {{ head.head_rule.label }}
                </p>
                <p class="text-sm text-gray-500">
                  Price: ₱{{ Number(head.price_snapshot).toFixed(2) }}
                </p>
                <p class="text-sm text-gray-500">
                  Subtotal:
                  ₱{{ Number(head.subtotal ?? head.qty * head.price_snapshot).toFixed(2) }}
                </p>
              </div>

              <div class="text-sm text-gray-700">
                Qty: {{ head.qty }}
              </div>
            </div>
          </div>
        </div>

      </div>
        
      </div>

      <!-- RIGHT COLUMN -->
      <div class="w-full lg:w-1/2 bg-white p-5 rounded-xl shadow-lg">
        <h2 class="text-xl font-semibold mb-4">Cart / Checkout</h2>

        <div v-if="cart.length === 0" class="text-gray-500">
          No table selected yet.
        </div>

        <div v-for="(item,index) in cart" :key="item.id" class="border-b pb-3 mb-3">

          <div class="flex justify-between items-center">
            <p class="font-semibold">
              {{ item.table.name }} ({{ item.ref_no }})
            </p>
            <button class="text-red-600 text-sm" @click="removeFromCart(index)">
              Remove
            </button>
          </div>

          <div v-for="head in item.head_counts" :key="head.id" class="mt-2">
            <div class="flex justify-between">
              <span>{{ head.head_rule.label }}</span>
              <span>Qty: {{ head.qty }}</span>
            </div>
            <div class="text-sm text-gray-500 ml-1">
              <p>Price: ₱{{ Number(head.price_snapshot).toFixed(2) }}</p>
              <p>Subtotal: ₱{{ (head.qty * head.price_snapshot).toFixed(2) }}</p>
            </div>
          </div>
        </div>

        <div v-if="cart.length > 0">

          <!-- SC/PWD -->
          <label v-if="is_discount_allowed === 1" class="flex items-center mb-2">
            <input type="checkbox"
                   v-model="isSeniorOrPWD"
                   @change="handleSeniorPWDChange"
                   :disabled="voucherApplied"
                   class="mr-2">
            Senior / PWD (20%)
          </label>

          <!-- VOUCHER -->
          <div class="mb-3">
            <div class="flex gap-2">
              <input v-model="voucherCode"
                     class="flex-1 border rounded px-3 py-1"
                     placeholder="Voucher Code"
                     :disabled="isSeniorOrPWD">
              <button @click="applyVoucher"
                      :disabled="isSeniorOrPWD"
                      class="bg-gray-800 text-white px-3 py-1 rounded">
                
                Apply
                <span class="fa fa-arrow-circle-right"></span>
              </button>
            </div>
            <p v-if="voucherError" class="text-red-600 text-sm mt-1">{{ voucherError }}</p>
            <p v-if="voucherSuccess" class="text-green-600 text-sm mt-1">{{ voucherSuccess }}</p>
          </div>

          <!-- TOTALS -->
          <p>Subtotal: ₱{{ subtotal.toFixed(2) }}</p>
          <p v-if="discountAmount > 0" class="text-green-600">
            Discount: -₱{{ discountAmount.toFixed(2) }}
          </p>
          <p class="font-bold text-lg">
            Total: ₱{{ totalPrice.toFixed(2) }}
          </p>

          <!-- PAYMENT -->
          <div class="mt-3">
            <select v-model="paymentMethod" class="w-full border rounded px-3 py-2">
              <option value="Cash">Cash</option>
              <option value="GCash">GCash</option>
              <option value="Card">Card</option>
            </select>

            <div v-if="paymentMethod==='Cash'" class="mt-2">
              <input type="number"
                     v-model="cashAmount"
                     class="w-full border rounded px-3 py-2"
                     placeholder="Cash Amount">
              <p v-if="cashAmount>=totalPrice" class="text-green-600">
                Change: ₱{{ changeAmount.toFixed(2) }}
              </p>
              <p v-else class="text-red-600">Insufficient Cash</p>
            </div>
          </div>

          <!-- <button @click="checkoutSelectedTable"
                  class="mt-4 w-full bg-blue-600 text-white px-4 py-2 rounded">
            <i class="fa fa-check-circle" aria-hidden="true"></i> Checkout
          </button> -->
          <button @click="checkoutSelectedTable"
                  class="mt-4 w-full bg-gray-800 text-white px-4 py-2 rounded">
            <i class="fa fa-check-circle" aria-hidden="true"></i> Checkout
          </button>

        </div>
      </div>
    </div>

    <!-- Print Receipt Modal -->
    <div v-if="showPrintModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
     <div class="bg-white rounded-lg w-full max-w-md p-6">
       <h2 class="text-xl font-semibold mb-4">Order Successfully Placed 🎉</h2>
       <p class="mb-4">Order No: <strong>{{ lastOrderNo }}</strong></p>
       <div class="flex justify-end gap-2">
         <button class="px-4 py-2 bg-gray-300 rounded" @click="showPrintModal=false">Close</button>
         <button class="px-4 py-2 bg-blue-600 text-white rounded" @click="printReceipt">Print Receipt</button>
       </div>
     </div>
    </div>

    <!-- MANAGER MODAL -->
    <div v-if="showManagerDiscountModal"
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">

      <div class="bg-white rounded-lg w-full max-w-md p-6">

        <h2 class="text-xl font-semibold mb-4">Manager Approval</h2>

        <div v-for="manager in managers"
             :key="manager.id"
             class="border-b">

          <div class="p-2 cursor-pointer hover:bg-gray-100"
               @click="selectedManager=manager">
            {{ manager.name }}
          </div>

          <div v-if="selectedManager?.id===manager.id" class="p-3 bg-gray-50">
            <input type="password"
                   v-model="managerPassword"
                   class="w-full border rounded px-3 py-2"
                   placeholder="Enter Password">
            <p v-if="passwordError" class="text-red-600 text-sm mt-1">
              {{ passwordError }}
            </p>
          </div>
        </div>

        <div class="flex justify-end gap-2 mt-4">
          <button @click="closeManagerModal"
                  class="px-4 py-2 bg-gray-300 rounded">
            Cancel
          </button>

          <button @click="useManagerAccount"
                  class="px-4 py-2 bg-blue-600 text-white rounded">
            Approve
          </button>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <div v-if="showToast" class="fixed top-5 right-5 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50">
      {{ toastMessage }}
    </div>

  </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePosIndex } from '@/Composables/pos/usePosIndex'

const {
  managers,
  is_discount_allowed,

  cart,
  openAccordion,

  showPrintModal,
  isSeniorOrPWD,
  voucherCode,
  voucherApplied,
  voucherDiscount,

  showManagerDiscountModal,
  selectedManager,
  managerPassword,
  passwordError,

  paymentMethod,
  cashAmount,
  isLoading,

  voucherError,
  voucherSuccess,

  toastMessage,
  showToast,

  lastOrderNo,
  loading,

  refreshList,
  selectTable,
  removeFromCart,

  handleSeniorPWDChange,
  applyVoucher,
  useManagerAccount,
  closeManagerModal,

  subtotal,
  discountAmount,
  totalPrice,
  changeAmount,

  checkoutSelectedTable,

  filteredTableSessions,
  toggleAccordion,
  printReceipt,
} = usePosIndex()
</script>