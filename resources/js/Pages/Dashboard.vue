<script setup>
import { computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, usePage } from '@inertiajs/vue3'

const page = usePage()
const user = computed(() => page.props?.auth?.user ?? null)
const role = computed(() => user.value?.role ?? null)

const ROLE_LABELS = {
  0: 'Administrator',
  1: 'Manager',
  2: 'Cashier',
  3: 'Front Desk',
  4: 'Purchaser',
  5: 'Kitchen',
  6: 'Finance',
  7: 'Human Resources',
}

const roleName = computed(() => ROLE_LABELS[role.value] ?? 'User')

/*
|--------------------------------------------------------------------------
| ROLE COLOR THEMES
|--------------------------------------------------------------------------
*/

const ROLE_COLORS = {
  0: 'from-red-500 to-red-600',
  1: 'from-blue-500 to-blue-600',
  2: 'from-green-500 to-green-600',
  3: 'from-purple-500 to-purple-600',
  4: 'from-orange-500 to-orange-600',
  5: 'from-yellow-500 to-yellow-600',
  6: 'from-indigo-500 to-indigo-600',
  7: 'from-pink-500 to-pink-600',
}

const roleGradient = computed(() => ROLE_COLORS[role.value] ?? 'from-gray-500 to-gray-600')

/*
|--------------------------------------------------------------------------
| ROLE FUNCTIONS WITH ICONS
|--------------------------------------------------------------------------
*/

