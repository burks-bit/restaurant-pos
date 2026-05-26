<template>
  <div class="relative p-6 bg-gray-50 min-h-screen font-poppins">

    <!-- FULL PAGE LOADER -->
    <div
      v-if="isLoading"
      class="fixed inset-0 bg-black bg-opacity-50 flex flex-col items-center justify-center z-[9999]"
    >
      <div class="bg-white p-8 rounded-xl shadow-xl flex flex-col items-center">
        <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
        <p class="text-lg font-semibold text-gray-700">Processing Order...</p>
      </div>
    </div>

    <!-- Header -->
    <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-3">
      <h1 :class="`text-3xl font-bold ${themeColor}-600`">Restaurant POS</h1>
      <OperationLinks />
    </div>

    <div class="flex flex-col lg:flex-row gap-6">

      <!-- MENUS COLUMN -->
      <div class="flex-1 bg-white p-5 rounded-xl shadow-lg overflow-hidden">

        <div class="mb-4">
          <div class="flex flex-wrap gap-2 mb-3">
            <button
              v-for="cat in categories"
              :key="cat.id"
              @click="selectedCategory = cat.id"
              :class="[
                'px-4 py-1 rounded-full font-medium transition',
                selectedCategory === cat.id
                  ? `bg-${themeColor}-600 text-white shadow`
                  : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
              ]"
            >
              {{ cat.name }}
            </button>

            <button
              @click="selectedCategory = null"
              :class="[
                'px-4 py-1 rounded-full font-medium transition',
                selectedCategory === null
                  ? `bg-${themeColor}-600 text-white shadow`
                  : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
              ]"
            >
              All
            </button>
          </div>

          <input
            type="text"
            v-model="searchQuery"
            placeholder="Search menu..."
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2"
            :class="`focus:ring-${themeColor}-500 focus:border-${themeColor}-500`"
          />
        </div>

        <div class="overflow-y-auto max-h-[60vh] border rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100 sticky top-0">
              <tr>
                <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Menu</th>
                <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Price</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">
              <tr
                v-for="item in filteredMenus"
                :key="item.id"
                class="hover:bg-gray-50 cursor-pointer"
                @click="addToCart(item)"
              >
                <td class="px-3 py-2 flex items-center gap-3">
                  <img
                    :src="item.image_path ? `/storage/${item.image_path}` : 'https://via.placeholder.com/50'"
                    class="w-12 h-12 object-cover rounded"
                  />
                  <span class="text-gray-800 font-medium">{{ item.name }}</span>
                </td>
                <td class="px-3 py-2 text-gray-600">
                  ₱{{ parseFloat(item.price).toFixed(2) }}
                </td>
              </tr>

              <tr v-if="filteredMenus.length === 0">
                <td colspan="2" class="px-3 py-2 text-gray-500 text-center">
                  No items found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- CART COLUMN -->
      <div class="w-full lg:w-1/3 sticky top-6 bg-white p-5 rounded-xl shadow-lg flex flex-col h-[86vh]">

        <h2 :class="`text-xl font-semibold mb-4 ${themeColor}-600`">Cart</h2>

        <div class="flex-1 overflow-y-auto">
          <div
            v-for="(item, index) in cart"
            :key="item.id"
            class="flex justify-between items-center border-b py-2"
          >
            <div>
              <p class="font-medium">{{ item.name }}</p>
              <p class="text-sm text-gray-500">
                ₱{{ (item.price * item.qty).toFixed(2) }}
                <span class="text-xs text-gray-400">({{ item.qty }} pcs)</span>
              </p>
            </div>

            <div class="flex items-center gap-2">
              <button class="px-2 py-1 bg-gray-200 rounded-lg" @click="decreaseQty(index)">-</button>
              <button class="px-2 py-1 bg-gray-200 rounded-lg" @click="increaseQty(index)">+</button>
              <button class="text-red-500 font-bold" @click="removeFromCart(index)">✕</button>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <label class="flex items-center mb-2">
            <input type="checkbox" v-model="isSeniorOrPWD" class="mr-2" />
            Senior / PWD Discount 20%
          </label>

          <p class="text-sm text-gray-600">Subtotal: ₱{{ subtotal.toFixed(2) }}</p>
          <p v-if="discountAmount > 0" class="text-sm text-green-600">
            Discount: -₱{{ discountAmount.toFixed(2) }}
          </p>
          <p class="font-bold mb-2 text-lg">
            Total: ₱{{ totalPrice.toFixed(2) }}
          </p>

          <!-- Table Number -->
          <div class="mb-3">
            <label class="text-sm font-medium">Table Number</label>
              <select 
                v-model="selectedTableSession" 
                class="w-full border rounded px-3 py-2 mt-1 text-sm"
              >
                <option value="" disabled>Select a table session</option>
                <option 
                  v-for="session in filteredTableSessions" 
                  :key="session.id" 
                  :value="session.id"
                >
                  {{ session.table.name }} ({{ session.ref_no }}) ({{ session.status }}) ({{ session.pax }} pax)
                </option>
              </select>

          </div>

          <!-- Payment Method -->
          <div class="mb-3">
            <label class="text-sm font-medium">Payment Method</label>
            <select
              v-model="paymentMethod"
              class="w-full border rounded px-3 py-2 mt-1"
            >
              <option value="Cash">Cash</option>
              <option value="GCash">GCash</option>
              <option value="Card">Card</option>
            </select>
          </div>

          <!-- Cash Input (Only for Cash) -->
          <div v-if="paymentMethod === 'Cash'" class="mb-3">
            <label class="text-sm font-medium">Cash Amount</label>
            <input
              type="number"
              v-model="cashAmount"
              class="w-full border rounded px-3 py-2 mt-1"
            />
            <p
              v-if="cashAmount >= totalPrice"
              class="text-green-600 text-sm mt-1"
            >
              Change: ₱{{ changeAmount.toFixed(2) }}
            </p>
            <p
              v-else
              class="text-red-600 text-sm mt-1"
            >
              Insufficient Cash
            </p>
          </div>


          <button
            class="bg-blue-600 text-white px-4 py-2 rounded-lg w-full hover:bg-blue-700 transition"
            @click="checkout"
            :disabled="isLoading"
          >
            Checkout
          </button>
        </div>
      </div>
    </div>

    <!-- PRINT MODAL -->
    <div
      v-if="showPrintModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white rounded-lg w-full max-w-md p-6">
        <h2 class="text-xl font-semibold mb-4">Order Successfully Placed 🎉</h2>

        <p class="mb-4">
          Order No: <strong>{{ lastOrderNo }}</strong>
        </p>

        <div class="flex justify-end gap-2">
          <button class="px-4 py-2 bg-gray-300 rounded" @click="showPrintModal = false">
            Close
          </button>

          <button
            class="px-4 py-2 bg-blue-600 text-white rounded"
            @click="printReceipt"
          >
            Print Receipt
          </button>
        </div>
      </div>
    </div>

    <!-- TOAST -->
    <div
      v-if="showToast"
      class="fixed top-5 right-5 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50"
    >
      {{ toastMessage }}
    </div>

  </div>
