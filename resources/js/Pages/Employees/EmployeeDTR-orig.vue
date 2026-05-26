<template>
  <AuthenticatedLayout>

    <!-- ================= FULL SCREEN LOADER ================= -->
    <div
      v-if="isLoading"
      class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50"
    >
      <div class="bg-white p-8 rounded-xl shadow-xl flex flex-col items-center">
        <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
        <p class="text-lg font-semibold text-gray-700">
          Processing Payroll...
        </p>
      </div>
    </div>

    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50">

        <!-- PAGE TITLE -->
        <h1 class="text-2xl font-semibold mb-6 text-gray-900">
          Payroll Summary
        </h1>

        <!-- ================= Search and FILTER SECTION ================= -->
        <div class="bg-white p-4 rounded-lg shadow mb-6">
          <div class="flex flex-wrap items-end gap-4">

            <!-- Search -->
            <div class="flex flex-col">
              <label class="text-sm text-gray-600 mb-1">Search</label>
              <input
                v-model="search"
                type="text"
                placeholder="Search employee..."
                class="border px-3 py-2 rounded text-sm w-64"
              />
            </div>

            <!-- Per Page -->
            <div class="flex flex-col">
              <label class="text-sm text-gray-600 mb-1">Rows</label>
              <select v-model="perPage"
                      class="border px-3 py-2 rounded text-sm w-24">
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="20">20</option>
              </select>
            </div>

            <!-- Start Date -->
            <div class="flex flex-col">
              <label class="text-sm text-gray-600 mb-1">Start Date</label>
              <input type="date"
                    v-model="filters.start_date"
                    class="border rounded px-3 py-2 text-sm"
                    :disabled="isLoading" />
            </div>

            <!-- End Date -->
            <div class="flex flex-col">
              <label class="text-sm text-gray-600 mb-1">End Date</label>
              <input type="date"
                    v-model="filters.end_date"
                    class="border rounded px-3 py-2 text-sm"
                    :disabled="isLoading" />
            </div>

            <!-- Buttons -->
            <div class="flex gap-2">
              <button
                @click="getSchedules"
                :disabled="isLoading"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
                <span class="fa fa-filter"></span> Get Schedules
              </button>

              <button
                @click="printPayroll"
                :disabled="employees.length === 0 || isLoading"
                class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-800 disabled:opacity-50">
                <span class="fa fa-print"></span> Print Payroll Copy
              </button>
            </div>

          </div>
        </div>
        

        <!-- ================= PAYROLL TABLE ================= -->
        <div class="bg-white rounded-lg shadow">

          <div class="max-h-[500px] overflow-auto custom-scroll">

            <table class="min-w-full border-collapse text-sm">

              <!-- HEADER -->
              <thead class="bg-gray-100 sticky top-0 z-20">
                <tr>
                  <th class="sticky left-0 bg-gray-100 z-30 border px-3 py-2">Code</th>
                  <th class="sticky left-[120px] bg-gray-100 z-30 border px-3 py-2">Employee Name</th>
                  <th class="border px-3 py-2 text-center">Days</th>
                  <th class="border px-3 py-2 text-center">Hours</th>
                  <th class="border px-3 py-2 text-center">Late</th>
                  <th class="border px-3 py-2 text-center">Undertime</th>
                  <th class="sticky right-0 bg-gray-100 z-30 border px-3 py-2 text-right">Earnings</th>
                </tr>
              </thead>

              <!-- BODY -->
              <tbody>
                <tr v-if="paginatedEmployees.length === 0">
                  <td colspan="7" class="text-center py-6 text-gray-400">
                    No payroll records found.
                  </td>
                </tr>

                <tr v-for="emp in paginatedEmployees" :key="emp.id"
                  class="hover:bg-gray-50">

                  <td class="sticky left-0 bg-white border px-3 py-2 w-[120px]">
                    {{ emp.employee_code }}
                  </td>

                  <td class="sticky left-[120px] bg-white border px-3 py-2 min-w-[200px]">
                    {{ emp.first_name }} {{ emp.last_name }}
                  </td>

                  <td class="border px-3 py-2 text-center">{{ emp.total_days }}</td>
                  <td class="border px-3 py-2 text-center">{{ emp.total_hours }}</td>
                  <td class="border px-3 py-2 text-center text-red-600">{{ emp.total_late }}</td>
                  <td class="border px-3 py-2 text-center text-orange-600">{{ emp.total_undertime }}</td>

                  <td class="sticky right-0 bg-white border px-3 py-2 text-right font-semibold">
                    ₱ {{ Number(emp.total_earnings).toFixed(2) }}
                  </td>
                </tr>
              </tbody>

              <!-- FOOTER -->
              <tfoot v-if="filteredEmployees.length > 0"
                class="sticky bottom-0 bg-gray-100 z-20">
                <tr class="font-bold">
                  <td colspan="6" class="border px-3 py-2 text-right">
                    Grand Total Payroll
                  </td>
                  <td class="sticky right-0 bg-gray-100 border px-3 py-2 text-right">
                    ₱ {{ grandTotal }}
                  </td>
                </tr>
              </tfoot>

            </table>
          </div>
        </div>

        <!-- PAGINATION -->
        <div class="flex justify-between items-center mt-4 text-sm">
          <div>
            Showing {{ startItem }} - {{ endItem }} of {{ filteredEmployees.length }}
          </div>

          <div class="flex gap-2">
            <button @click="prevPage" :disabled="page===1"
              class="px-3 py-1 border rounded disabled:opacity-40">
              Prev
            </button>

            <button @click="nextPage" :disabled="page===totalPages"
              class="px-3 py-1 border rounded disabled:opacity-40">
              Next
            </button>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'
