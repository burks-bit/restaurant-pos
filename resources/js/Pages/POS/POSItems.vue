<template>
    <AuthenticatedLayout>
        <div class="relative p-4 bg-gray-50 font-poppins">

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

            <!-- HEADER -->
            <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-3">
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-bold text-black-600">Post Add-ons</h1>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row gap-6">
                
                <!-- ITEMS COLUMN -->
                <div class="flex-1 bg-white p-5 rounded-xl shadow-lg overflow-hidden">
                    <div class="mb-4">
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Search item..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1"
                            :class="`focus:ring-${themeColor}-500 focus:border-${themeColor}-500`"
                        />
                    </div>

                    <div class="overflow-y-auto max-h-[40vh] border rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Item</th>
                                    <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Unit</th>
                                    <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Stock</th>
                                    <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Price</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200">
                                <tr
                                    v-for="item in filteredOrderableItems"
                                    :key="item.id"
                                    class="hover:bg-gray-50 cursor-pointer"
                                    @click="addToCart(item)"
                                >
                                    <td class="px-3 py-2 text-gray-800 font-medium">
                                        {{ item.name }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-600">
                                        {{ item.unit }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-600">
                                        {{ parseFloat(item.current_quantity).toFixed(2) }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-600">
                                        ₱{{ parseFloat(item.unit_price).toFixed(2) }}
                                    </td>
                                </tr>

                                <tr v-if="filteredOrderableItems.length === 0">
                                    <td colspan="4" class="px-3 py-2 text-gray-500 text-center">
                                        No orderable items found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- CART COLUMN -->
                <div class="w-full lg:w-1/3 sticky top-6 bg-white p-5 rounded-xl shadow-lg flex flex-col h-[72vh]">
                    <h2 class="text-xl font-semibold mb-4 text-gray-600">Cart</h2>

                    <div class="flex-1 overflow-y-auto">
                        <div
                            v-for="(item, index) in cart"
                            :key="`${item.id}-${index}`"
                            class="flex justify-between items-center border-b py-2"
                        >
                            <div>
                                <p class="font-medium">{{ item.name }}</p>
                                <p class="text-sm text-gray-500">
                                    ₱{{ (parseFloat(item.unit_price) * item.qty).toFixed(2) }}
                                    <span class="text-xs text-gray-400">({{ item.qty }} pcs)</span>
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <button class="px-2 py-1 bg-gray-200 rounded-lg" @click.stop="decreaseQty(index)">-</button>
                                <button class="px-2 py-1 bg-gray-200 rounded-lg" @click.stop="increaseQty(index)">+</button>
                                <button class="text-red-500 font-bold" @click.stop="removeFromCart(index)">✕</button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label v-if="is_discount_allowed === 1" class="flex items-center mb-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="isSeniorOrPWD"
                                class="mr-2"
                            />
                            Senior / PWD Discount 20%
                        </label>

                        <p class="text-sm text-gray-600">Subtotal: ₱{{ subtotal.toFixed(2) }}</p>
                        <p v-if="discountAmount > 0" class="text-sm text-green-600">
                            Discount: -₱{{ discountAmount.toFixed(2) }}
                        </p>
                        <p class="font-bold mb-2 text-lg">
                            Total: ₱{{ totalPrice.toFixed(2) }}
                        </p>

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
                            @click="checkout"
                            class="mt-4 w-full bg-gray-800 text-white px-4 py-2 rounded"
                        >
                            <i class="fa fa-check-circle" aria-hidden="true"></i> Checkout
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
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePosAddons } from '@/Composables/pos/usePosAddons'

const {
  is_discount_allowed,

  cart,

  isSeniorOrPWD,
  searchQuery,
  themeColor,

  isLoading,

  showToast,
  toastMessage,

  showPrintModal,
  lastOrderNo,

  paymentMethod,
  cashAmount,

  changeAmount,
  filteredOrderableItems,
  addToCart,
  increaseQty,
  decreaseQty,
  removeFromCart,

  subtotal,
  discountAmount,
  totalPrice,
  checkout,
  printReceipt,
} = usePosAddons()
</script>

<style scoped>
body {
    font-family: 'Poppins', sans-serif;
}
</style>