<template>
  <div class="p-4 bg-gray-50 min-h-screen">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-2xl font-bold text-teal-500">Restaurant POS</h1>
      <div class="flex gap-4">
        <Link href="/dashboard" class="text-sm text-gray-600 hover:underline">
          Dashboard
        </Link>
        <Link href="/cashier/orders" class="text-sm text-gray-600 hover:underline">
          View Orders
        </Link>
        <Link href="/cashier/reports" class="text-sm text-gray-600 hover:underline">
          Reports
        </Link>
      </div>
    </div>
    <div class="flex flex-col lg:flex-row gap-4">
      <!-- Menus Column -->
      <div class="flex-1 bg-white p-4 rounded-lg shadow overflow-y-auto h-[70vh]">
        <!-- Category Tabs -->
        <div class="mb-4">
          <!-- Category buttons -->
          <div class="flex flex-wrap gap-2 mb-2">
            <button
              v-for="cat in categories"
              :key="cat.id"
              @click="selectedCategory = cat.id"
              :class="[
                'px-3 py-1 rounded-full font-medium',
                selectedCategory === cat.id
                  ? 'bg-teal-500 text-white'
                  : 'bg-gray-200 text-gray-700 hover:bg-teal-100'
              ]"
            >
              {{ cat.name }}
            </button>
            <button
              @click="selectedCategory = null"
              :class="[
                'px-3 py-1 rounded-full font-medium',
                selectedCategory === null
                  ? 'bg-teal-500 text-white'
                  : 'bg-gray-200 text-gray-700 hover:bg-teal-100'
              ]"
            >
              All
            </button>
          </div>

          <!-- Search bar -->
          <div class="w-full">
            <input
              type="text"
              v-model="searchQuery"
              placeholder="Search menu..."
              class="w-full px-3 py-2 border rounded"
            />
          </div>
        </div>


        <!-- Menu Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="item in filteredMenus"
            :key="item.id"
            class="border rounded p-2 hover:shadow-lg cursor-pointer"
            @click="addToCart(item)"
          >
            <img
              :src="item.image_path 
                ? `/storage/${item.image_path}` 
                : 'https://via.placeholder.com/150'"
              alt=""
              class="w-full h-20 object-cover rounded"
            />
            <div class="mt-2 text-center">
              <p class="font-medium">{{ item.name }}</p>
              <p class="text-sm text-gray-600">₱{{ parseFloat(item.price).toFixed(2) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Cart Column -->
      <div class="w-full lg:w-1/3 bg-white p-4 rounded-lg shadow flex flex-col h-[70vh]">
        <h2 class="text-xl font-semibold mb-2">Cart</h2>
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
              <button
                class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300"
                @click="decreaseQty(index)"
              >-</button>
              <button
                class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300"
                @click="increaseQty(index)"
              >+</button>
              <button
                class="text-red-500 hover:text-red-700 font-bold"
                @click="removeFromCart(index)"
              >
                ✕
              </button>
            </div>
          </div>
        </div>

        <!-- Discount & Checkout -->
        <div class="mt-4">
          <label class="flex items-center mb-2">
            <input type="checkbox" v-model="isSeniorOrPWD" class="mr-2" />
            Senior / PWD Discount 20%
          </label>

          <p class="text-sm text-gray-600">Subtotal: ₱{{ subtotal.toFixed(2) }}</p>
          <p v-if="discountAmount > 0" class="text-sm text-green-600">
            Discount: -₱{{ discountAmount.toFixed(2) }}
          </p>
          <p class="font-bold mb-2">Total: ₱{{ totalPrice.toFixed(2) }}</p>

          <button
            class="bg-teal-500 text-white px-4 py-2 rounded hover:bg-teal-600 w-full"
            @click="checkout"
          >
            Checkout
          </button>
        </div>
      </div>
    </div>

    <!-- Checkout Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center"
    >
      <div class="bg-white p-6 rounded shadow max-w-lg mx-auto">

        <h2 class="text-xl font-bold mb-4">Order Details</h2>
        <ul>
          <li v-for="item in cart" :key="item.id" class="grid grid-cols-3 gap-2 mb-1">
            <!-- Item name -->
            <span>{{ item.name }}</span>
            <!-- Quantity -->
            <span>{{ item.qty }} pcs</span>
            <!-- Price -->
            <span>₱{{ (item.price * item.qty).toFixed(2) }}</span>
          </li>
        </ul>
        <p class="mt-4 text-sm text-gray-600">Subtotal: ₱{{ subtotal.toFixed(2) }}</p>
        <p v-if="discountAmount > 0" class="text-sm text-green-600">
          Discount: -₱{{ discountAmount.toFixed(2) }}
        </p>
        <p class="font-bold mt-2">Total: ₱{{ totalPrice.toFixed(2) }}</p>

        <div class="mt-4 flex justify-end gap-2">
          <button class="px-4 py-2 bg-gray-300 rounded" @click="closeModal">Close</button>
          <button class="px-4 py-2 bg-teal-500 text-white rounded" @click="printReceipt">
            Print Receipt
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import { Inertia } from '@inertiajs/inertia'

const page = usePage()
const categories = computed(() => page.props.categories ?? [])
const menus = computed(() => page.props.menus ?? [])

const selectedCategory = ref(null)
const cart = ref([])
const isSeniorOrPWD = ref(false)
const showModal = ref(false)
const lastOrderId = ref(null)

const searchQuery = ref('')

const filteredMenus = computed(() => {
  let list = menus.value
  if (selectedCategory.value) {
    list = list.filter(m => m.category_id === selectedCategory.value)
  }
  if (searchQuery.value.trim() !== '') {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(m => m.name.toLowerCase().includes(q))
  }
  return list
})


// Add to cart: merge quantity if same item exists
const addToCart = (item) => {
  const existing = cart.value.find(i => i.id === item.id)
  if (existing) {
    existing.qty += 1
  } else {
    cart.value.push({ ...item, qty: 1 })
  }
}

// Increase/decrease quantity
const increaseQty = (index) => cart.value[index].qty += 1
const decreaseQty = (index) => {
  if (cart.value[index].qty > 1) {
    cart.value[index].qty -= 1
  } else {
    cart.value.splice(index, 1)
  }
}

// Remove item
const removeFromCart = (index) => cart.value.splice(index, 1)

// Checkout modal
const checkout = () => {
  console.log('Checkout cart:', cart.value);
  console.log('Senior/PWD discount:', isSeniorOrPWD.value);
  console.log('discountAmount:', discountAmount.value);
  if (!cart.value.length) return alert('Cart is empty!')
    // showModal.value = true
    // Wrap cart in an object so Laravel sees it as $request->cart
    Inertia.post('/cashier/menus/store', 
      { cart: cart.value,
        total_discount: discountAmount.value
      }, {
      preserveState: true,
      forceFormData: true,
      onSuccess: () => {
        // showModal.value = false
        // Optionally clear cart after success
        cart.value = []
        isSeniorOrPWD.value = false
        // if (page.props.lastOrderId) {
        //   lastOrderId.value = page.props.lastOrderId;
        // }
      },
    })

}
const closeModal = () => (showModal.value = false)
const printReceipt = () => {
  if (!lastOrderId.value) return alert('No recent order to print!');
  window.open(route('cashier.orders.pdf', lastOrderId.value), '_blank');
    // window.open(route('cashier.orders.pdf', 10), '_blank');
}

// Subtotal before discount
const subtotal = computed(() =>
  cart.value.reduce((sum, item) => sum + parseFloat(item.price) * item.qty, 0)
)

// Discount amount (20% if senior/PWD)
const discountAmount = computed(() =>
  isSeniorOrPWD.value ? subtotal.value * 0.2 : 0
)

// Final total after discount
const totalPrice = computed(() =>
  subtotal.value - discountAmount.value
)


</script>

<style scoped>
body {
  font-family: 'Poppins', sans-serif;
}
</style>
