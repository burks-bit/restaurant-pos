<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50 min-h-screen">
        <h1 class="text-2xl font-semibold mb-4 text-gray-900">Reservations</h1>

        <!-- Toolbar -->
        <div class="flex justify-between mb-4 flex-wrap gap-2">
          <div class="flex gap-2 flex-wrap">
            <input
              v-model="search"
              type="text"
              placeholder="Search name, contact, status..."
              class="border rounded px-3 py-1 text-sm w-56 focus:outline-none focus:ring-2 focus:ring-blue-600"
            />
            <select
              v-model="statusFilter"
              class="border rounded px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
            >
              <option value="">All statuses</option>
              <option value="pending">Pending</option>
              <option value="confirmed">Confirmed</option>
              <option value="seated">Seated</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <button
            class="flex items-center gap-1 text-sm bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700"
            @click="openAddModal(pricingRules)"
          >
            <i class="fa fa-plus"></i> Add Reservation
          </button>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow overflow-auto max-h-[65vh]">
          <table class="min-w-full table-auto border-collapse text-sm">
            <thead class="sticky top-0 bg-gray-100 z-10 text-gray-800 text-left">
              <tr>
                <th class="px-3 py-2 border">Name</th>
                <th class="px-3 py-2 border">Entry Datetime</th>
                <th class="px-3 py-2 border">Scheme</th>
                <th class="px-3 py-2 border">Pax breakdown</th>
                <th class="px-3 py-2 border">Schedule</th>
                <th class="px-3 py-2 border">Contact</th>
                <th class="px-3 py-2 border">Res. fee</th>
                <th class="px-3 py-2 border">Status</th>
                <th class="px-3 py-2 border">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="r in filteredReservations"
                :key="r.id"
                class="hover:bg-gray-50"
              >
                <td class="px-3 py-1 border font-medium">{{ r.name }}</td>
                <td class="px-3 py-1 border">{{ formatDateTime(r.created_at) }}</td>
                <td class="px-3 py-1 border text-gray-600">{{ r.pricing_scheme?.name ?? '-' }}</td>
                <td class="px-3 py-1 border">
                  <div v-if="r.reservation_pax?.length" class="flex flex-col gap-0.5">
                    <span
                      v-for="p in r.reservation_pax.filter(x => x.qty > 0)"
                      :key="p.id"
                      class="text-xs text-gray-700"
                    >
                      {{ p.head_pricing_rule?.label }}: {{ p.qty }}
                    </span>
                  </div>
                  <span v-else class="text-gray-400">{{ r.pax }} pax</span>
                </td>
                <td class="px-3 py-1 border">{{ formatDateTime(r.reservation_datetime) }}</td>
                <td class="px-3 py-1 border">{{ r.contact_number ?? '-' }}</td>
                <td class="px-3 py-1 border">{{ formatCurrency(r.reservation_fee) }}</td>
                <td class="px-3 py-1 border">
                  <span class="px-2 py-0.5 rounded text-xs font-medium" :class="statusClass(r.status)">
                    {{ r.status }}
                  </span>
                </td>
                <td class="px-3 py-1 border">
                  <div class="flex gap-1 flex-wrap">
                    <!-- Arrival — only for pending/confirmed -->
                    <button
                      v-if="['pending','confirmed'].includes(r.status)"
                      class="flex items-center gap-1 px-1.5 py-0.5 text-xs bg-green-600 text-white rounded hover:bg-green-700"
                      @click="goToAdmission(r)"
                    >
                      <i class="fa fa-sign-in"></i> Arrived
                    </button>
                    <button
                      class="flex items-center gap-1 px-1.5 py-0.5 text-xs bg-blue-600 text-white rounded hover:bg-blue-700"
                      @click="openEditModal(r, pricingRules)"
                    >
                      <i class="fa fa-edit"></i> Edit
                    </button>
                    <button
                      class="flex items-center gap-1 px-1.5 py-0.5 text-xs bg-red-600 text-white rounded hover:bg-red-700"
                      @click="deleteReservation(r)"
                    >
                      <i class="fa fa-trash"></i> Delete
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredReservations.length === 0">
                <td colspan="8" class="text-center py-8 text-gray-400">No reservations found</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ── Add Modal ───────────────────────────────────────────────────── -->
        <Teleport to="body">
          <div
            v-if="showAddModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
          >
            <div class="bg-white rounded-lg w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
              <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i class="fa fa-calendar"></i> Add Reservation
              </h2>
              <ReservationForm
                :form="newForm"
                :pricing-schemes="pricingSchemes"
                :pricing-rules="pricingRules"
                :processing="processing"
                @submit="saveReservation"
                @cancel="showAddModal = false"
                @qty-change="(i) => onQtyChange(newForm.pax_breakdown, i)"
                @scheme-change="onSchemeChange(newForm, $event)"
              />
            </div>
          </div>
        </Teleport>

        <!-- ── Edit Modal ──────────────────────────────────────────────────── -->
        <Teleport to="body">
          <div
            v-if="showEditModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
          >
            <div class="bg-white rounded-lg w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
              <h2 class="text-lg font-semibold text-gray-900 mb-4">Edit Reservation</h2>
              <ReservationForm
                :form="editForm"
                :pricing-schemes="pricingSchemes"
                :pricing-rules="pricingRules"
                :processing="processing"
                @submit="updateReservation"
                @cancel="showEditModal = false"
                @qty-change="(i) => onQtyChange(editForm.pax_breakdown, i)"
                @scheme-change="onSchemeChange(editForm, $event)"
              />
            </div>
          </div>
        </Teleport>

        <!-- ── Arrival / Table Assignment Modal ───────────────────────────── -->
        <Teleport to="body">
          <div
            v-if="showArrivalModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
          >
            <div class="bg-white rounded-lg w-full max-w-sm p-6">
              <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i class="fa fa-table"></i> Assign Table
              </h2>
              <p class="text-sm text-gray-600 mb-4">
                Select a table for the arriving reservation.
              </p>
              <div class="space-y-3">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Table</label>
                  <select
                    v-model="arrivalForm.table_id"
                    class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
                  >
                    <option value="" disabled>Select a table...</option>
                    <option v-for="t in availableTables" :key="t.id" :value="t.id">
                      {{ t.name }} (capacity: {{ t.capacity }})
                    </option>
                  </select>
                </div>
              </div>
              <div class="flex justify-end gap-2 mt-6">
                <button
                  class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 text-sm"
                  @click="showArrivalModal = false"
                >
                  Cancel
                </button>
                <button
                  class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm"
                  :disabled="!arrivalForm.table_id || processing"
                  @click="assignTable"
                >
                  <i class="fa fa-check"></i> Confirm Arrival
                </button>
              </div>
            </div>
          </div>
        </Teleport>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ReservationForm from './ReservationForm.vue'
