<template>
  <AuthenticatedLayout>
    <div class="p-4 bg-gray-50">
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-black-600">Petty Cash</h1>
      </div>

      <div class="mb-4 flex flex-wrap gap-4 items-center">
        <div class="flex items-center gap-2">
          <label class="text-sm font-medium text-gray-700">Date:</label>
          <input
            type="date"
            v-model="selectedDate"
            @change="filterByDate"
            class="border rounded px-2 py-1 text-sm"
          />
        </div>

        <input
          v-model="search"
          type="text"
          placeholder="Search notes or purpose…"
          class="border rounded px-3 py-1 text-sm w-64"
        />

        <button
          @click="openNewPettyCashModal"
          class="px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600"
        >
          <span class="fa fa-plus"></span>
          Post New Petty Cash
        </button>
      </div>

      <div
        v-if="$page.props.flash.success"
        class="mb-4 p-2 bg-blue-100 text-blue-700 rounded"
      >
        {{ $page.props.flash.success }}
      </div>
      <div
        v-if="$page.props.flash.error"
        class="mb-4 p-2 bg-red-100 text-red-700 rounded"
      >
        {{ $page.props.flash.error }}
      </div>

      <div class="bg-white rounded shadow overflow-hidden">
        <div class="max-h-[60vh] overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-100 sticky top-0 z-10">
              <tr class="text-left">
                <th class="px-4 py-2">#</th>
                <th class="px-4 py-2">Date</th>
                <th class="px-4 py-2">Total Amount</th>
                <th class="px-4 py-2">Used</th>
                <th class="px-4 py-2">Remaining</th>
                <th class="px-4 py-2">Posted By</th>
                <th class="px-4 py-2">Updated By</th>
                <th class="px-4 py-2 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredPettyCashes.length === 0">
                <td colspan="8" class="text-center py-6 text-gray-400">
                  No petty cash found
                </td>
              </tr>

              <tr
                v-for="(pc, index) in filteredPettyCashes"
                :key="pc.id"
                class="border-t hover:bg-gray-50"
              >
                <td class="px-4 py-2 font-medium">{{ index + 1 }}</td>
                <td class="px-4 py-2 font-medium">{{ pc.date }}</td>
                <td class="px-4 py-2">₱{{ Number(pc.total_amount).toFixed(2) }}</td>
                <td class="px-4 py-2">₱{{ Number(pc.amount_used).toFixed(2) }}</td>
                <td class="px-4 py-2 font-semibold">
                  ₱{{ Number(pc.remaining).toFixed(2) }}
                </td>
                <td class="px-4 py-2 font-semibold">{{ pc.posted_by_user?.name }}</td>
                <td class="px-4 py-2 font-semibold">{{ pc.updated_by_user?.name ?? pc.posted_by_user?.name }}</td>
                <td class="px-4 py-2 text-right space-x-2">
                  <button
                    @click="openModal(pc)"
                    class="px-2 py-1 text-xs bg-gray-200 rounded hover:bg-gray-300"
                  >
                    <span class="fa fa-eye"></span>
                    View / Post Usage
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div
        v-if="showNewModal"
        class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center"
      >
        <div class="bg-white rounded shadow-lg w-[520px] p-4 max-h-[80vh] overflow-y-auto">
          <h2 class="text-lg font-bold mb-4">Post New Petty Cash</h2>

          <div class="flex flex-col gap-3">
            <input
              type="date"
              v-model="newPettyCash.date"
              class="border rounded px-2 py-1 text-sm"
            />

            <div class="border rounded p-3 bg-gray-50">
              <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-semibold text-gray-700">
                  Coins / Bills Breakdown
                </p>

                <button
                  type="button"
                  @click="addNewPettyRow"
                  class="px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600"
                >
                  <span class="fa fa-plus mr-1"></span> Add Row
                </button>
              </div>

              <div v-if="newPettyCashRows.length === 0" class="text-xs text-gray-500">
                No rows yet. Click <b>Add Row</b>.
              </div>

              <div v-for="(row, idx) in newPettyCashRows" :key="idx" class="grid grid-cols-12 gap-2 mb-2">
                <div class="col-span-6">
                  <select v-model.number="row.denom" class="border rounded px-2 py-1 text-sm w-full">
                    <option :value="null" disabled>Select denomination</option>
                    <option v-for="d in DENOMS" :key="d" :value="d">₱{{ d }}</option>
                  </select>
                </div>

                <div class="col-span-4">
                  <input
                    type="number"
                    min="0"
                    v-model.number="row.qty"
                    class="border rounded px-2 py-1 text-sm w-full"
                    placeholder="Qty"
                  />
                </div>

                <div class="col-span-2 flex justify-end">
                  <button
                    type="button"
                    @click="removeNewPettyRow(idx)"
                    class="px-2 py-1 text-xs bg-red-100 text-red-600 rounded hover:bg-red-200"
                    title="Remove row"
                  >
                    <span class="fa fa-trash"></span>
                  </button>
                </div>

                <div class="col-span-12 text-xs text-gray-500 -mt-1">
                  Subtotal:
                  <b>₱{{ ((Number(row.denom || 0) * Number(row.qty || 0))).toFixed(2) }}</b>
                </div>
              </div>

              <div class="flex justify-between pt-2 border-t mt-3">
                <span class="text-sm font-medium">Computed Total:</span>
                <span class="text-sm font-bold">₱{{ newPettyCashTotal.toFixed(2) }}</span>
              </div>
            </div>

            <input
              type="text"
              v-model="newPettyCash.notes"
              placeholder="Notes (optional)"
              class="border rounded px-2 py-1 text-sm"
            />

            <div class="flex justify-end gap-2 mt-2">
              <button
                @click="closeNewModal"
                class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300"
              >
                <span class="fa fa-close"></span>
                Cancel
              </button>

              <button
                @click="postNewPettyCash"
                class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600"
              >
                <span class="fa fa-save"></span>
                Post
              </button>
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="showModal"
        class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center"
      >
        <div class="bg-white rounded shadow-lg w-[130vh] p-6 max-h-[85vh] overflow-y-auto">
          <h2 class="text-lg font-bold mb-4">
            Petty Cash - {{ selectedPettyCash?.date }}
          </h2>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-gray-50 p-4 rounded border">
              <h3 class="font-semibold mb-3 text-gray-700">
                <span class="fa fa-calculator mr-2"></span>
                Computation
              </h3>

              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span>Total Amount:</span>
                  <span class="font-medium">
                    ₱{{ Number(selectedPettyCash?.total_amount || 0).toFixed(2) }}
                  </span>
                </div>

                <div class="flex justify-between">
                  <span>Total Used:</span>
                  <span class="font-medium text-red-600">
                    ₱{{ Number(selectedPettyCash?.amount_used || 0).toFixed(2) }}
                  </span>
                </div>

                <div class="flex justify-between border-t pt-2">
                  <span class="font-semibold">Remaining:</span>
                  <span
                    :class="[
                      'font-bold',
                      selectedPettyCash?.remaining > 0 ? 'text-green-600' : 'text-gray-400'
                    ]"
                  >
                    ₱{{ Number(selectedPettyCash?.remaining || 0).toFixed(2) }}
                  </span>
                </div>
              </div>

              <div class="mt-4 pt-4 border-t">
                <h4 class="text-sm font-semibold text-gray-700 mb-2">
                  <span class="fa fa-coins mr-2"></span>
                  Usage Breakdown (Per Coin)
                </h4>

                <div v-if="usageCoinRows.length === 0" class="text-xs text-gray-500">
                  No denomination data found in usage details.
                </div>

                <div v-else class="bg-white rounded border overflow-hidden">
                  <table class="w-full text-xs">
                    <thead class="bg-gray-100">
                      <tr>
                        <th class="px-3 py-2 text-left">Denom</th>
                        <th class="px-3 py-2 text-right">Used Qty</th>
                        <th class="px-3 py-2 text-right">Used Total</th>
                        <th class="px-3 py-2 text-right">Start Qty</th>
                        <th class="px-3 py-2 text-right">Balance Qty</th>
                        <th class="px-3 py-2 text-right">Balance Total</th>
                      </tr>
                    </thead>

                    <tbody>
                      <tr
                        v-for="row in usageCoinRows"
                        :key="row.denom"
                        class="border-t"
                      >
                        <td class="px-3 py-2 font-semibold">₱{{ row.denom }}</td>
                        <td class="px-3 py-2 text-right">{{ row.usedQty }}</td>
                        <td class="px-3 py-2 text-right text-red-600 font-semibold">
                          ₱{{ row.usedTotal.toFixed(2) }}
                        </td>
                        <td class="px-3 py-2 text-right">{{ row.startQty }}</td>
                        <td class="px-3 py-2 text-right font-semibold">{{ row.balanceQty }}</td>
                        <td class="px-3 py-2 text-right font-semibold text-green-700">
                          ₱{{ row.balanceTotal.toFixed(2) }}
                        </td>
                      </tr>
                    </tbody>

                    <tfoot class="bg-gray-50 border-t">
                      <tr>
                        <td class="px-3 py-2 font-bold" colspan="2">Totals</td>
                        <td class="px-3 py-2 text-right font-bold text-red-700">
                          ₱{{ usageCoinsGrandTotal.toFixed(2) }}
                        </td>
                        <td class="px-3 py-2"></td>
                        <td class="px-3 py-2"></td>
                        <td class="px-3 py-2 text-right font-bold text-green-700">
                          ₱{{ balanceCoinsGrandTotal.toFixed(2) }}
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>

                <p class="text-[11px] text-gray-500 mt-2">
                  Note: Balance is computed using (Start Qty - Used Qty). If Start denominations are not saved, Start Qty defaults to 0.
                </p>
              </div>
            </div>

            <div class="border p-4 rounded">
              <h3 class="font-semibold mb-3 text-gray-700">
                <span class="fa fa-plus mr-2"></span>
                Post Usage
              </h3>

              <div class="flex flex-col gap-2">
                <input
                  type="text"
                  v-model="newUsage.purpose"
                  placeholder="Purpose (change, supplies, etc.)"
                  class="border rounded px-2 py-1 text-sm"
                />

                <div class="border rounded p-3 bg-gray-50">
                  <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-semibold text-gray-700">Coins / Bills Used</p>

                    <button
                      type="button"
                      @click="addUsageRow"
                      class="px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600"
                    >
                      <span class="fa fa-plus mr-1"></span> Add Row
                    </button>
                  </div>

                  <div v-if="newUsageRows.length === 0" class="text-xs text-gray-500">
                    No rows yet. Click <b>Add Row</b>.
                  </div>

                  <div v-for="(row, idx) in newUsageRows" :key="idx" class="grid grid-cols-12 gap-2 mb-2">
                    <div class="col-span-6">
                      <select v-model.number="row.denom" class="border rounded px-2 py-1 text-sm w-full">
                        <option :value="null" disabled>Select denomination</option>
                        <option v-for="d in DENOMS" :key="d" :value="d">₱{{ d }}</option>
                      </select>
                    </div>

                    <div class="col-span-4">
                      <input
                        type="number"
                        min="0"
                        v-model.number="row.qty"
                        class="border rounded px-2 py-1 text-sm w-full"
                        placeholder="Qty"
                      />
                    </div>

                    <div class="col-span-2 flex justify-end">
                      <button
                        type="button"
                        @click="removeUsageRow(idx)"
                        class="px-2 py-1 text-xs bg-red-100 text-red-600 rounded hover:bg-red-200"
                        title="Remove row"
                      >
                        <span class="fa fa-trash"></span>
                      </button>
                    </div>

                    <div class="col-span-12 text-xs text-gray-500 -mt-1">
                      Subtotal:
                      <b>₱{{ ((Number(row.denom || 0) * Number(row.qty || 0))).toFixed(2) }}</b>
                    </div>
                  </div>

                  <div class="flex justify-between pt-2 border-t mt-3">
                    <span class="text-sm font-medium">Computed Usage Amount:</span>
                    <span class="text-sm font-bold text-red-600">
                      ₱{{ usageTotal.toFixed(2) }}
                    </span>
                  </div>
                </div>

                <input
                  type="text"
                  v-model="newUsage.notes"
                  placeholder="Notes"
                  class="border rounded px-2 py-1 text-sm"
                />

                <button
                  @click="postUsage"
                  :disabled="!hasRemaining"
                  :class="[
                    'px-3 py-2 text-sm rounded mt-2 transition',
                    hasRemaining
                      ? 'bg-blue-500 text-white hover:bg-blue-600'
                      : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                  ]"
                >
                  <span class="fa fa-plus mr-1"></span>
                  {{ hasRemaining ? 'Post Usage' : 'No Remaining Balance' }}
                </button>
              </div>
            </div>
          </div>

          <div>
            <h3 class="font-semibold mb-2 text-gray-700">
              <span class="fa fa-list mr-2"></span>
              Usage Details
            </h3>

            <table class="min-w-full bg-white border rounded-lg text-sm">
              <thead>
                <tr class="bg-gray-50">
                  <th class="px-4 py-2 border text-left">Purpose</th>
                  <th class="px-4 py-2 border text-left">Amount</th>
                  <th class="px-4 py-2 border text-left">Posted By</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!selectedPettyCash.details || selectedPettyCash.details.length === 0">
                  <td colspan="3" class="text-center py-3 text-gray-400">
                    No usage posted yet
                  </td>
                </tr>

                <tr
                  v-for="detail in selectedPettyCash.details"
                  :key="detail.id"
                  class="hover:bg-gray-50"
                >
                  <td class="px-4 py-2 border">{{ detail.purpose }}</td>
                  <td class="px-4 py-2 border">₱{{ Number(detail.amount).toFixed(2) }}</td>
                  <td class="px-4 py-2 border">{{ detail.posted_by_user?.name ?? '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex justify-end mt-6">
            <button
              @click="closeDetailsModal"
              class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
            >
              <span class="fa fa-close mr-1"></span>
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePettyCashIndex } from '@/Composables/petty_cash/usePettyCashIndex'

const {
  DENOMS,
  showModal,
  showNewModal,
  selectedPettyCash,
  selectedDate,
  search,
  hasRemaining,
  newPettyCash,
  newPettyCashRows,
  newPettyCashTotal,
  newUsage,
  newUsageRows,
  usageTotal,
  usageCoinRows,
  usageCoinsGrandTotal,
  balanceCoinsGrandTotal,
  filteredPettyCashes,
  addNewPettyRow,
  removeNewPettyRow,
  addUsageRow,
  removeUsageRow,
  openModal,
  openNewPettyCashModal,
  closeNewModal,
  closeDetailsModal,
  postNewPettyCash,
  postUsage,
  filterByDate,
} = usePettyCashIndex()
</script>