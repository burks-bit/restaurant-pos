<template>
  <div class="relative p-6 bg-gray-50 min-h-screen font-poppins">

    <!-- Loader -->
    <div v-if="isLoading" class="fixed inset-0 bg-black bg-opacity-50 flex flex-col items-center justify-center z-[9999]">
      <div class="bg-white p-8 rounded-xl shadow-xl flex flex-col items-center">
        <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
        <p class="text-lg font-semibold text-gray-700">Processing Order...</p>
      </div>
    </div>

    <!-- Header -->
    <!-- <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-3">
      <h1 :class="`text-3xl font-bold ${themeColor}-600`">Restaurant POS</h1>
      <OperationLinks />
    </div> -->
    <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-3">
      <div class="flex items-center gap-3">
        <BuildingStorefrontIcon class="w-8 h-8 text-green-600" />
        <h1 :class="`text-3xl font-bold ${themeColor}-600`">
          Restaurant POS
        </h1>
      </div>

      <OperationLinks />
    </div>

    <div class="flex flex-col lg:flex-row gap-6">

      <!-- MENUS -->
      <div class="flex-1 bg-white p-5 rounded-xl shadow-lg overflow-hidden">
        <!-- Categories + Search -->
        <div class="mb-4">
          <div class="flex flex-wrap gap-2 mb-3">
            <button
              v-for="cat in categories"
              :key="cat.id"
              @click="selectedCategory = cat.id"
              :class="[selectedCategory === cat.id ? `bg-${themeColor}-600 text-white shadow` : 'bg-gray-200 text-gray-700 hover:bg-gray-300', 'px-4 py-1 rounded-full font-medium transition']"
            >
              {{ cat.name }}
            </button>

            <button
              @click="selectedCategory = null"
              :class="[selectedCategory === null ? `bg-${themeColor}-600 text-white shadow` : 'bg-gray-200 text-gray-700 hover:bg-gray-300', 'px-4 py-1 rounded-full font-medium transition']"
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
                    :src="item.image_path 
                            ? `/storage/${item.image_path}` 
                            : '/storage/web_images/food.png'"
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

      <!-- CART -->
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
            <input type="checkbox" v-model="isSeniorOrPWD" @change="handleSeniorPWDChange" class="mr-2" />
            Senior/PWD Discount (20%)
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

          <div class="grid grid-cols-2 gap-4 mb-3">
            <!-- Payment Method -->
            <div>
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

            <!-- Cash Amount -->
            <div v-if="paymentMethod === 'Cash'">
              <label class="text-sm font-medium">Cash Amount</label>
              <input
                type="number"
                v-model="cashAmount"
                class="w-full border rounded px-3 py-2 mt-1"
              />
              <p v-if="cashAmount >= totalPrice" class="text-green-600 text-sm mt-1">
                Change: ₱{{ changeAmount.toFixed(2) }}
              </p>
              <p v-else class="text-red-600 text-sm mt-1">
                Insufficient Cash
              </p>
            </div>
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

    <!-- Manager Approval Modal -->
    <div 
      v-if="showManagerDiscountModal" 
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white rounded-lg w-full max-w-md p-6">

        <h2 class="text-xl font-semibold mb-4">Manager Accounts</h2>

        <!-- Manager List -->
        <div class="mb-4 max-h-48 overflow-y-auto border rounded">
          <div 
            v-for="manager in managers" 
            :key="manager.id"
            class="border-b"
          >
            <!-- Manager Select Row -->
            <div 
              class="p-2 cursor-pointer hover:bg-gray-100 flex justify-between items-center"
              :class="{ 'bg-blue-100': selectedManager?.id === manager.id }"
              @click="toggleManager(manager)"
            >
              <span>{{ manager.name }}</span>
              <span v-if="selectedManager?.id === manager.id">▼</span>
            </div>

            <!-- Accordion Password Section -->
            <div 
              v-if="selectedManager?.id === manager.id" 
              class="p-3 bg-gray-50 border-t"
            >
              <label class="text-sm font-medium">Enter Password</label>
              <input
                type="password"
                v-model="managerPassword"
                class="w-full border rounded px-3 py-2 mt-1"
                placeholder="Manager password"
              />

              <p v-if="passwordError" class="text-red-600 text-sm mt-1">
                {{ passwordError }}
              </p>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2">
          <button 
            class="px-4 py-2 bg-gray-300 rounded"
            @click="closeManagerModal"
          >
            Close
          </button>

          <button 
            class="px-4 py-2 bg-blue-600 text-white rounded"
            :disabled="!selectedManager || !managerPassword"
            @click="useManagerAccount"
          >
            Use Account
          </button>
        </div>

      </div>
    </div>

    <!-- TOAST -->
    <div v-if="showToast" class="fixed top-5 right-5 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50">
      {{ toastMessage }}
    </div>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import OperationLinks from '@/Components/NavLinks/OperationLinks.vue'
import { BuildingStorefrontIcon } from '@heroicons/vue/24/solid'
import useRolePrefix from '@/Composables/useRolePrefix'
const { prefix } = useRolePrefix()

const page = usePage()
const categories = computed(() => page.props.categories ?? [])
const menus = computed(() => page.props.menus ?? [])
const managers = computed(() => page.props.managers ?? [])

const table_sessions = ref(page.props.table_sessions ?? [])
const selectedTableSession = ref(null)