import useRolePrefix from '@/Composables/useRolePrefix'

const { prefix } = useRolePrefix()

const filters = ref({ start_date:'', end_date:'' })
const employees = ref([])
const isLoading = ref(false)

const search = ref('')
const page = ref(1)
const perPage = ref(10)

const filteredEmployees = computed(() => {
  return employees.value.filter(emp =>
    `${emp.first_name} ${emp.last_name} ${emp.employee_code}`
      .toLowerCase()
      .includes(search.value.toLowerCase())
  )
})

const totalPages = computed(() =>
  Math.ceil(filteredEmployees.value.length / perPage.value)
)

const paginatedEmployees = computed(() => {
  const start = (page.value - 1) * perPage.value
  return filteredEmployees.value.slice(start, start + perPage.value)
})

const startItem = computed(() =>
  filteredEmployees.value.length === 0 ? 0 : (page.value - 1) * perPage.value + 1
)

const endItem = computed(() =>
  Math.min(page.value * perPage.value, filteredEmployees.value.length)
)

const grandTotal = computed(() =>
  filteredEmployees.value
    .reduce((sum, emp) => sum + Number(emp.total_earnings || 0), 0)
    .toFixed(2)
)

const prevPage = () => page.value--
const nextPage = () => page.value++

const getSchedules = async () => {
  if (!filters.value.start_date || !filters.value.end_date) {
    alert('Please select both dates.')
    return
  }

  try {
    isLoading.value = true
    const { data } = await axios.get(`/${prefix.value}/payroll/summary`, {
      params: filters.value
    })
    employees.value = data
    page.value = 1
  } finally {
    isLoading.value = false
  }
}

// const printPayroll = () => {
//   // Assuming `filters.value` contains query params
//   const query = new URLSearchParams(filters.value).toString();
//   const url = `/${prefix.value}/payroll/summary/print?${query}`;
//   window.open(url, '_blank'); // Open the PDF in a new tab
// }

const printPayroll = () => {
  const query = new URLSearchParams(filters.value).toString();
  window.open(`/${prefix.value}/payroll/summary/print?${query}`, '_blank');
}
</script>

<style>
.custom-scroll::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}
.custom-scroll::-webkit-scrollbar-thumb {
  background: #94a3b8;
  border-radius: 10px;
}
.custom-scroll::-webkit-scrollbar-track {
  background: #f1f5f9;
}
</style>