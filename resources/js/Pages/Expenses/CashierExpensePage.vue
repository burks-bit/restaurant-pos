<template>
  <AuthenticatedLayout>
    <div class="p-4 bg-gray-50">
      <!-- HEADER -->
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-black-600">Expenses</h1>

        <button
          @click="openModal"
          class="px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600"
        >
          <span class="fa fa-plus mr-1"></span>
          Post Expense
        </button>
      </div>

      <!-- FILTER -->
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
          placeholder="Search description…"
          class="border rounded px-3 py-1 text-sm w-64"
        />
      </div>

      <!-- FLASH -->
      <div
        v-if="$page.props.flash.success"
        class="mb-4 p-2 bg-blue-100 text-blue-700 rounded"
      >
        {{ $page.props.flash.success }}
      </div>

      <!-- TABLE -->
      <div class="bg-white rounded shadow overflow-hidden">
        <div class="max-h-[60vh] overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-100 sticky top-0">
              <tr class="text-left">
                <th class="px-4 py-2">#</th>
                <th class="px-4 py-2">Date</th>
                <th class="px-4 py-2">Description</th>
                <th class="px-4 py-2 text-right">Amount</th>
                <th class="px-4 py-2">Posted By</th>
                <th class="px-4 py-2 text-right">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="filteredExpenses.length === 0">
                <td colspan="6" class="text-center py-6 text-gray-400">
                  No expenses found
                </td>
              </tr>

              <tr
                v-for="(exp, index) in filteredExpenses"
                :key="exp.id"
                class="border-t hover:bg-gray-50"
              >
                <td class="px-4 py-2">{{ index + 1 }}</td>
                <td class="px-4 py-2">{{ new Date(exp.created_at).toLocaleString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</td>
                <td class="px-4 py-2">{{ exp.description }}</td>
                <td class="px-4 py-2 text-right text-red-600 font-semibold">
                  ₱ {{ Number(exp.amount).toFixed(2) }}
                </td>
                <td class="px-4 py-2">{{ exp.creator?.name }}</td>
                <td class="px-4 py-2 text-right">
                  <button
                    @click="openEditModal(exp)"
                    class="px-2 py-1 text-xs bg-yellow-400 text-white rounded hover:bg-yellow-500"
                  >
                    <span class="fa fa-pencil mr-1"></span>Edit
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- MODAL -->
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center"
      >
        <div class="bg-white rounded shadow-lg w-[400px] p-4">
          <h2 class="text-lg font-bold mb-4">Post Expense</h2>

          <div class="flex flex-col gap-3">
            <!-- Amount -->
            <input
              type="number"
              v-model="form.amount"
              placeholder="Amount"
              class="border rounded px-2 py-1 text-sm"
            />

            <!-- Description -->
            <textarea
              v-model="form.description"
              placeholder="Description"
              class="border rounded px-2 py-1 text-sm"
            ></textarea>

            <div class="flex justify-end gap-2 mt-2">
              <button
                @click="closeModal"
                class="px-3 py-1 bg-gray-200 rounded"
              >
                Cancel
              </button>

              <button
                @click="submit"
                class="px-3 py-1 bg-red-500 text-white rounded"
              >
                Save
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- EDIT MODAL -->
      <div
        v-if="showEditModal"
        class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center"
      >
        <div class="bg-white rounded shadow-lg w-[400px] p-4">
          <h2 class="text-lg font-bold mb-4">
            <span class="fa fa-pencil mr-2 text-yellow-500"></span>
            Edit Expense
          </h2>

          <div class="flex flex-col gap-3">
            <input
              type="number"
              v-model="editForm.amount"
              placeholder="Amount"
              class="border rounded px-2 py-1 text-sm"
            />

            <textarea
              v-model="editForm.description"
              placeholder="Description"
              class="border rounded px-2 py-1 text-sm"
            ></textarea>

            <div class="flex justify-end gap-2 mt-2">
              <button
                @click="closeEditModal"
                class="px-3 py-1 bg-gray-200 rounded text-sm"
              >
                Cancel
              </button>
              <button
                @click="submitEdit"
                class="px-3 py-1 bg-yellow-500 text-white rounded text-sm"
              >
                <span class="fa fa-save mr-1"></span>Save Changes
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import useRolePrefix from '@/Composables/useRolePrefix'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

const current_shift = computed(() => page.props.current_shift ?? [])
const { prefix } = useRolePrefix()

const props = defineProps({
  current_shift: Object
})

const showModal = ref(false)
const search = ref('')
const selectedDate = ref(new Date().toISOString().slice(0, 10))
const localExpenses = ref(page.props.expenses ?? [])

const form = ref({
  shift_id: current_shift.value?.id ?? null,
  amount: '',
  description: ''
})

const openModal = () => (showModal.value = true)
const closeModal = () => (showModal.value = false)

const submit = async () => {
  await axios.post(route(`${prefix.value}.expenses.store`), form.value)
  form.value.amount = ''
  form.value.description = ''
  closeModal()
  await filterByDate()
}

// Edit state
const showEditModal = ref(false)
const editForm = ref({
  id: null,
  amount: '',
  description: ''
})

function openEditModal(exp) {
  editForm.value.id          = exp.id
  editForm.value.amount      = exp.amount
  editForm.value.description = exp.description
  showEditModal.value        = true
}

function closeEditModal() {
  showEditModal.value = false
}

async function submitEdit() {
  try {
    await axios.put(
      route(`${prefix.value}.cashier_expenses.update`, editForm.value.id),
      {
        amount:      editForm.value.amount,
        description: editForm.value.description,
      }
    )
    closeEditModal()
    await filterByDate()
  } catch (error) {
    console.error(error)
    if (error.response?.data?.errors) {
      alert(Object.values(error.response.data.errors).flat().join('\n'))
    } else {
      alert('Something went wrong while updating the expense.')
    }
  }
}

async function filterByDate() {
  try {
    const response = await axios.get(
      route(`${prefix.value}.cashier_expenses.fetch`),
      { params: { date: selectedDate.value } }
    )
    localExpenses.value = response.data.expenses
  } catch (error) {
    console.error(error)
    alert('Failed to load expenses.')
  }
}

const filteredExpenses = computed(() => {
  return localExpenses.value.filter(e =>
    e.description?.toLowerCase().includes(search.value.toLowerCase())
  )
})

onMounted(() => filterByDate())
</script>