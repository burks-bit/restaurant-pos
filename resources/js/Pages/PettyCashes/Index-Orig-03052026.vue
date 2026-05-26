<template>
  <AuthenticatedLayout>
  <div class="p-4 bg-gray-50">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-3xl font-bold text-black-600">Petty Cash</h1>
      <!-- <OperationLinks /> -->
    </div>

    <!-- Filters & Actions -->
    <div class="mb-4 flex flex-wrap gap-4 items-center">
      <!-- Date filter -->
      <div class="flex items-center gap-2">
        <label class="text-sm font-medium text-gray-700">Date:</label>
        <input
          type="date"
          v-model="selectedDate"
          @change="filterByDate"
          class="border rounded px-2 py-1 text-sm"
        />
      </div>

      <!-- Search -->
      <input
        v-model="search"
        type="text"
        placeholder="Search notes or purpose…"
        class="border rounded px-3 py-1 text-sm w-64"
      />

      <!-- Button to create new petty cash -->
      <button
        @click="openNewPettyCashModal"
        class="px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600"
      >
        <span class="fa fa-plus"></span>
        Post New Petty Cash
      </button>
    </div>

    <!-- Flash messages -->
    <div v-if="$page.props.flash.success" class="mb-4 p-2 bg-blue-100 text-blue-700 rounded">
      {{ $page.props.flash.success }}
    </div>
    <div v-if="$page.props.flash.error" class="mb-4 p-2 bg-red-100 text-red-700 rounded">
      {{ $page.props.flash.error }}
    </div>

    <!-- Petty Cash Table -->
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
              <td colspan="8" class="text-center py-6 text-gray-400">No petty cash found</td>
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
              <td class="px-4 py-2 font-semibold">₱{{ Number(pc.remaining).toFixed(2) }}</td>
              <td class="px-4 py-2 font-semibold">{{ pc.posted_by_user.name }}</td>
              <td class="px-4 py-2 font-semibold">{{ pc.posted_by_user.name }}</td>
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

    <!-- New Petty Cash Modal -->
    <div v-if="showNewModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center">
      <div class="bg-white rounded shadow-lg w-96 p-4 max-h-[80vh] overflow-y-auto">
        <h2 class="text-lg font-bold mb-4">Post New Petty Cash</h2>
        <div class="flex flex-col gap-3">
          <input type="date" v-model="newPettyCash.date" class="border rounded px-2 py-1 text-sm" />
          <input type="number" v-model.number="newPettyCash.total_amount" placeholder="Total Amount" class="border rounded px-2 py-1 text-sm"/>
          <input type="text" v-model="newPettyCash.denominations" placeholder='Denominations (JSON e.g. {"100":5})' class="border rounded px-2 py-1 text-sm"/>
          <input type="text" v-model="newPettyCash.notes" placeholder="Notes (optional)" class="border rounded px-2 py-1 text-sm"/>
          <div class="flex justify-end gap-2 mt-2">
            <button @click="showNewModal = false" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                <span class="fa fa-close"></span>
                Cancel
            </button>
            <button @click="postNewPettyCash" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                <span class="fa fa-save"></span>
                Post
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Petty Cash Details Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center">
      <div class="bg-white rounded shadow-lg w-[110vh] p-6 max-h-[85vh] overflow-y-auto">
        
        <h2 class="text-lg font-bold mb-4">
          Petty Cash - {{ selectedPettyCash?.date }}
        </h2>

        <!-- 🔥 TOP: 2 COLUMNS -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

          <!-- LEFT COLUMN - Computation -->
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
          </div>

          <!-- RIGHT COLUMN - Form -->
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

              <input
                type="number"
                v-model.number="newUsage.amount"
                placeholder="Amount used"
                class="border rounded px-2 py-1 text-sm"
              />

              <input
                type="text"
                v-model="newUsage.denominations"
                placeholder='Denominations (JSON e.g. {"100":1})'
                class="border rounded px-2 py-1 text-sm"
              />

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

        <!-- 🔥 BELOW: FULL WIDTH TABLE -->
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
                <td class="px-4 py-2 border">
                  ₱{{ Number(detail.amount).toFixed(2) }}
                </td>
                <td class="px-4 py-2 border">
                  {{ detail.posted_by_user?.name ?? '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Footer -->
        <div class="flex justify-end mt-6">
          <button
            @click="showModal = false"
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
import { ref, computed, reactive } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Inertia } from '@inertiajs/inertia'
import OperationLinks from '@/Components/NavLinks/OperationLinks.vue'
import useRolePrefix from '@/Composables/useRolePrefix'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const { prefix } = useRolePrefix()

// make pettyCashes reactive
const { props } = usePage()
const pettyCashes = ref(props.pettyCashes ?? [])

console.log(pettyCashes.value)

const showModal = ref(false)
const showNewModal = ref(false)
const selectedPettyCash = ref(null)
const selectedDate = ref(new Date().toISOString().slice(0, 10))
const search = ref('')

const hasRemaining = computed(() => {
  if (!selectedPettyCash.value) return false
  return Number(selectedPettyCash.value.remaining) > 0
})

const newPettyCash = reactive({
  date: new Date().toISOString().slice(0, 10),
  total_amount: 0,
  denominations: '',
  notes: ''
})

const newUsage = reactive({
  purpose: '',
  amount: 0,
  denominations: '',
  notes: ''
})

// Filter petty cash by date or search
const filteredPettyCashes = computed(() => {
  let result = pettyCashes.value

  if (selectedDate.value) {
    result = result.filter(pc => {
      const pcDate = pc.date.slice(0, 10) // take YYYY-MM-DD
      return pcDate === selectedDate.value
    })
  }

  if (search.value) {
    const q = search.value.toLowerCase()
    result = result.filter(pc =>
      (pc.notes || '').toLowerCase().includes(q) ||
      (pc.details?.some(d => d.purpose.toLowerCase().includes(q)))
    )
  }

  return result
})

function openModal(pc) {
  selectedPettyCash.value = {
    ...pc,
    total_amount: Number(pc.total_amount),
    amount_used: Number(pc.amount_used),
    remaining: Number(pc.remaining)
  }
  showModal.value = true
}

function openNewPettyCashModal() {
  showNewModal.value = true
}

// Post new petty cash without page reload
async function postNewPettyCash() {
  if (!newPettyCash.total_amount) {
    alert('Total amount is required')
    return
  }

  try {
    const response = await axios.post(
      route(`${prefix.value}.petty-cashes.store`),
      newPettyCash
    )

    pettyCashes.value.unshift(response.data.pettyCash)

    showNewModal.value = false

    newPettyCash.total_amount = 0
    newPettyCash.denominations = ''
    newPettyCash.notes = ''

  } catch (error) {
    console.error(error)
    alert('Something went wrong.')
  }
}

// Post usage under selected petty cash without page reload
async function postUsage() {
  if (!hasRemaining.value) {
    alert('No remaining petty cash balance.')
    return
  }

  if (!newUsage.amount || !newUsage.purpose) {
    alert('Purpose and amount are required')
    return
  }

  if (newUsage.amount > selectedPettyCash.value.remaining) {
    alert('Amount exceeds remaining balance.')
    return
  }

  try {
    const response = await axios.post(
      route(`${prefix.value}.petty-cashes.details.store`, {
        pettyCash: selectedPettyCash.value.id
      }),
      newUsage
    )

    const updated = response.data.pettyCash

    // Update main list
    const index = pettyCashes.value.findIndex(pc => pc.id === updated.id)
    if (index !== -1) {
      pettyCashes.value[index] = updated
    }

    // Update modal data
    selectedPettyCash.value = updated

    // Reset form
    newUsage.purpose = ''
    newUsage.amount = 0
    newUsage.denominations = ''
    newUsage.notes = ''

  } catch (error) {
    console.error(error)
    alert('Something went wrong.')
  }
}

function filterByDate() {
  Inertia.get(route(`${prefix.value}.petty-cashes.index`), { date: selectedDate.value }, { preserveState: true, replace: true })
}
</script>