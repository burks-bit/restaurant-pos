<template>
  <AuthenticatedLayout>
    <div class="p-6">

      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Payroll List</h1>
      </div>

      <div
        v-if="$page.props.flash?.success"
        class="mb-4 p-3 bg-green-100 text-green-700 rounded"
      >
        {{ $page.props.flash.success }}
      </div>

      <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full text-sm text-gray-700">
          <thead class="bg-gray-100 text-xs uppercase tracking-wider">
            <tr>
              <th class="px-4 py-3 text-left">Cutoff Start</th>
              <th class="px-4 py-3 text-left">Cutoff End</th>
              <th class="px-4 py-3 text-left">Status</th>
              <th class="px-4 py-3 text-left">Employees</th>
              <th class="px-4 py-3 text-left">Created</th>
              <th class="px-4 py-3 text-right">Action</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="payroll in payrolls"
              :key="payroll.id"
              class="border-t hover:bg-gray-50"
            >
              <td class="px-4 py-3">
                {{ formatDate(payroll.cutoff_start) }}
              </td>
              <td class="px-4 py-3">
                {{ formatDate(payroll.cutoff_end) }}
              </td>
              <td class="px-4 py-3">
                <span
                  class="px-2 py-1 rounded text-xs font-medium"
                  :class="statusBadge(payroll.status)"
                >
                  {{ payroll.status }}
                </span>
              </td>
              <td class="px-4 py-3">
                {{ payroll.items_count }}
              </td>
              <td class="px-4 py-3">
                {{ formatDate(payroll.created_at) }}
              </td>
              <td class="px-4 py-3 text-right">
                <Link
                  :href="route(`${prefix}.payroll.view-payroll-details`, { payroll: payroll.id })"
                  class="bg-blue-600 px-2 py-1 rounded text-white hover:bg-blue-700 text-sm"
                >
                  <span class="fa fa-eye"></span>
                  View
                </Link>
              </td>
            </tr>

            <tr v-if="!payrolls.length">
              <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                No payroll records found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Link } from '@inertiajs/vue3'
import { usePayrollList } from '@/Composables/payroll/usePayrollList'

defineProps({
  payrolls: Array
})

const {
  prefix,
  formatDate,
  statusBadge,
} = usePayrollList()
</script>