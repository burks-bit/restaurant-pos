<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <div class="min-h-screen bg-gray-50 p-4">
        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div class="flex flex-col gap-3 md:flex-row md:items-center md:gap-6">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Customer & Table Registration</h1>
              <p class="text-sm text-gray-500">
                Admit a customer or group to a table.
              </p>
            </div>
          </div>

          <Link
            :href="route('frontdoor.table_occupancies')"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm text-white transition hover:bg-blue-700"
          >
            <i class="fa fa-arrow-left"></i>
            Back to Table Monitoring
          </Link>
        </div>

        <div
          v-if="incomingReservation"
          class="mb-4 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800 flex items-center gap-2"
        >
          <i class="fa fa-calendar-check-o"></i>
          Loading reservation for <strong>{{ incomingReservation.name }}</strong>
          — {{ incomingReservation.pax }} pax · scheduled
          {{ formatDateTime(incomingReservation.reservation_datetime) }}
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
          <!-- Tables -->
          <div class="xl:col-span-2">
            <div class="rounded-2xl border bg-white shadow-sm">
              <div class="border-b px-5 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Tables</h2>
                <p class="text-sm text-gray-500">Choose a table for admission.</p>
              </div>

              <div class="p-5">
                <!-- Search Input -->
                <div class="mb-4">
                  <label class="mb-1 block text-sm font-medium text-gray-700">
                    Search Table
                  </label>
                  <input
                    v-model="tableSearch"
                    type="text"
                    class="w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Search by table name..."
                  />
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                  <button
                    v-for="table in filteredTables"
                    :key="table.id"
                    type="button"
                    @click="selectTable(table)"
                    class="rounded-2xl border px-4 py-4 text-left transition-all disabled:cursor-not-allowed disabled:opacity-70"
                    :disabled="!canSelectTable(table)"
                    :class="[
                      selectedTable?.id === table.id
                        ? 'border-blue-600 bg-blue-50 ring-2 ring-blue-200'
                        : 'border-gray-200 bg-white hover:border-blue-300 hover:bg-gray-50',
                      getTableStats(table.id).isFullyOccupied
                        ? 'border-red-300 bg-red-50 hover:border-red-300 hover:bg-red-50'
                        : '',
                    ]"
                  >
                    <div class="flex h-full flex-col">
                      <!-- Header -->
                      <div class="flex items-start justify-between gap-3 border-b border-gray-200 pb-2">
                        <h3 class="text-sm font-semibold text-gray-900 leading-tight">
                          {{ table.name }}
                        </h3>

                        <div
                          class="mt-0.5 h-3 w-3 flex-shrink-0 rounded-full"
                          :class="selectedTable?.id === table.id ? 'bg-blue-600' : 'bg-gray-300'"
                        />
                      </div>

                      <!-- Body -->
                      <div class="pt-3 space-y-1 text-xs text-gray-600">
                        <div class="flex justify-between gap-3">
                          <span>Capacity</span>
                          <span class="font-medium text-gray-900">{{ table.capacity }}</span>
                        </div>

                        <div class="flex justify-between gap-3">
                          <span>Occupied</span>
                          <span class="font-medium text-gray-900">{{ getTableStats(table.id).occupiedPax }}</span>
                        </div>

                        <div class="flex justify-between gap-3">
                          <span>Available</span>
                          <span
                            class="font-semibold"
                            :class="getTableStats(table.id).availableCapacity === 0 ? 'text-red-600' : 'text-green-600'"
                          >
                            {{ getTableStats(table.id).availableCapacity }}
                          </span>
                        </div>
                      </div>

                      <!-- Status badges -->
                      <div class="mt-3 flex flex-wrap gap-1">
                        <span
                          v-if="getTableStats(table.id).isFullyOccupied"
                          class="inline-flex rounded-full bg-red-100 px-2 py-1 text-[10px] font-semibold text-red-700"
                        >
                          Fully Occupied
                        </span>

                        <span
                          v-else-if="getTableStats(table.id).hasOpenSession"
                          class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-[10px] font-semibold text-amber-700"
                        >
                          Occupied
                        </span>

                        <span
                          v-else
                          class="inline-flex rounded-full bg-green-100 px-2 py-1 text-[10px] font-semibold text-green-700"
                        >
                          Available
                        </span>

                        <span
                          v-if="getTableStats(table.id).hasSharedSession"
                          class="inline-flex rounded-full bg-blue-100 px-2 py-1 text-[10px] font-semibold text-blue-700"
                        >
                          Shared
                        </span>
                      </div>
                    </div>
                  </button>
                </div>

                <div
                  v-if="!filteredTables.length"
                  class="mt-4 rounded-xl border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-500"
                >
                  No tables found.
                </div>

                <p v-if="errors.table_id" class="mt-3 text-sm text-red-500">
                  {{ errors.table_id }}
                </p>

                <p v-if="errors.general" class="mt-3 text-sm text-red-500">
                  {{ errors.general }}
                </p>
              </div>
            </div>
          </div>

          <!-- Form -->
          <div class="xl:col-span-1">
            <form @submit.prevent="submitAdmission" class="rounded-2xl border bg-white shadow-sm">
              <div class="border-b px-5 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Admission Details</h2>
                <p class="text-sm text-gray-500">Fill in customer and pricing details.</p>
              </div>

              <div class="space-y-5 p-5">
                <div class="rounded bg-gray-100 px-3 py-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Selected Table:
                    <span class="text-teal-700">
                      {{ selectedTable ? selectedTable.name : 'None' }}
                    </span>
                  </label>
                </div>

                <div>
                  <label class="mb-1 block text-sm font-medium text-gray-700">
                    Pricing Scheme <span class="text-red-500">*</span>
                  </label>

                  <select
                    v-model="form.pricing_scheme_id"
                    class="w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                    <option :value="null" disabled>Select pricing scheme</option>
                    <option
                      v-for="scheme in activePricingSchemes"
                      :key="scheme.id"
                      :value="scheme.id"
                    >
                      {{ scheme.name }} ({{ scheme.type }})
                    </option>
                  </select>

                  <p v-if="errors.pricing_scheme_id" class="mt-1 text-xs text-red-500">
                    {{ errors.pricing_scheme_id }}
                  </p>

                  <div
                    v-if="selectedPricingScheme"
                    class="mt-2 rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-2 text-xs text-indigo-700"
                  >
                    <span class="font-semibold">{{ selectedPricingScheme.name }}</span>
                    <span v-if="selectedPricingScheme.description">
                      — {{ selectedPricingScheme.description }}
                    </span>
                  </div>
                </div>

                <div>
                  <label class="mb-1 block text-sm font-medium text-gray-700">
                    Customer Name <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.customer_name"
                    type="text"
                    class="w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Enter customer name"
                  />
                  <p v-if="errors.customer_name" class="mt-1 text-xs text-red-500">
                    {{ errors.customer_name }}
                  </p>
                </div>

                <div>
                  <label class="mb-1 block text-sm font-medium text-gray-700">
                    Remarks
                  </label>
                  <textarea
                    v-model="form.remarks"
                    rows="3"
                    class="w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Optional remarks"
                  />
                  <p v-if="errors.remarks" class="mt-1 text-xs text-red-500">
                    {{ errors.remarks }}
                  </p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                  <label class="flex cursor-pointer items-center justify-between gap-3">
                    <div>
                      <div class="text-sm font-medium text-gray-800">Shared Table</div>
                      <div class="text-xs text-gray-500">
                        Allow another customer/group to use the same table.
                      </div>
                    </div>

                    <input
                      v-model="form.is_shared"
                      type="checkbox"
                      class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />
                  </label>
                </div>

                <div>
                  <div class="mb-2 flex items-center justify-between">
                    <label class="block text-sm font-medium text-gray-700">
                      Head Prices
                    </label>
                    <span class="text-xs text-gray-400">
                      Based on selected pricing scheme
                    </span>
                  </div>

                  <div v-if="activeHeadPrices.length" class="space-y-3">
                    <div
                      v-for="head in activeHeadPrices"
                      :key="head.id"
                      class="rounded-xl border border-gray-200 px-4 py-3"
                    >
                      <div class="flex items-start justify-between gap-3">
                        <div>
                          <div class="text-sm font-semibold text-gray-900">
                            {{ head.label }}
                          </div>
                          <div class="text-xs text-gray-500">
                            ₱{{ formatMoney(head.price) }} each
                          </div>
                        </div>

                        <div class="w-24">
                          <input
                            v-model.number="form.head_counts[head.id]"
                            type="number"
                            min="0"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="0"
                          />
                        </div>
                      </div>

                      <div class="mt-2 text-xs text-gray-500">
                        Line Total:
                        <span class="font-medium text-gray-700">
                          ₱{{ formatMoney(lineTotal(head)) }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <div
                    v-else
                    class="rounded-xl border border-dashed border-gray-300 px-4 py-4 text-sm text-gray-500"
                  >
                    No active head rules found for the selected pricing scheme.
                  </div>

                  <p v-if="errors.head_counts" class="mt-2 text-xs text-red-500">
                    {{ errors.head_counts }}
                  </p>
                </div>

                <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4">
                  <h3 class="text-sm font-semibold text-blue-900">Admission Summary</h3>

                  <div class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between">
                      <span class="text-gray-600">Table</span>
                      <span class="font-medium text-gray-900">
                        {{ selectedTable ? selectedTable.name : '-' }}
                      </span>
                    </div>

                    <div class="flex justify-between">
                      <span class="text-gray-600">Pricing Scheme</span>
                      <span class="font-medium text-gray-900">
                        {{ selectedPricingScheme ? selectedPricingScheme.name : '-' }}
                      </span>
                    </div>

                    <div class="flex justify-between">
                      <span class="text-gray-600">Capacity</span>
                      <span class="font-medium text-gray-900">
                        {{ selectedTable ? selectedTable.capacity : '-' }}
                      </span>
                    </div>

                    <div class="flex justify-between">
                      <span class="text-gray-600">Available Seats</span>
                      <span class="font-medium text-gray-900">
                        {{ selectedTable ? getTableStats(selectedTable.id).availableCapacity : '-' }}
                      </span>
                    </div>

                    <div class="flex justify-between">
                      <span class="text-gray-600">Total Pax</span>
                      <span class="font-medium text-gray-900">{{ totalPax }}</span>
                    </div>

                    <div class="flex justify-between">
                      <span class="text-gray-600">Subtotal</span>
                      <span class="font-semibold text-gray-900">
                        ₱{{ formatMoney(subtotal) }}
                      </span>
                    </div>

                    <div
                      v-if="selectedTable && totalPax > getTableStats(selectedTable.id).availableCapacity"
                      class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-600"
                    >
                      Pax exceeds available seats for this table.
                    </div>
                  </div>
                </div>

                <div class="flex gap-3">
                  <button
                    type="button"
                    @click="resetForm"
                    class="inline-flex w-full items-center justify-center rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                  >
                    Reset
                  </button>

                  <button
                    type="submit"
                    :disabled="processing || !selectedTable"
                    class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                  >
                    {{ processing ? 'Saving...' : 'Admit Table' }}
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <div v-if="toast.show" class="fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded shadow-lg transition-opacity duration-300 z-[9999]">
          {{ toast.message }}
        </div>
        
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useTableAdmission } from '@/Composables/tables/useTableAdmission'

const {
  tables,
  tableSearch,
  filteredTables,
  activePricingSchemes,
  selectedPricingScheme,
  activeHeadPrices,
  selectedTable,
  toast,
  form,
  errors,
  processing,
  totalPax,
  subtotal,
  getTableStats,
  canSelectTable,
  selectTable,
  resetForm,
  submitAdmission,
  formatMoney,
  lineTotal,
  
  incomingReservation,
  formatDateTime
} = useTableAdmission()
</script>