import { useReservations } from '@/Composables/reservations/useReservations'

const page = usePage()
const reservations = computed(() => page.props.reservations ?? [])
const pricingSchemes = computed(() => page.props.pricing_schemes ?? [])
const pricingRules = computed(() => page.props.pricing_rules ?? [])
const availableTables = computed(() => page.props.available_tables ?? [])

const {
  search, statusFilter, processing,
  showAddModal, showEditModal, showArrivalModal,
  newForm, editForm, arrivalForm,
  filteredReservations,
  totalPax, totalEstimated, onQtyChange,
  openAddModal, openEditModal, openArrivalModal, goToAdmission,
  saveReservation, updateReservation, deleteReservation, assignTable,
  formatDateTime, statusClass, formatCurrency,
} = useReservations(reservations)

// When the pricing scheme changes, re-filter rules to that scheme
const onSchemeChange = (form, schemeId) => {
  form.pricing_scheme_id = schemeId
  const rules = pricingRules.value.filter((r) => r.pricing_scheme_id == schemeId)
  form.pax_breakdown = rules.map((rule) => ({
    head_pricing_rule_id: rule.id,
    label: rule.label,
    qty: 0,
    price_snapshot: parseFloat(rule.price ?? 0),
    subtotal: 0,
  }))
}
</script>