<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50 min-h-screen">
        <h1 class="text-2xl font-semibold mb-4 text-gray-900">
          Gift Voucher Management
        </h1>

        <div class="flex justify-between mb-4 flex-wrap gap-2">
          <input
            v-model="search"
            type="text"
            placeholder="Search vouchers…"
            class="border rounded px-3 py-1 text-sm w-64 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
          />

          <div class="flex gap-2 flex-wrap">
            <button
              class="flex items-center gap-1 text-sm bg-blue-600 text-white px-2.5 py-1.5 rounded hover:bg-blue-700"
              @click="createVoucher"
            >
              <i class="fa fa-plus"></i>
              Add Voucher
            </button>

            <button
              class="flex items-center gap-1 text-sm bg-green-600 text-white px-2.5 py-1.5 rounded hover:bg-green-700"
              @click="printAllVouchers"
            >
              <i class="fa fa-print"></i>
              Print All
            </button>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-auto max-h-[60vh]">
          <table class="min-w-full table-auto border-collapse">
            <thead class="sticky top-0 bg-gray-100 z-10">
              <tr class="text-gray-900 text-left">
                <th class="px-2 py-1 border">ID</th>
                <th class="px-2 py-1 border">Control No.</th>
                <th class="px-2 py-1 border">Type</th>
                <th class="px-2 py-1 border">Status</th>
                <th class="px-2 py-1 border">Used By Order</th>
                <th class="px-2 py-1 border">Valid Until</th>
                <th class="px-2 py-1 border">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="voucher in filteredVouchers"
                :key="voucher.id"
                class="hover:bg-gray-50"
              >
                <td class="px-2 py-1 border">{{ voucher.id }}</td>
                <td class="px-2 py-1 border">{{ voucher.control_no }}</td>
                <td class="px-2 py-1 border">{{ voucher.type }}</td>
                <td class="px-2 py-1 border capitalize">{{ voucher.status }}</td>
                <td class="px-2 py-1 border">
                  {{ voucher.order?.order_no ?? '-' }}
                </td>
                <td class="px-2 py-1 border capitalize">{{ formatDate(voucher.validity) }}</td>
                <td class="px-2 py-1 border flex gap-2">
                  <button
                    class="flex items-center gap-1 px-1.5 py-0.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                    @click="editVoucher(voucher)"
                  >
                    <i class="fa fa-edit"></i>
                    Edit
                  </button>

                  <button
                    class="flex items-center gap-1 px-1.5 py-0.5 text-sm bg-red-600 text-white rounded hover:bg-red-700"
                    @click="deleteVoucher(voucher)"
                  >
                    <i class="fa fa-trash"></i>
                    Delete
                  </button>

                  <button
                    class="flex items-center gap-1 px-1.5 py-0.5 text-sm bg-green-600 text-white rounded hover:bg-green-700"
                    @click="printVoucher(voucher)"
                  >
                    <i class="fa fa-print"></i>
                    Print
                  </button>
                </td>
              </tr>

              <tr v-if="filteredVouchers.length === 0">
                <td colspan="7" class="text-center py-6 text-gray-400">
                  No vouchers found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-if="showAddModal"
          class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
          <div class="bg-white rounded-lg w-full max-w-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <i class="fa fa-plus"></i>
              Add New Voucher
            </h2>

            <form class="space-y-4" @submit.prevent="saveVoucher">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Control No.</label>
                <input
                  type="text"
                  v-model="newForm.control_no"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Valid Until</label>
                <input
                  type="date"
                  v-model="newForm.validity"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select
                  v-model="newForm.type"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                  required
                >
                  <option value="20%">20%</option>
                  <option value="30%">30%</option>
                  <option value="40%">40%</option>
                  <option value="50%">50%</option>
                  <option value="Free Meal">Free Meal</option>
                </select>
              </div>

              <div class="flex justify-end gap-2 mt-4">
                <button
                  type="button"
                  class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
                  @click="closeAddModal"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="flex items-center gap-1 px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                  <i class="fa fa-save"></i>
                  Save
                </button>
              </div>
            </form>
          </div>
        </div>

        <div
          v-if="showEditModal"
          class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
          <div class="bg-white rounded-lg w-full max-w-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <i class="fa fa-edit"></i>
              Edit Voucher
            </h2>

            <form class="space-y-4" @submit.prevent="updateVoucher">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Control No.</label>
                <input
                  type="text"
                  v-model="editForm.control_no"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Valid Until</label>
                <input
                  type="date"
                  v-model="editForm.validity"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select
                  v-model="editForm.type"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                  required
                >
                  <option value="20%">20%</option>
                  <option value="30%">30%</option>
                  <option value="40%">40%</option>
                  <option value="50%">50%</option>
                  <option value="Free Meal">Free Meal</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select
                  v-model="editForm.status"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                  required
                >
                  <option value="available">Available</option>
                  <option value="used">Used</option>
                  <option value="expired">Expired</option>
                </select>
              </div>

              <div class="flex justify-end gap-2 mt-4">
                <button
                  type="button"
                  class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
                  @click="closeEditModal"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="flex items-center gap-1 px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                  <i class="fa fa-save"></i>
                  Save
                </button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useGiftVouchersIndex } from '@/Composables/gift_vouchers/useGiftVouchersIndex'

const {
  search,
  showAddModal,
  showEditModal,
  newForm,
  editForm,
  filteredVouchers,
  createVoucher,
  closeAddModal,
  editVoucher,
  closeEditModal,
  saveVoucher,
  updateVoucher,
  deleteVoucher,
  printVoucher,
  printAllVouchers,
  formatDate,
} = useGiftVouchersIndex()
</script>

<style scoped>
body {
  font-family: 'Poppins', sans-serif;
}
</style>