const filteredTableSessions = computed(() => 
  table_sessions.value
    .filter(s => 
      s.status === 'open' &&
      !s.cashier_id &&
      (!s.total_amount || s.total_amount === "0.00") &&
      !s.order_id
    )
    .sort((a,b) => a.ref_no.localeCompare(b.ref_no))
)

const selectedCategory = ref(null)
const selectedManager = ref(null)
const cart = ref([])
const isSeniorOrPWD = ref(false)
const searchQuery = ref('')
const themeColor = ref('blue')

const isLoading = ref(false)
const showPrintModal = ref(false)
const showManagerDiscountModal = ref(false)
const lastOrderNo = ref(null)
const showToast = ref(false)
const toastMessage = ref('')

const paymentMethod = ref('Cash')
const cashAmount = ref(0)

const managerPassword = ref('')
const passwordError = ref('')

const managerApprovedDiscount = ref(false)

const handleSeniorPWDChange = (event) => {
  
  if (cart.value.length === 0) {
    alert('Cart is empty! Cannot apply discount.');
    // Uncheck the checkbox if needed
    event.target.checked = false;
    return;
  }
  
  if (event.target.checked) {
    // Always require manager approval
    showManagerDiscountModal.value = true
  } else {
    // If cashier removes checkbox → remove approval
    managerApprovedDiscount.value = false
    isSeniorOrPWD.value = false
  }
}

const closeManagerModal = () => {
  showManagerDiscountModal.value = false
  selectedManager.value = null
  managerPassword.value = ''
  passwordError.value = ''
  managerApprovedDiscount.value = false
  isSeniorOrPWD.value = false
}

const useManagerAccount = async () => {
  if (!selectedManager.value) return

  passwordError.value = ''

  try {
    // 🔐 Verify manager password via backend
    const response = await axios.post(`/${prefix.value}/verify-manager-password`, {
      manager_id: selectedManager.value.id,
      password: managerPassword.value
    })

    if (response.data.success) {
      // toastMessage.value = 'Discount approved by manager!'
      // showToast.value = true
      // SetTimeout(()=>showToast.value=false,3000)

      managerApprovedDiscount.value = true
      isSeniorOrPWD.value = true
      showManagerDiscountModal.value = false
      managerPassword.value = ''

    } else {
      passwordError.value = 'Invalid password.'
    }

  } catch (err) {
    passwordError.value = 'Invalid password.'
  }
}


const toggleManager = (manager) => {
  if (selectedManager.value?.id === manager.id) {
    selectedManager.value = null
    managerPassword.value = ''
    passwordError.value = ''
  } else {
    selectedManager.value = manager
    managerPassword.value = ''
    passwordError.value = ''
  }
}

const subtotal = computed(() =>
  cart.value.reduce((sum, item) => sum + parseFloat(item.price) * item.qty, 0)
)

// const discountAmount = computed(() =>
//   isSeniorOrPWD.value ? subtotal.value * 0.2 : 0
// )
const discountAmount = computed(() =>
  managerApprovedDiscount.value ? subtotal.value * 0.2 : 0
)

const totalPrice = computed(() =>
  subtotal.value - discountAmount.value
)

const changeAmount = computed(() =>
  paymentMethod.value === 'Cash' ? cashAmount.value - totalPrice.value : 0
)

const filteredMenus = computed(() => {
  let list = menus.value
  if (selectedCategory.value)
    list = list.filter(m => m.category_id === selectedCategory.value)
  if (searchQuery.value.trim() !== '')
    list = list.filter(m => m.name.toLowerCase().includes(searchQuery.value.toLowerCase()))
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

const checkout = async () => {
  if (!cart.value.length) {
    toastMessage.value = 'Cart is empty!'
    showToast.value = true
    setTimeout(()=>showToast.value=false,3000)
    return
  }

  if (paymentMethod.value==='Cash' && cashAmount.value<totalPrice.value){
    toastMessage.value = 'Insufficient cash!'
    showToast.value = true
    setTimeout(()=>showToast.value=false,3000)
    return
  }

  isLoading.value = true
  try {
    const response = await axios.post(`/${prefix.value}/orders/store`,{
      cart: cart.value,
      total_discount: discountAmount.value,
      payment_method: paymentMethod.value,
      cash_amount: paymentMethod.value==='Cash'?cashAmount.value:null,
      table_session_id: selectedTableSession.value
    })

    // then reset cart & selections
    cart.value = []
    isSeniorOrPWD.value=false
    cashAmount.value=0
    paymentMethod.value='Cash'
    selectedTableSession.value=''
    managerApprovedDiscount.value = false

    // use order_no for print modal
    lastOrderNo.value = response.data.order_no

    // refresh table sessions first
    const tableResponse = await axios.get(`/${prefix.value}/pos/table-sessions`)
    table_sessions.value = tableResponse.data.table_sessions

    isLoading.value = false
    
    toastMessage.value = response.data.message
    showToast.value = true
    showPrintModal.value = true
    setTimeout(()=>showToast.value=false,3000)

  } catch(err){
    toastMessage.value = err.response?.data?.message ?? 'Something went wrong!'
    showToast.value = true
    setTimeout(()=>showToast.value=false,3000)
  } finally {
    isLoading.value = false
  }
}

const printReceipt = () => {
  window.open(`/${prefix.value}/orders/${lastOrderNo.value}/pdf`, '_blank')
  showPrintModal.value = false
}
</script>
