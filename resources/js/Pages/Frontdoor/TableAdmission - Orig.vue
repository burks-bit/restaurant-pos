<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50 min-h-screen">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900">Table Admission</h1>
            <p class="text-sm text-gray-500">
              Admit single customers or grouped guests
            </p>
          </div>

          <div class="flex flex-wrap gap-2">
            <button
              class="px-4 py-2 rounded-lg text-sm font-medium"
              :class="admissionMode === 'single'
                ? 'bg-green-600 text-white'
                : 'bg-white border text-gray-700 hover:bg-gray-50'"
              @click="setAdmissionMode('single')"
            >
              Single Admission
            </button>

            <button
              class="px-4 py-2 rounded-lg text-sm font-medium"
              :class="admissionMode === 'group'
                ? 'bg-blue-600 text-white'
                : 'bg-white border text-gray-700 hover:bg-gray-50'"
              @click="setAdmissionMode('group')"
            >
              Group Admission
            </button>
          </div>
        </div>

        <!-- Search -->
        <div class="mb-5">
          <input
            v-model="search"
            type="text"
            placeholder="Search table..."
            class="w-full md:w-80 border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600"
          />
        </div>

        <!-- GROUP MODE FORM -->
        <div
          v-if="admissionMode === 'group'"
          class="bg-white rounded-xl shadow border border-gray-200 p-5 mb-6"
        >
          <div class="mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Group Admission Form</h2>
            <p class="text-sm text-gray-500">
              Enter the quantity per pricing rule instead of one total pax.
            </p>
          </div>

          <form class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4" @submit.prevent>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Group / Customer Name</label>
              <input
                v-model="groupForm.customer_name"
                type="text"
                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                placeholder="e.g. Santos Family"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Selected Parent Table</label>
              <select
                v-model.number="groupForm.parent_table_id"
                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
              >
                <option :value="null">Choose table</option>
                <option
                  v-for="table in filteredTables"
                  :key="table.id"
                  :value="Number(table.id)"
                >
                  {{ table.name }} ({{ availableChildrenCapacity(table) }} capacity available)
                </option>
              </select>
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
              <input
                v-model="groupForm.remarks"
                type="text"
                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                placeholder="Optional"
              />
            </div>
          </form>

          <!-- Pax Rules -->
          <div class="mt-5 border rounded-xl overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b">
              <h3 class="text-sm font-semibold text-gray-800">Pax Entry by Rule</h3>
            </div>

            <div class="divide-y">
              <div
                v-for="rule in normalizedHeadPrices"
                :key="rule.id"
                class="grid grid-cols-1 md:grid-cols-12 gap-3 px-4 py-4 items-center"
              >
                <div class="md:col-span-5">
                  <div class="font-medium text-sm text-gray-900">{{ rule.label }}</div>
                  <div class="text-xs text-gray-500 mt-1">
                    {{ getRuleDescription(rule) }}
                  </div>
                </div>

                <div class="md:col-span-3">
                  <div class="text-sm font-medium text-gray-700">
                    ₱{{ formatPrice(rule.price) }} / pax
                  </div>
                </div>

                <div class="md:col-span-2">
                  <label class="block text-xs text-gray-500 mb-1">Qty</label>
                  <input
                    v-model.number="groupForm.rule_counts[rule.id]"
                    type="number"
                    min="0"
                    class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                  />
                </div>

                <div class="md:col-span-2">
                  <div class="text-xs text-gray-500">Line Total</div>
                  <div class="text-sm font-semibold text-gray-900">
                    ₱{{ formatPrice(getGroupLineTotal(rule.id, rule.price)) }}
                  </div>
                </div>
              </div>
            </div>

            <div class="px-4 py-4 bg-gray-50 border-t">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-lg bg-white border px-4 py-3">
                  <div class="text-xs text-gray-500">Total Pax</div>
                  <div class="text-lg font-semibold text-gray-900">{{ groupTotalPax }}</div>
                </div>

                <div class="rounded-lg bg-white border px-4 py-3">
                  <div class="text-xs text-gray-500">Estimated Amount</div>
                  <div class="text-lg font-semibold text-blue-700">
                    ₱{{ formatPrice(groupEstimatedTotal) }}
                  </div>
                </div>

                <div class="rounded-lg bg-white border px-4 py-3">
                  <div class="text-xs text-gray-500">Available Capacity</div>
                  <div class="text-lg font-semibold text-gray-900">
                    {{ selectedGroupParentAvailableSlots }}
                  </div>
                </div>
              </div>

              <p
                v-if="groupForm.parent_table_id && groupTotalPax > selectedGroupParentAvailableSlots"
                class="text-sm text-red-600 mt-3"
              >
                Entered pax exceeds available capacity for the selected parent table.
              </p>
            </div>
          </div>

          <div class="mt-4 flex flex-wrap gap-2">
            <button
              type="button"
              class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700"
              @click="submitGroupAdmission"
            >
              Proceed Group Admission
            </button>

            <button
              type="button"
              class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm hover:bg-gray-300"
              @click="resetGroupForm"
            >
              Reset
            </button>
          </div>
        </div>

        <!-- TABLE CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
          <div
            v-for="table in filteredTables"
            :key="table.id"
            class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden"
          >
            <div class="px-4 py-3 border-b bg-gray-50">
              <div class="flex items-center justify-between">
                <div>
                  <h2 class="text-lg font-semibold text-gray-900">{{ table.name }}</h2>
                  <p class="text-xs text-gray-500">
                    {{ occupiedChildrenCount(table) }} occupied / {{ totalChildrenCount(table) }} total slots
                  </p>
                </div>

                <span
                  :class="getParentStatusClass(table)"
                  class="px-2.5 py-1 rounded-full text-xs font-medium"
                >
                  {{ getParentStatus(table) }}
                </span>
              </div>
            </div>

            <div
              v-if="admissionMode === 'group'"
              class="px-4 pt-4"
            >
              <button
                type="button"
                class="w-full rounded-lg border border-blue-200 bg-blue-50 text-blue-700 px-4 py-2 text-sm font-medium hover:bg-blue-100"
                @click="selectParentTable(table)"
              >
                Select {{ table.name }} for Group
              </button>
            </div>

            <div class="p-4">
              <div class="grid grid-cols-2 gap-3">
                <button
                  v-for="child in table.children"
                  :key="child.id"
                  type="button"
                  class="rounded-xl border p-4 text-left transition"
                  :class="getSlotCardClass(child)"
                  :disabled="admissionMode === 'group' || isChildOccupied(child)"
                  @click="openAssignModal(child, table)"
                >
                  <div class="flex items-start justify-between gap-2">
                    <div>
                      <div class="font-semibold text-sm text-gray-900">{{ child.name }}</div>
                      <div class="text-xs mt-1 text-gray-500">
                        Capacity: {{ child.capacity ?? 1 }}
                      </div>
                      <div class="text-xs text-gray-500">
                        Guest Count: {{ child.guest_count ?? 0 }}
                      </div>
                      <div
                        v-if="isChildOccupied(child) && child.customer_name"
                        class="text-xs text-gray-600 mt-1"
                      >
                        Customer: {{ child.customer_name }}
                      </div>
                      <div
                        v-if="isChildOccupied(child) && child.ref_no"
                        class="text-xs text-gray-400"
                      >
                        Ref #: {{ child.ref_no }}
                      </div>
                    </div>

                    <span
                      class="text-[11px] px-2 py-1 rounded-full font-medium"
                      :class="isChildOccupied(child)
                        ? 'bg-red-100 text-red-700'
                        : 'bg-green-100 text-green-700'"
                    >
                      {{ isChildOccupied(child) ? 'occupied' : 'vacant' }}
                    </span>
                  </div>

                  <div class="mt-3 text-xs font-medium text-gray-700">
                    {{
                      admissionMode === 'group'
                        ? 'Disabled in group mode'
                        : isChildOccupied(child)
                          ? 'Unavailable'
                          : 'Click to assign'
                    }}
                  </div>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div
          v-if="filteredTables.length === 0"
          class="bg-white rounded-xl shadow border border-dashed border-gray-300 py-12 text-center text-gray-400 mt-6"
        >
          No tables found
        </div>

        <!-- SINGLE ASSIGN MODAL -->
        <div
          v-if="showAssignModal"
          class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 px-4"
        >
          <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <div class="mb-4">
              <h2 class="text-xl font-semibold text-gray-900">Single Admission</h2>
              <p class="text-sm text-gray-500 mt-1">
                {{ selectedParent?.name }} / {{ selectedSlot?.name }}
              </p>
            </div>

            <form class="space-y-4" @submit.prevent="submitSingleAdmission">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Customer Name</label>
                <input
                  v-model="singleForm.customer_name"
                  type="text"
                  class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600"
                  placeholder="Optional"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Applicable Rule</label>
                <select
                  v-model.number="singleForm.rule_id"
                  class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600"
                >
                  <option :value="null">Select pricing rule</option>
                  <option
                    v-for="rule in normalizedHeadPrices"
                    :key="rule.id"
                    :value="rule.id"
                  >
                    {{ rule.label }} - ₱{{ formatPrice(rule.price) }}
                  </option>
                </select>

                <p class="text-xs text-gray-500 mt-1" v-if="selectedSingleRule">
                  {{ getRuleDescription(selectedSingleRule) }}
                </p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pax</label>
                <input
                  v-model="singleForm.pax"
                  type="number"
                  min="1"
                  max="1"
                  class="w-full border rounded-lg px-3 py-2 text-sm bg-gray-100"
                  readonly
                />
              </div>

              <div class="rounded-lg border bg-gray-50 px-4 py-3">
                <div class="text-xs text-gray-500">Amount</div>
                <div class="text-lg font-semibold text-green-700">
                  ₱{{ formatPrice(singleSelectedPrice) }}
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                <textarea
                  v-model="singleForm.remarks"
                  rows="3"
                  class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600"
                  placeholder="Optional remarks"
                ></textarea>
              </div>

              <div class="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300"
                  @click="closeAssignModal"
                >
                  Cancel
                </button>

                <button
                  type="submit"
                  class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700"
                >
                  Assign
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
import { useTableAdmission } from '@/Composables/tables/useTableAdmission'

const {
  search,
  admissionMode,
  showAssignModal,
  selectedSlot,
  selectedParent,
  singleForm,
  groupForm,
  filteredTables,
  normalizedHeadPrices,
  groupTotalPax,
  groupEstimatedTotal,
  selectedGroupParentAvailableSlots,
  selectedSingleRule,
  singleSelectedPrice,
  setAdmissionMode,
  totalChildrenCount,
  occupiedChildrenCount,
  availableChildrenCapacity,
  getParentStatus,
  getParentStatusClass,
  getSlotCardClass,
  getRuleDescription,
  getGroupLineTotal,
  formatPrice,
  isChildOccupied,
  openAssignModal,
  closeAssignModal,
  submitSingleAdmission,
  submitGroupAdmission,
  selectParentTable,
  resetGroupForm,
} = useTableAdmission()
</script>