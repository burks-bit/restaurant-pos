<script setup>
import { computed, ref } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'

const props = defineProps({
  menu: Boolean  // parent-controlled boolean
})

const emit = defineEmits(['toggle'])

const page = usePage()
const user = page.props?.auth?.user || null
const role = computed(() => user?.role ?? null)

// Collapsible states (local only for submenus)
const posOpen = ref(false)
const managementOpen = ref(false)
const inventoryOpen = ref(false)
const expensesOpen = ref(false)
const reportsOpen = ref(false)
const biometrixOpen = ref(false)
const dtrOpen = ref(false)
const employeeOpen = ref(false)

// Toggle parent-controlled menu
const toggle = () => {
  emit('toggle') // parent will update the menu state
}
</script>

<template>
  <template v-if="role === 0">

    <div class="space-y-2">
      <!-- DASHBOARD -->
      <Link :href="route('dashboard')" class="flex items-center w-full px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand space-x-2">
        <i class="fa fa-tachometer-alt"></i>
        <span>Dashboard</span>
      </Link>

      <Link :href="route('frontdoor.reservations.index')" class="flex items-center w-full px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand space-x-2">
        <i class="fa fa-list"></i>
        <span>Reservations</span>
      </Link>

      <!-- POS -->
      <button
        @click="posOpen = !posOpen"
        type="button"
        class="flex items-center w-full justify-between px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand"
      >
        <div class="flex items-center space-x-2">
          <i class="fa fa-cash-register"></i>
          <span>POS Access</span>
        </div>
        <svg :class="['w-4 h-4 transition-transform', posOpen ? 'rotate-180' : '']"
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
        <ul v-show="posOpen" class="overflow-hidden space-y-1">
          <li>
            <Link :href="route('admin.pos')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-square"></i>
              <span>POS</span>
            </Link>
          </li>
          <li>
            <Link :href="route('admin.orders')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-list"></i>
              <span>Orders</span>
            </Link>
          </li>
        </ul>
      </transition>

      <!-- MANAGEMENT -->
      <button
        @click="managementOpen = !managementOpen"
        type="button"
        class="flex items-center w-full justify-between px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand"
      >
        <div class="flex items-center space-x-2">
          <i class="fa fa-cogs"></i>
          <span>Management</span>
        </div>
        <svg :class="['w-4 h-4 transition-transform', managementOpen ? 'rotate-180' : '']"
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
        <ul v-show="managementOpen" class="overflow-hidden space-y-1">
          <!-- <li><Link :href="route('admin.menus')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"><i class="fa fa-utensils"></i><span>Menus</span></Link></li> -->
          <li><Link :href="route('admin.users')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"><i class="fa fa-user"></i><span>Accounts</span></Link></li>
          <li><Link :href="route('admin.tables')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"><i class="fa fa-table"></i><span>Tables</span></Link></li>
          <li><Link :href="route('admin.branches')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"><i class="fa fa-building"></i><span>Branches</span></Link></li>
          <li><Link :href="route('admin.head-pricing-rules.index')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"><i class="fa fa-percent"></i><span>Pricing Schemes</span></Link></li>
          <li><Link :href="route('admin.vouchers.index')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"><i class="fa fa-gift"></i><span>Gift Vouchers</span></Link></li>
        </ul>
      </transition>

      <Link href="/admin/configurations" class="flex items-center w-full px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand space-x-2">
        <i class="fa fa-cog"></i>
        <span>Configurations</span>
      </Link>

      <!-- EMPLOYEE -->
      <button
        @click="employeeOpen = !employeeOpen"
        type="button"
        class="flex items-center w-full justify-between px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand"
      >
        <div class="flex items-center space-x-2">
          <i class="fa fa-users"></i>
          <span>Employee</span>
        </div>
        <svg :class="['w-4 h-4 transition-transform', employeeOpen ? 'rotate-180' : '']"
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
        <ul v-show="employeeOpen" class="overflow-hidden space-y-1">
          <li>
            <Link :href="route('admin.employees.index')" 
              class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-user"></i>
              <span>Employees</span>
            </Link>
          </li>
          <!-- <li>
              <Link :href="route('admin.employees.overtimes')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
                <i class="fa fa-clock"></i>
                <span>Overtime Requests</span>
              </Link>
          </li>
          <li>
            <Link :href="route('admin.users')" 
              class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-id-badge"></i>
              <span>User Accounts</span>
            </Link>
          </li> -->
        </ul>
      </transition>

      <!-- INVENTORY -->
      <button
        @click="inventoryOpen = !inventoryOpen"
        type="button"
        class="flex items-center w-full justify-between px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand"
      >
        <div class="flex items-center space-x-2">
          <i class="fa fa-boxes"></i>
          <span>Inventory</span>
        </div>
        <svg :class="['w-4 h-4 transition-transform', inventoryOpen ? 'rotate-180' : '']"
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
        <ul v-show="inventoryOpen" class="overflow-hidden space-y-1">
          <li><Link :href="route('admin.inventory.categories')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"><i class="fa fa-tags"></i><span>Categories</span></Link></li>
          <li><Link :href="route('admin.inventory.items')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"><i class="fa fa-box"></i><span>Inventory Items</span></Link></li>
          <li><Link :href="route('admin.inventory')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"><i class="fa fa-list-alt"></i><span>Inventory Summary</span></Link></li>
        </ul>
      </transition>

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
              :href="route('admin.generate-payroll')" 
              class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"
            >
              <i class="fa fa-calculator"></i>
              <span>Generate Payroll</span>
            </Link>
          </li>

          <li>
            <Link
              :href="route('admin.payroll.index')" 
              class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"
            >
              <i class="fa fa-file-invoice-dollar"></i>
              <span>Payroll Index</span>
            </Link>
          </li>

        </ul>
      </transition>

      <!-- EXPENSES -->
      <button
        @click="expensesOpen = !expensesOpen"
        type="button"
        class="flex items-center w-full justify-between px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand"
      >
        <div class="flex items-center space-x-2">
          <i class="fa fa-file-invoice-dollar"></i>
          <span>Expenses</span>
        </div>
        <svg :class="['w-4 h-4 transition-transform', expensesOpen ? 'rotate-180' : '']"
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
        <ul v-show="expensesOpen" class="overflow-hidden space-y-1">
          <li><Link :href="route('admin.expenses')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"><i class="fa fa-money-bill"></i><span>Expenses</span></Link></li>
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
            <!-- <Link :href="route('admin.reports.daily-sales')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"> -->
            <Link :href="route('admin.sales.report.index')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-calendar-day"></i>
              <span>Sales Report</span>
            </Link>
          </li>
          <li>
            <!-- <Link :href="route('admin.reports.stocks.inventory')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"> -->
            <Link :href="route('admin.inventory.report.index')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-boxes"></i>
              <span>Inventory Report </span>
            </Link>
          </li>
          <li>
            <!-- <Link :href="route('admin.reports.stocks.expenses')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"> -->
            <Link :href="route('admin.expenses.report.index')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-file-invoice-dollar"></i>
              <span>Expenses Report</span>
            </Link>
          </li>
          <li>
            <!-- <Link :href="route('admin.reports.petty-cash')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"> -->
            <Link :href="route('admin.expenses')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-money-bill-wave"></i>
              <span>Petty Cash Report</span>
            </Link>
          </li>
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
            <!-- <Link :href="route('admin.reports.daily-sales')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"> -->
            <Link :href="route('admin.myrecords')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-calendar-day"></i>
              <span>My Biometix</span>
            </Link>
          </li>
        </ul>
      </transition>
      
    </div>

    <!-- MOBILE SIMPLE -->
    <div class="sm:hidden space-y-1 mt-4">
      <ResponsiveNavLink :href="route('dashboard')">Dashboard</ResponsiveNavLink>
      <ResponsiveNavLink :href="route('admin.pos')">POS</ResponsiveNavLink>
      <ResponsiveNavLink :href="route('admin.orders')">Orders</ResponsiveNavLink>
      <!-- <ResponsiveNavLink :href="route('admin.menus')">Menus</ResponsiveNavLink> -->
      <ResponsiveNavLink :href="route('admin.inventory')">Inventory</ResponsiveNavLink>
      <ResponsiveNavLink :href="route('admin.users')">Accounts</ResponsiveNavLink>
    </div>

  </template>
</template>