</template>


<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import OperationLinks from '@/Components/NavLinks/OperationLinks.vue'
import axios from 'axios'

const page = usePage()
const categories = computed(() => page.props.categories ?? [])
const table_sessions = computed(() => page.props.table_sessions ?? [])
const menus = computed(() => page.props.menus ?? [])

console.log(table_sessions)
const selectedTableSession = ref(null)

const filteredTableSessions = computed(() => {
  return table_sessions.value
    .filter(s => 
      s.status === 'open' &&
      !s.cashier_id &&
      (!s.total_amount || s.total_amount === "0.00") &&
      !s.order_id
    )
    .sort((a, b) => a.ref_no.localeCompare(b.ref_no))
})

const selectedCategory = ref(null)
const cart = ref([])
const isSeniorOrPWD = ref(false)
const searchQuery = ref('')
const themeColor = ref('blue')

// Loader
const isLoading = ref(false)

// Toast
const showToast = ref(false)
const toastMessage = ref('')

// Print
const showPrintModal = ref(false)
const lastOrderId = ref(null)
const lastOrderNo = ref(null)

const paymentMethod = ref('Cash')
const cashAmount = ref(0)
const tableNumber = ref('')

const changeAmount = computed(() => {
  if (paymentMethod.value !== 'Cash') return 0
  return cashAmount.value - totalPrice.value
})

const filteredMenus = computed(() => {
  let list = menus.value
  if (selectedCategory.value)
    list = list.filter(m => m.category_id === selectedCategory.value)

  if (searchQuery.value.trim() !== '') {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(m => m.name.toLowerCase().includes(q))
  }

  return list
})

const addToCart = (item) => {
  const existing = cart.value.find(i => i.id === item.id)
  if (existing) existing.qty += 1
  else cart.value.push({ ...item, qty: 1 })
}

const increaseQty = (index) => cart.value[index].qty += 1
const decreaseQty = (index) => {
  if (cart.value[index].qty > 1) cart.value[index].qty -= 1
  else cart.value.splice(index, 1)
}
const removeFromCart = (index) => cart.value.splice(index, 1)

const subtotal = computed(() =>
  cart.value.reduce((sum, item) => sum + parseFloat(item.price) * item.qty, 0)
)

const discountAmount = computed(() =>
  isSeniorOrPWD.value ? subtotal.value * 0.2 : 0
)

const totalPrice = computed(() =>
  subtotal.value - discountAmount.value
)

const checkout = async () => {
  console.log('checkout');
  console.log('table session id: ' +selectedTableSession.value)
  if (!cart.value.length) {
    toastMessage.value = 'Cart is empty!'
    showToast.value = true
    setTimeout(() => showToast.value = false, 3000)
    return
  }

  if (paymentMethod.value === 'Cash' && cashAmount.value < totalPrice.value) {
    toastMessage.value = 'Insufficient cash amount!'
    showToast.value = true
    setTimeout(() => showToast.value = false, 3000)
    return
  }

  isLoading.value = true

  try {
    const response = await axios.post('/cashier/menus/store', {
      cart: cart.value,
      total_discount: discountAmount.value,
      payment_method: paymentMethod.value,
      cash_amount: paymentMethod.value === 'Cash' ? cashAmount.value : null,
      table_session_id: selectedTableSession.value
    })

    lastOrderId.value = response.data.order_id
    lastOrderNo.value = response.data.order_no

    toastMessage.value = response.data.message
    showToast.value = true
    setTimeout(() => showToast.value = false, 3000)

    showPrintModal.value = true

    // reset
    cart.value = []
    isSeniorOrPWD.value = false
    cashAmount.value = 0
    tableNumber.value = ''
    paymentMethod.value = 'Cash'
    selectedTableSession.value = ''

    Inertia.reload({ only: ['table_sessions'] })

  } catch (error) {
    toastMessage.value =
      error.response?.data?.message ?? 'Something went wrong!'
    showToast.value = true
    setTimeout(() => showToast.value = false, 3000)
  } finally {
    isLoading.value = false
  }
}

const printReceipt = () => {
  window.open(`/cashier/orders/${lastOrderId.value}/pdf`, '_blank')
  showPrintModal.value = false
}
</script>



<style scoped>
body { font-family: 'Poppins', sans-serif; }
</style>