const ROLE_FUNCTIONS = {
  0: [
    { icon: 'fa-solid fa-cash-register', text: 'Access POS and Orders Management' },
    { icon: 'fa-solid fa-users-gear', text: 'Manage System Accounts and User Roles' },
    { icon: 'fa-solid fa-table', text: 'Manage Tables and Seating Configuration' },
    { icon: 'fa-solid fa-code-branch', text: 'Manage Branches' },
    { icon: 'fa-solid fa-tags', text: 'Manage Head Pricing Rules' },
    { icon: 'fa-solid fa-layer-group', text: 'Manage Inventory Categories' },
    { icon: 'fa-solid fa-boxes-stacked', text: 'Access Inventory Items and Summary' },
    { icon: 'fa-solid fa-receipt', text: 'Manage Expense Categories' },
    { icon: 'fa-solid fa-chart-pie', text: 'Access Inventory, Expense, and Sales Reports' },
    { icon: 'fa-solid fa-fingerprint', text: 'Biometric Access (Clock In/Out, View Schedule, File Overtime)' },
  ],

  1: [
    { icon: 'fa-solid fa-cash-register', text: 'Access POS and Orders' },
    { icon: 'fa-solid fa-tags', text: 'Manage Head Pricing Rules' },
    { icon: 'fa-solid fa-ticket', text: 'Manage Gift Vouchers' },
    { icon: 'fa-solid fa-calendar-days', text: 'Manage Employee Scheduling' },
    { icon: 'fa-solid fa-check-circle', text: 'Approve or Reject Overtime Requests' },
    { icon: 'fa-solid fa-boxes-stacked', text: 'Access Inventory Items and Summary' },
    { icon: 'fa-solid fa-wallet', text: 'Access Petty Cash Records' },
    { icon: 'fa-solid fa-percent', text: 'Approve Discounts, Vouchers, and Voids' },
    { icon: 'fa-solid fa-chart-pie', text: 'Access Inventory, Expense, and Sales Reports' },
    { icon: 'fa-solid fa-fingerprint', text: 'Biometric Access (Clock In/Out, View Schedule, File Overtime)' },
  ],

  2: [
    { icon: 'fa-solid fa-cash-register', text: 'Operate POS (Post Charges and Print Receipts)' },
    { icon: 'fa-solid fa-ban', text: 'Cancel Orders (with authorization)' },
    { icon: 'fa-solid fa-wallet', text: 'Access Petty Cash Records' },
    { icon: 'fa-solid fa-chart-line', text: 'Access Sales Reports' },
    { icon: 'fa-solid fa-fingerprint', text: 'Biometric Access (Clock In/Out, View Schedule, File Overtime)' },
  ],

  3: [
    { icon: 'fa-solid fa-chair', text: 'Assign Tables and Record Guest Details (Name and Pax)' },
    { icon: 'fa-solid fa-door-open', text: 'Update Table Availability Status' },
    { icon: 'fa-solid fa-clock', text: 'Monitor Table Duration of Stay' },
    { icon: 'fa-solid fa-fingerprint', text: 'Biometric Access (Clock In/Out, View Schedule, File Overtime)' },
  ],

  4: [
    { icon: 'fa-solid fa-boxes-stacked', text: 'Access Inventory Items and Stock Summary' },
    { icon: 'fa-solid fa-fingerprint', text: 'Biometric Access (Clock In/Out, View Schedule, File Overtime)' },
  ],

  5: [
    { icon: 'fa-solid fa-utensils', text: 'Access Inventory Items and Stock Summary' },
    { icon: 'fa-solid fa-fingerprint', text: 'Biometric Access (Clock In/Out, View Schedule, File Overtime)' },
  ],

  6: [
    { icon: 'fa-solid fa-users', text: 'Access Employee Records' },
    { icon: 'fa-solid fa-file-invoice-dollar', text: 'Generate Payroll via DTR Page' },
    { icon: 'fa-solid fa-boxes-stacked', text: 'Access Inventory Items and Summary' },
    { icon: 'fa-solid fa-receipt', text: 'Access Expense Summary' },
    { icon: 'fa-solid fa-chart-pie', text: 'Access Inventory, Expense, and Sales Reports' },
    { icon: 'fa-solid fa-fingerprint', text: 'Biometric Access (Clock In/Out, View Schedule, File Overtime)' },
  ],

  7: [
    { icon: 'fa-solid fa-user-pen', text: 'Manage Employee Records (Create, Update, 201 File, View/Print DTR)' },
    { icon: 'fa-solid fa-file-invoice-dollar', text: 'Generate Payroll via DTR Page' },
    { icon: 'fa-solid fa-calendar-days', text: 'Manage Employee Scheduling' },
    { icon: 'fa-solid fa-check-circle', text: 'Approve or Reject Overtime Requests' },
    { icon: 'fa-solid fa-users-gear', text: 'Manage System Accounts' },
    { icon: 'fa-solid fa-fingerprint', text: 'Biometric Access (Clock In/Out, View Schedule, File Overtime)' },
  ],
}

const functions = computed(() => ROLE_FUNCTIONS[role.value] ?? [])
</script>

<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout>
    <template #header>
      <div
        class="p-6 rounded-xl text-white shadow-lg bg-gradient-to-r"
        :class="roleGradient"
      >
        <h2 class="text-2xl font-bold">
          {{ roleName }} Dashboard
        </h2>
        <p class="text-sm opacity-90 mt-1">
          Overview of your system permissions and access controls.
        </p>
      </div>
    </template>

    <div class="mt-8">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="(item, index) in functions"
          :key="index"
          class="bg-white rounded-xl shadow-md hover:shadow-2xl transition duration-300 p-5 flex items-start space-x-4 opacity-0 animate-fadeIn"
          :style="{ animationDelay: `${index * 100}ms` }"
        >
          <div
            class="text-white p-3 rounded-lg bg-gradient-to-r"
            :class="roleGradient"
          >
            <i :class="item.icon"></i>
          </div>

          <div>
            <p class="text-gray-800 font-medium leading-relaxed">
              {{ item.text }}
            </p>
          </div>
        </div>
      </div>

      <div v-if="functions.length === 0" class="mt-6 text-gray-500">
        No assigned permissions for this role.
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style>
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(15px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadeIn {
  animation: fadeInUp 0.5s ease forwards;
}
</style>