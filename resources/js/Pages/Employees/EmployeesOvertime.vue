<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50">

        <h1 class="text-2xl font-semibold mb-6 text-gray-900">
          Employees Overtime Requests
        </h1>

        <div class="flex flex-wrap gap-4 mb-4">
          <input
            type="text"
            v-model="filters.search"
            placeholder="Search by name or code"
            class="px-3 py-2 border rounded-md focus:outline-none focus:ring focus:border-blue-300"
          />

          <input
            type="date"
            v-model="filters.startDate"
            class="px-3 py-2 border rounded-md focus:outline-none focus:ring focus:border-blue-300"
          />

          <input
            type="date"
            v-model="filters.endDate"
            class="px-3 py-2 border rounded-md focus:outline-none focus:ring focus:border-blue-300"
          />

          <button
            @click="fetchFilteredOvertime"
            class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
          >
            Filter
          </button>
        </div>

        <div class="bg-white shadow rounded-xl overflow-hidden">
          <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-100 text-xs uppercase">
              <tr>
                <th class="px-4 py-3">Employee</th>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Start</th>
                <th class="px-4 py-3">End</th>
                <th class="px-4 py-3">Hours</th>
                <th class="px-4 py-3">Type</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 text-center">Action</th>
              </tr>
            </thead>

            <tbody>
              <template v-for="employee in employeesWithOvertime" :key="employee.id">
                <tr
                  v-for="ot in employee.overtimes"
                  :key="ot.id"
                  class="border-t hover:bg-gray-50"
                >
                  <td class="px-4 py-3">
                    <div class="font-medium">
                      {{ employee.first_name }} {{ employee.last_name }}
                    </div>
                    <div class="text-xs text-gray-500">
                      {{ employee.employee_code }}
                    </div>
                  </td>

                  <td class="px-4 py-3">{{ formatDate(ot.ot_date) }}</td>
                  <td class="px-4 py-3">{{ ot.start_time }}</td>
                  <td class="px-4 py-3">{{ ot.end_time }}</td>
                  <td class="px-4 py-3">{{ ot.total_hours }}</td>
                  <td class="px-4 py-3 capitalize">{{ formatOtType(ot.type) }}</td>

                  <td class="px-4 py-3">
                    <span
                      class="px-2 py-1 text-xs rounded-full text-white"
                      :class="{
                        'bg-yellow-500': ot.status === 'pending',
                        'bg-green-600': ot.status === 'approved',
                        'bg-red-500': ot.status === 'rejected'
                      }"
                    >
                      {{ ot.status }}
                    </span>
                  </td>

                  <td class="px-4 py-3 text-center space-x-2">
                    <button
                      @click="updateStatus(ot.id, 'approved')"
                      class="px-3 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700"
                    >
                      Approve
                    </button>

                    <button
                      @click="updateStatus(ot.id, 'rejected')"
                      class="px-3 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600"
                    >
                      Reject
                    </button>
                  </td>
                </tr>
              </template>

              <tr v-if="!employeesWithOvertime.length">
                <td colspan="8" class="text-center py-6 text-gray-500">
                  No overtime requests found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div
      v-if="toast.show"
      class="fixed top-5 right-5 z-50 px-4 py-3 rounded-lg shadow-lg text-white"
      :class="toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'"
    >
      {{ toast.message }}
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useEmployeeOvertimes } from '@/Composables/employees/useEmployeeOvertimes'

const {
  filters,
  toast,
  employeesWithOvertime,
  fetchFilteredOvertime,
  updateStatus,
  formatOtType,
  formatDate,
} = useEmployeeOvertimes()
</script>