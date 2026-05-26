<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50">

        <h1 class="text-2xl font-semibold mb-4 text-gray-900">
          Employees Schedule
        </h1>

        <div class="flex justify-between mb-4 flex-wrap gap-2 items-end">
          <input
            v-model="search"
            type="text"
            placeholder="Search employees…"
            class="border rounded px-3 py-1 text-sm w-64 focus:outline-none focus:ring-2 focus:ring-blue-600"
          />

          <div class="flex gap-2 items-center">
            <input type="date" v-model="startDate" class="border rounded px-2 py-1 text-sm" />
            <input type="date" v-model="endDate" class="border rounded px-2 py-1 text-sm" />

            <button
              @click="printAllSchedules"
              class="flex items-center gap-1 text-sm bg-green-600 text-white px-3 py-1.5 rounded hover:bg-green-700"
            >
              <i class="fa fa-print"></i>
              Print All Schedules
            </button>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-auto max-h-[60vh]">
          <table class="min-w-full table-auto border-collapse text-sm">
            <thead class="sticky top-0 bg-gray-100">
              <tr class="text-left">
                <th class="border px-2 py-1">ID</th>
                <th class="border px-2 py-1">Code</th>
                <th class="border px-2 py-1">Name</th>
                <th class="border px-2 py-1">Position / Department</th>
                <th class="border px-2 py-1">Status</th>
                <th class="border px-2 py-1">Schedule</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="employee in filteredEmployees" :key="employee.id">
                <td class="border px-2 py-1">{{ employee.id }}</td>
                <td class="border px-2 py-1">{{ employee.employee_code }}</td>
                <td class="border px-2 py-1">
                  {{ employee.first_name }} {{ employee.last_name }}
                </td>
                <td class="border px-2 py-1">
                  {{ getEmploymentText(employee) }}
                </td>
                <td class="border px-2 py-1 capitalize">{{ employee.status }}</td>
                <td class="border px-2 py-1">
                  <Link
                    :href="route(`${prefix}.employees.schedule`, { employee: employee.id })"
                    class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 inline-block"
                  >
                    <span class="fa fa-eye"></span>
                    View Schedule
                  </Link>
                </td>
              </tr>

              <tr v-if="filteredEmployees.length === 0">
                <td colspan="6" class="text-center py-6 text-gray-400">
                  No employees found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useEmployeeSchedulesIndex } from '@/Composables/employees/useEmployeeSchedulesIndex'

const {
  prefix,
  search,
  startDate,
  endDate,
  filteredEmployees,
  getEmploymentText,
  printAllSchedules,
} = useEmployeeSchedulesIndex()
</script>