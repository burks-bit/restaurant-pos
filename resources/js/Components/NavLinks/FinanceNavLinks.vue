<script setup>
import { computed, ref } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'

const page = usePage()
const user = page.props?.auth?.user || null
const role = computed(() => user?.role ?? null)

// Collapsible states
const employeeOpen = ref(false)
const dtrOpen = ref(false)
const inventoryOpen = ref(false)
const expensesOpen = ref(false)
const reportsOpen = ref(false)
const biometrixOpen = ref(false)

const posOpen = ref(false)
const postSaleOpen = ref(false)
const postExpenseOpen = ref(false)

</script>

<template>
  <template v-if="role === 6">
    <div class="space-y-2">

      <!-- DASHBOARD -->
      <Link :href="route('dashboard')" class="flex items-center w-full px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand space-x-2">
        <i class="fa fa-tachometer-alt"></i>
        <span>Dashboard</span>
      </Link>

      <!-- POS -->
      <button @click="posOpen = !posOpen" type="button"
        class="flex items-center w-full justify-between px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand">
        <div class="flex items-center space-x-2">
          <i class="fa fa-cash-register"></i>
          <span>POS Access</span>
        </div>
        <svg :class="['w-4 h-4 transition-transform', posOpen ? 'rotate-180' : '']" viewBox="0 0 24 24" fill="none">
          <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
        </svg>
      </button>

      <transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 max-h-0" enter-to-class="opacity-100 max-h-40" leave-active-class="transition-all duration-200" leave-from-class="opacity-100 max-h-40" leave-to-class="opacity-0 max-h-0">
        <ul v-show="posOpen" class="overflow-hidden space-y-1">
          <li>
            <Link :href="route('cashier.pos')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-money-bill-wave"></i>
              <span>POS</span>
            </Link>
          </li>
          <li>
            <Link :href="route('cashier.pos-items')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-money-bill-wave"></i>
              <span>Single Ordering</span>
            </Link>
          </li>
          <!-- <li>
            <Link :href="route('cashier.orders')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-list"></i>
              <span>Orders</span>
            </Link>
          </li> -->
        </ul>
      </transition>

      <!-- POST EMERGENCY EXPENSE -->
      <button
        @click="postExpenseOpen = !postExpenseOpen"
        type="button"
        class="flex items-center w-full justify-between px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand"
      >
        <div class="flex items-center space-x-2">
          <i class="fa fa-dollar"></i>
          <span>Cashier Expenses</span>
        </div>
        <svg :class="['w-4 h-4 transition-transform', postExpenseOpen ? 'rotate-180' : '']" viewBox="0 0 24 24" fill="none">
          <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
        </svg>
      </button>

      <transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 max-h-0" enter-to-class="opacity-100 max-h-20" leave-active-class="transition-all duration-200" leave-from-class="opacity-100 max-h-20" leave-to-class="opacity-0 max-h-0">
        <ul v-show="postExpenseOpen" class="overflow-hidden space-y-1">
          <li><Link :href="route('cashier.cashier_expenses')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"><i class="fa fa-coins"></i><span>Post Expense</span></Link></li>
        </ul>
      </transition>

      <!-- POST SALES -->
      <button
        @click="postSaleOpen = !postSaleOpen"
        type="button"
        class="flex items-center w-full justify-between px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand"
      >
        <div class="flex items-center space-x-2">
          <i class="fa fa-wallet"></i>
          <span>Post Sales</span>
        </div>
        <svg :class="['w-4 h-4 transition-transform', postSaleOpen ? 'rotate-180' : '']" viewBox="0 0 24 24" fill="none">
          <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
        </svg>
      </button>

      <transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 max-h-0" enter-to-class="opacity-100 max-h-20" leave-active-class="transition-all duration-200" leave-from-class="opacity-100 max-h-20" leave-to-class="opacity-0 max-h-0">
        <ul v-show="postSaleOpen" class="overflow-hidden space-y-1">
          <li><Link :href="route('cashier.cash-registers')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"><i class="fa fa-money-bill"></i><span>Cash Register</span></Link></li>
        </ul>
      </transition>

      <!-- EMPLOYEE -->
      <button @click="employeeOpen = !employeeOpen" type="button"
        class="flex items-center w-full justify-between px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand">
        <div class="flex items-center space-x-2">
          <i class="fa fa-user"></i>
          <span>Employee</span>
        </div>
        <svg :class="['w-4 h-4 transition-transform', employeeOpen ? 'rotate-180' : '']" viewBox="0 0 24 24" fill="none">
          <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
        </svg>
      </button>
      <transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 max-h-0" enter-to-class="opacity-100 max-h-40" leave-active-class="transition-all duration-200" leave-from-class="opacity-100 max-h-40" leave-to-class="opacity-0 max-h-0">
        <ul v-show="employeeOpen" class="overflow-hidden space-y-1">
          <li>
            <Link :href="route('finance.employees.index')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-id-badge"></i>
              <span>Employees</span>
            </Link>
          </li>
        </ul>
      </transition>

      <!-- DAILY TIME RECORD -->
      <button
        @click="dtrOpen = !dtrOpen"
        type="button"
        class="flex items-center w-full justify-between px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand"
      >
        <div class="flex items-center space-x-2">
          <i class="fa fa-money-check-dollar"></i>
          <span>Payroll</span>
        </div>

        <svg :class="['w-4 h-4 transition-transform', dtrOpen ? 'rotate-180' : '']"
          viewBox="0 0 24 24" fill="none">
          <path stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round"
            d="m19 9-7 7-7-7"/>
        </svg>
      </button>

      <transition
        enter-active-class="transition-all duration-300"
        enter-from-class="opacity-0 max-h-0"
        enter-to-class="opacity-100 max-h-40"
        leave-active-class="transition-all duration-200"
        leave-from-class="opacity-100 max-h-40"
        leave-to-class="opacity-0 max-h-0"
      >
        <ul v-show="dtrOpen" class="overflow-hidden space-y-1">
          
          <li>
            <Link
              :href="route('finance.generate-payroll')" 
              class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"
            >
              <i class="fa fa-calculator"></i>
              <span>Generate Payroll</span>
            </Link>
          </li>

          <li>
            <Link
              :href="route('finance.payroll.index')" 
              class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"
            >
              <i class="fa fa-file-invoice-dollar"></i>
              <span>Payroll Index</span>
            </Link>
          </li>

        </ul>
      </transition>

      <!-- INVENTORY -->
      <button @click="inventoryOpen = !inventoryOpen" type="button"
        class="flex items-center w-full justify-between px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand">
        <div class="flex items-center space-x-2">
          <i class="fa fa-boxes"></i>
          <span>Inventory</span>
        </div>
        <svg :class="['w-4 h-4 transition-transform', inventoryOpen ? 'rotate-180' : '']" viewBox="0 0 24 24" fill="none">
          <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
        </svg>
      </button>
      <transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 max-h-0" enter-to-class="opacity-100 max-h-60" leave-active-class="transition-all duration-200" leave-from-class="opacity-100 max-h-60" leave-to-class="opacity-0 max-h-0">
        <ul v-show="inventoryOpen" class="overflow-hidden space-y-1">
          <li>
            <Link :href="route('finance.inventory.items')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-box"></i>
              <span>Inventory Items</span>
            </Link>
          </li>
          <li>
            <Link :href="route('finance.inventory')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-list-alt"></i>
              <span>Inventory Summary</span>
            </Link>
          </li>
        </ul>
      </transition>

      
      <Link :href="route('frontdoor.reservations.index')" class="flex items-center w-full px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand space-x-2">
        <i class="fa fa-list"></i>
        <span>Reservations</span>
      </Link>

      <!-- EXPENSES -->
      <button @click="expensesOpen = !expensesOpen" type="button"
        class="flex items-center w-full justify-between px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand">
        <div class="flex items-center space-x-2">
          <i class="fa fa-file-invoice-dollar"></i>
          <span>Expenses</span>
        </div>
        <svg :class="['w-4 h-4 transition-transform', expensesOpen ? 'rotate-180' : '']" viewBox="0 0 24 24" fill="none">
          <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
        </svg>
      </button>
      <transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 max-h-0" enter-to-class="opacity-100 max-h-40" leave-active-class="transition-all duration-200" leave-from-class="opacity-100 max-h-40" leave-to-class="opacity-0 max-h-0">
        <ul v-show="expensesOpen" class="overflow-hidden space-y-1">
          <li>
            <Link :href="route('finance.expenses')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-money-bill"></i>
              <span>Expenses</span>
            </Link>
          </li>
        </ul>
      </transition>

      <!-- REPORTS -->
      <button
        @click="reportsOpen = !reportsOpen"
        type="button"
        class="flex items-center w-full justify-between px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand"
      >
        <div class="flex items-center space-x-2">
          <i class="fa fa-chart-line"></i>
          <span>Reports</span>
        </div>
        <svg :class="['w-4 h-4 transition-transform', reportsOpen ? 'rotate-180' : '']"
          viewBox="0 0 24 24" fill="none">
          <path stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round"
            d="m19 9-7 7-7-7"/>
        </svg>
      </button>

      <transition
        enter-active-class="transition-all duration-300"
        enter-from-class="opacity-0 max-h-0"
        enter-to-class="opacity-100 max-h-60"
        leave-active-class="transition-all duration-200"
        leave-from-class="opacity-100 max-h-60"
        leave-to-class="opacity-0 max-h-0"
      >
        <ul v-show="reportsOpen" class="overflow-hidden space-y-1">
          
          <li>
            <Link :href="route('finance.sales.report.index')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-calendar-day"></i>
              <span>Sales Report</span>
            </Link>
          </li>
          <li>
            <Link :href="route('finance.inventory.report.index')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-boxes"></i>
              <span>Inventory Report </span>
            </Link>
          </li>
          <li>
            <Link :href="route('finance.expenses.report.index')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-file-invoice-dollar"></i>
              <span>Expenses Report</span>
            </Link>
          </li>
          <!-- <li>
            <Link :href="route('finance.expenses')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-money-bill-wave"></i>
              <span>Petty Cash Report</span>
            </Link>
          </li> -->
        </ul>
      </transition>

      <!-- BIOMETRIX -->
      <button
        @click="biometrixOpen = !biometrixOpen"
        type="button"
        class="flex items-center w-full justify-between px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand"
      >
        <div class="flex items-center space-x-2">
          <i class="fa fa-fingerprint"></i>
          <span>My Biometrix</span>
        </div>
        <svg :class="['w-4 h-4 transition-transform', biometrixOpen ? 'rotate-180' : '']"
          viewBox="0 0 24 24" fill="none">
          <path stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round"
            d="m19 9-7 7-7-7"/>
        </svg>
      </button>

      <transition
        enter-active-class="transition-all duration-300"
        enter-from-class="opacity-0 max-h-0"
        enter-to-class="opacity-100 max-h-60"
        leave-active-class="transition-all duration-200"
        leave-from-class="opacity-100 max-h-60"
        leave-to-class="opacity-0 max-h-0"
      >
        <ul v-show="biometrixOpen" class="overflow-hidden space-y-1">
          
          <li>
            <Link :href="route('finance.myrecords')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-calendar-day"></i>
              <span>My Biometix</span>
            </Link>
          </li>
        </ul>
      </transition>

      <Link href="/profile" class="flex items-center w-full px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand space-x-2">
        <i class="fa fa-key"></i>
        <span>Reset Password</span>
      </Link>

    </div>

    <!-- MOBILE LINKS -->
    <div class="sm:hidden space-y-1 mt-4">
      <ResponsiveNavLink :href="route('dashboard')">Dashboard</ResponsiveNavLink>
      <ResponsiveNavLink :href="route('finance.employees.index')">Employees</ResponsiveNavLink>
      <ResponsiveNavLink :href="route('finance.generate-payroll')">DTR</ResponsiveNavLink>
      <ResponsiveNavLink :href="route('finance.inventory')">Inventory Summary</ResponsiveNavLink>
      <ResponsiveNavLink :href="route('finance.expenses')">Expenses</ResponsiveNavLink>
    </div>
  </template>
</template>