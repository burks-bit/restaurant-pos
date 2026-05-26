<template>
  <AuthenticatedLayout>
    <div class="relative p-4 max-h-screen font-poppins">

      <!-- LOADER -->
      <div v-if="isLoading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]">
        <div class="bg-white p-8 rounded-xl shadow-xl flex flex-col items-center">
          <div class="w-12 h-12 border-4 border-gray-800 border-t-transparent rounded-full animate-spin mb-4"></div>
          <p class="text-lg font-semibold text-gray-700">Processing Order...</p>
        </div>
      </div>

      <!-- HEADER -->
      <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-3">
        <div class="flex items-center gap-3">
          <h1 class="text-3xl font-bold text-black-600">POS</h1>
        </div>

        <div class="flex flex-wrap gap-2">
          <button
            @click="refreshList"
            :disabled="loading"
            class="bg-gray-700 text-white px-4 py-2 rounded text-sm hover:bg-gray-800 disabled:bg-gray-300"
          >
            <span v-if="!loading">
              <span class="fa fa-refresh mr-1"></span> Refresh
            </span>
            <span v-else>
              <span class="fa fa-spinner fa-spin mr-1"></span> Refreshing...
            </span>
          </button>

          <button
            @click="showSelectTableModal = true"
            class="bg-gray-800 text-white px-4 py-2 rounded text-sm hover:bg-black"
          >
            <i class="fa fa-table mr-1"></i> Select Table
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- LEFT COLUMN -->
        <div class="bg-white p-5 rounded-xl shadow-lg min-h-[700px]">
          <h2 class="text-xl font-semibold mb-4">Selected Table / Base Dine-in</h2>

          <div v-if="cart.length === 0" class="border rounded-lg p-6 bg-gray-50 text-center">
            <div class="mb-3">
              <span class="fa fa-table text-3xl text-gray-500"></span>
            </div>
            <p class="font-semibold text-gray-700">No table selected yet.</p>
            <p class="text-sm text-gray-500 mt-1 mb-4">Select a table session to begin billing.</p>

            <button
              @click="showSelectTableModal = true"
              class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-black"
            >
              <i class="fa fa-table mr-1"></i> Select Table
            </button>
          </div>

          <div v-else>
            <!-- SELECTED TABLE -->
            <div class="border rounded-xl p-4 mb-4 bg-gray-50">
              <div class="flex justify-between items-start gap-4">
                <div>
                  <p class="font-semibold text-xl text-gray-800">
                    {{ cart[0].table?.name }} ({{ cart[0].ref_no }})
                  </p>
                  <p class="text-sm text-gray-600 mt-1">
                    Customer: <span class="font-medium">{{ cart[0].customer_name }}</span>
                  </p>
                  <p class="text-sm text-gray-600">
                    Pax: <span class="font-medium">{{ cart[0].pax }}</span>
                  </p>
                  <p class="text-sm text-gray-600">
                    Payment Status:
                    <span class="font-medium">
                      {{ cart[0].order?.payment_status ?? 'Unpaid / Pending Final Billing' }}
                    </span>
                  </p>
                </div>

                <button
                  class="text-red-600 text-sm hover:text-red-700"
                  @click="removeFromCart(0)"
                >
                  Remove
                </button>
              </div>
            </div>

            <!-- BASE DINE-IN DETAILS -->
            <div class="border rounded-xl p-4">
              <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800 text-lg">Base Dine-in Details</h3>
                <span class="text-xs bg-gray-100 text-gray-600 px-3 py-1 rounded-full">
                  Frontdoor Posted
                </span>
              </div>

              <div v-if="cart[0].head_counts?.length === 0" class="text-sm text-gray-500">
                No base dine-in details found.
              </div>

              <div v-else class="space-y-3">
                <div
                  v-for="head in cart[0].head_counts"
                  :key="head.id"
                  class="border rounded-lg p-3 bg-white"
                >
                  <div class="flex justify-between items-start">
                    <div>
                      <p class="font-medium text-gray-800">{{ head.head_rule?.label }}</p>
                      <p class="text-sm text-gray-500 mt-1">
                        ₱{{ Number(head.price_snapshot).toFixed(2) }} × {{ head.qty }}
                      </p>
                    </div>

                    <div class="font-semibold text-gray-800">
                      ₱{{ Number(head.subtotal).toFixed(2) }}
                    </div>
                  </div>
                </div>
              </div>

              <div class="border-t mt-4 pt-4 flex justify-between text-lg font-bold text-gray-900">
                <span>Base Dine-in Total</span>
                <span>₱{{ baseSubtotal.toFixed(2) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="bg-white p-5 rounded-xl shadow-lg min-h-[700px]">
          <h2 class="text-xl font-semibold mb-4">Add-ons / Leftovers / Checkout</h2>

          <div v-if="cart.length === 0" class="border rounded-lg p-6 bg-gray-50 text-center text-gray-500">
            Select a table first before posting add-ons, leftovers, and payment.
          </div>

          <div v-else class="space-y-4">

            <!-- ACTION BUTTONS -->
            <div class="flex flex-wrap gap-2">
              <button
                @click="openAddonModal"
                class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700"
              >
                <i class="fa fa-plus-circle mr-1"></i> Select Add-ons
              </button>

              <button
                @click="openLeftoverModal"
                class="bg-orange-600 text-white px-4 py-2 rounded text-sm hover:bg-orange-700"
              >
                <i class="fa fa-cutlery mr-1"></i> Post Leftover
              </button>
            </div>

            <!-- ADD-ONS -->
            <div class="border rounded-xl p-4">
              <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-800 text-lg">Add-ons</h3>
                <button
                  @click="openAddonModal"
                  class="text-sm bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200"
                >
                  + Add
                </button>
              </div>

              <div v-if="selectedAddons.length === 0" class="text-sm text-gray-500">
                No add-ons posted yet.
              </div>

              <div v-else class="space-y-3">
                <div
                  v-for="(addon, index) in selectedAddons"
                  :key="addon.id ?? `addon-${index}`"
                  class="border rounded-lg p-3"
                >
                  <div class="flex justify-between items-start gap-3">
                    <div>
                      <p class="font-medium text-gray-800">{{ addon.item_name }}</p>
                      <p class="text-sm text-gray-500 mt-1">
                        ₱{{ Number(addon.unit_price).toFixed(2) }} × {{ addon.quantity }}
                        <span v-if="addon.unit" class="ml-1 text-xs">({{ addon.unit }})</span>
                      </p>
                    </div>

                    <div class="text-right">
                      <p class="font-semibold text-gray-800">
                        ₱{{ Number(addon.subtotal).toFixed(2) }}
                      </p>
                      <span class="text-xs text-gray-400 mt-1 inline-block">
                        Posted
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="border-t mt-4 pt-4 flex justify-between font-semibold text-gray-900">
                <span>Add-ons Total</span>
                <span>₱{{ addonsTotal.toFixed(2) }}</span>
              </div>
            </div>

            <!-- LEFTOVERS -->
            <div class="border rounded-xl p-4">
              <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-800 text-lg">Leftover Charges</h3>
                <button
                  @click="openLeftoverModal"
                  class="text-sm bg-orange-100 text-orange-700 px-3 py-1 rounded hover:bg-orange-200"
                >
                  + Post
                </button>
              </div>

              <div v-if="leftovers.length === 0" class="text-sm text-gray-500">
                No leftover charges posted yet.
              </div>

              <div v-else class="space-y-3">
                <div
                  v-for="(leftover, index) in leftovers"
                  :key="`leftover-${index}`"
                  class="border rounded-lg p-3"
                >
                  <div class="flex justify-between items-start gap-3">
                    <div>
                      <p class="font-medium text-gray-800">
                        Weight: {{ leftover.weight }}
                      </p>
                      <p class="text-sm text-gray-500 mt-1">
                        Price: ₱{{ Number(leftover.price).toFixed(2) }}
                      </p>
                      <p v-if="leftover.remarks" class="text-xs text-gray-400 mt-1">
                        Remarks: {{ leftover.remarks }}
                      </p>
                    </div>

                    <div class="text-right">
                      <p class="font-semibold text-gray-800">
                        ₱{{ Number(leftover.price).toFixed(2) }}
                      </p>
                      <button
                        class="text-red-600 text-sm mt-1"
                        @click="removeLeftover(index)"
                      >
                        Remove
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="border-t mt-4 pt-4 flex justify-between font-semibold text-gray-900">
                <span>Leftover Total</span>
                <span>₱{{ leftoversTotal.toFixed(2) }}</span>
              </div>
            </div>

            <!-- CHECKOUT -->
            <div class="border rounded-xl p-4 bg-gray-50">
              <h3 class="font-semibold text-gray-800 text-lg mb-4">Checkout Details</h3>

              <label v-if="is_discount_allowed === 1" class="flex items-center mb-3">
                <input
                  type="checkbox"
                  v-model="isSeniorOrPWD"
                  @change="handleSeniorPWDChange"
                  :disabled="voucherApplied"
                  class="mr-2"
                >
                Senior / PWD (20%)
              </label>

              <div class="mb-4">
                <div class="flex gap-2">
                  <input
                    v-model="voucherCode"
                    class="flex-1 border rounded px-3 py-2 bg-white"
                    placeholder="Voucher Code"
                    :disabled="isSeniorOrPWD"
                  >
                  <button
                    @click="applyVoucher"
                    :disabled="isSeniorOrPWD"
                    class="bg-gray-800 text-white px-3 py-2 rounded"
                  >
                    Apply
                    <span class="fa fa-arrow-circle-right ml-1"></span>
                  </button>
                </div>
                <p v-if="voucherError" class="text-red-600 text-sm mt-1">{{ voucherError }}</p>
                <p v-if="voucherSuccess" class="text-green-600 text-sm mt-1">{{ voucherSuccess }}</p>
              </div>

              <div class="space-y-2 mb-4">
                <div class="flex justify-between">
                  <span>Base Dine-in</span>
                  <span>₱{{ baseSubtotal.toFixed(2) }}</span>
                </div>

                <div class="flex justify-between">
                  <span>Add-ons</span>
                  <span>₱{{ addonsTotal.toFixed(2) }}</span>
                </div>

                <div class="flex justify-between">
                  <span>Leftovers</span>
                  <span>₱{{ leftoversTotal.toFixed(2) }}</span>
                </div>

                <div class="flex justify-between">
                  <span>Subtotal Before Discount</span>
                  <span>₱{{ subtotal.toFixed(2) }}</span>
                </div>

                <div v-if="discountAmount > 0" class="flex justify-between text-green-600">
                  <span>Discount</span>
                  <span>-₱{{ discountAmount.toFixed(2) }}</span>
                </div>

                <div class="flex justify-between text-xl font-bold border-t pt-3">
                  <span>Total</span>
                  <span>₱{{ totalPrice.toFixed(2) }}</span>
                </div>
              </div>

              <div class="space-y-3">
                <select v-model="paymentMethod" class="w-full border rounded px-3 py-2 bg-white">
                  <option value="Cash">Cash</option>
                  <option value="GCash">GCash</option>
                  <option value="Card">Card</option>
                </select>

                <div v-if="paymentMethod === 'Cash'">
                  <input
                    type="number"
                    v-model="cashAmount"
                    class="w-full border rounded px-3 py-2 bg-white"
                    placeholder="Cash Amount"
                  >
                  <p v-if="Number(cashAmount) >= totalPrice" class="text-green-600 mt-1">
                    Change: ₱{{ changeAmount.toFixed(2) }}
                  </p>
                  <p v-else class="text-red-600 mt-1">Insufficient Cash</p>
                </div>

                <button
                  @click="checkoutSelectedTable"
                  class="w-full bg-gray-800 text-white px-4 py-3 rounded hover:bg-black"
                >
                  <i class="fa fa-check-circle mr-1" aria-hidden="true"></i> Checkout
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- PRINT RECEIPT MODAL -->
      <div v-if="showPrintModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg w-full max-w-md p-6">
          <h2 class="text-xl font-semibold mb-4">Order Successfully Placed 🎉</h2>
          <p class="mb-4">Order No: <strong>{{ lastOrderNo }}</strong></p>
          <div class="flex justify-end gap-2">
            <button class="px-4 py-2 bg-gray-300 rounded" @click="showPrintModal = false">Close</button>
            <button class="px-4 py-2 bg-blue-600 text-white rounded" @click="printReceipt">Print Receipt</button>
          </div>
        </div>
      </div>

      <!-- MANAGER MODAL -->
      <div
        v-if="showManagerDiscountModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      >
        <div class="bg-white rounded-lg w-full max-w-md p-6">
          <h2 class="text-xl font-semibold mb-4">Manager Approval</h2>

          <div
            v-for="manager in managers"
            :key="manager.id"
            class="border-b"
          >
            <div
              class="p-2 cursor-pointer hover:bg-gray-100"
              @click="selectedManager = manager"
            >
              {{ manager.name }}
            </div>

            <div v-if="selectedManager?.id === manager.id" class="p-3 bg-gray-50">
              <input
                type="password"
                v-model="managerPassword"
                class="w-full border rounded px-3 py-2"
                placeholder="Enter Password"
              >
              <p v-if="passwordError" class="text-red-600 text-sm mt-1">
                {{ passwordError }}
              </p>
            </div>
          </div>

          <div class="flex justify-end gap-2 mt-4">
            <button
              @click="closeManagerModal"
              class="px-4 py-2 bg-gray-300 rounded"
            >
              Cancel
            </button>

            <button
              @click="useManagerAccount"
              class="px-4 py-2 bg-blue-600 text-white rounded"
            >
              Approve
            </button>
          </div>
        </div>
      </div>

      <!-- SELECT TABLE MODAL -->
      <div v-if="showSelectTableModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg w-full max-w-2xl p-6 max-h-[85vh] overflow-y-auto">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Select Table Session</h2>
            <button @click="showSelectTableModal = false" class="text-gray-500 hover:text-black">
              <i class="fa fa-times"></i>
            </button>
          </div>

          <div class="mt-1 pb-2">
            <input
              v-model="search"
              type="text"
              placeholder="Search table or ref no..."
              class="w-full md:w-100 border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-600 focus:border-blue-600"
            />
          </div>

          <div v-if="filteredTableSessions.length === 0" class="text-gray-500">
            No available open table sessions.
          </div>

          <div v-else class="space-y-3">

            

            <div
              v-for="session in filteredTableSessions"
              :key="`modal-table-${session.id}`"
              class="border rounded-lg p-4 hover:bg-gray-50"
            >
              <div class="flex justify-between items-start">
                <div>
                  <p class="font-semibold">{{ session.table?.name }} - {{ session.ref_no }} ({{ session.customer_name }})</p>
                  <p class="text-sm text-gray-500">{{ session.pax }} pax | Base Dining: ₱{{ getSessionBaseTotal(session).toFixed(2) }}</p>
                  
                </div>

                <button
                  @click="selectTable(session); showSelectTableModal = false"
                  class="bg-gray-800 text-white px-3 py-2 rounded text-sm hover:bg-black"
                >
                  Use This Table
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- REVISED ADD-ON MODAL -->
      <div v-if="showAddonModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg w-full max-w-5xl p-6 max-h-[90vh] overflow-hidden flex flex-col">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Select Add-ons</h2>
            <button @click="closeAddonModal" class="text-gray-500 hover:text-black">
              <i class="fa fa-times"></i>
            </button>
          </div>

          <!-- SEARCH -->
          <div class="mb-4">
            <input
              v-model="addonSearch"
              type="text"
              class="w-full border rounded px-3 py-2"
              placeholder="Search item name..."
            >
          </div>

          <!-- TABLE -->
          <div class="border rounded-lg overflow-hidden flex-1 min-h-0">
            <div class="overflow-auto max-h-[55vh]">
              <table class="min-w-full text-sm">
                <thead class="bg-gray-100 sticky top-0 z-10">
                  <tr class="text-left text-gray-700">
                    <th class="px-3 py-3 border-b w-[60px]">Select</th>
                    <th class="px-3 py-3 border-b">Item Name</th>
                    <th class="px-3 py-3 border-b w-[160px]">Price</th>
                    <th class="px-3 py-3 border-b w-[120px]">Qty</th>
                    <th class="px-3 py-3 border-b w-[160px]">Subtotal</th>
                  </tr>
                </thead>

                <tbody>
                  <tr v-if="filteredAddonItems.length === 0">
                    <td colspan="5" class="px-3 py-6 text-center text-gray-500">
                      No matching items found.
                    </td>
                  </tr>

                  <tr
                    v-for="item in filteredAddonItems"
                    :key="item.id"
                    class="hover:bg-gray-50"
                  >
                    <td class="px-3 py-3 border-b">
                      <input
                        type="checkbox"
                        :checked="isAddonSelected(item.id)"
                        @change="toggleAddonSelection(item)"
                      >
                    </td>

                    <td class="px-3 py-3 border-b">
                      {{ item.name }}
                    </td>

                    <td class="px-3 py-3 border-b">
                      ₱{{ Number(item.unit_price ?? item.price ?? 0).toFixed(2) }}
                    </td>

                    <td class="px-3 py-3 border-b">
                      <input
                        type="number"
                        min="1"
                        class="w-20 border rounded px-2 py-1"
                        :disabled="!isAddonSelected(item.id)"
                        :value="getAddonSelectedQty(item.id)"
                        @input="updateAddonSelectedQty(item.id, $event.target.value)"
                      >
                    </td>

                    <td class="px-3 py-3 border-b">
                      ₱{{ getAddonSelectedSubtotal(item).toFixed(2) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- FOOTER -->
          <div class="mt-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="text-sm text-gray-600">
              Selected:
              <strong>{{ addonSelectedItems.length }}</strong>
              item(s)
              |
              Total:
              <strong>₱{{ addonSelectedTotal.toFixed(2) }}</strong>
            </div>

            <div class="flex justify-end gap-2">
              <button @click="closeAddonModal" class="px-4 py-2 bg-gray-300 rounded">
                Cancel
              </button>
              <button @click="addSelectedAddonsToCart" class="px-4 py-2 bg-blue-600 text-white rounded">
                Add Selected Add-ons
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- LEFTOVER MODAL -->
      <div v-if="showLeftoverModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg w-full max-w-lg p-6">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Post Leftover Charge</h2>
            <button @click="closeLeftoverModal" class="text-gray-500 hover:text-black">
              <i class="fa fa-times"></i>
            </button>
          </div>

          <div class="space-y-4">

            <div>
              <label class="block text-sm font-medium mb-1">Weight</label>
              <input v-model="leftoverForm.weight" type="text" class="w-full border rounded px-3 py-2" placeholder="e.g. 250 grams or 0.25 kg">
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Price</label>
              <input v-model.number="leftoverForm.price" type="number" min="0" step="0.01" class="w-full border rounded px-3 py-2">
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Remarks</label>
              <textarea
                v-model="leftoverForm.remarks"
                class="w-full border rounded px-3 py-2"
                rows="3"
                placeholder="Reason / remarks"
              ></textarea>
            </div>

            <div class="bg-gray-50 rounded p-3 text-sm">
              Subtotal: <strong>₱{{ leftoverPreviewTotal.toFixed(2) }}</strong>
            </div>
          </div>

          <div class="flex justify-end gap-2 mt-6">
            <button @click="closeLeftoverModal" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
            <button @click="addLeftoverToCart" class="px-4 py-2 bg-orange-600 text-white rounded">Post Leftover</button>
          </div>
        </div>
      </div>

      <!-- TOAST -->
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
  search,
  
  managers,
  is_discount_allowed,

  cart,

  showPrintModal,
  isSeniorOrPWD,
  voucherCode,
  voucherApplied,

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

  filteredTableSessions,
  showSelectTableModal,
  showAddonModal,
  showLeftoverModal,

  leftoverForm,
  selectedAddons,
  leftovers,

  filteredAddonItems,
  addonSearch,
  addonSelectedItems,

  baseSubtotal,
  baseDineInTotal,
  addonsTotal,
  leftoversTotal,
  subtotal,
  discountAmount,
  totalPrice,
  changeAmount,

  leftoverPreviewTotal,
  addonSelectedTotal,

  refreshList,
  selectTable,
  removeFromCart,
  removeAddon,
  removeLeftover,
  openAddonModal,
  closeAddonModal,
  openLeftoverModal,
  closeLeftoverModal,

  toggleAddonSelection,
  isAddonSelected,
  getAddonSelectedQty,
  updateAddonSelectedQty,
  getAddonSelectedSubtotal,
  addSelectedAddonsToCart,

  addLeftoverToCart,

  handleSeniorPWDChange,
  applyVoucher,
  useManagerAccount,
  closeManagerModal,

  checkoutSelectedTable,

  printReceipt,
  getSessionBaseTotal,
} = usePosIndex()
</script>