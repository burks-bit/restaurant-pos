<script setup>
import { ref, computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'

const page = usePage()
const user = page.props?.auth?.user || null
const role = computed(() => user?.role ?? null)

// Collapsible states
const posOpen = ref(false)
const pettyCashOpen = ref(false)
const postSaleOpen = ref(false)
const postExpenseOpen = ref(false)
const reportsOpen = ref(false)
const biometrixOpen = ref(false)
</script>

<template>
  <template v-if="role === 2">
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
          <span>Expenses</span>
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

      <!-- PETTY CASH -->
      <!-- <button
        @click="pettyCashOpen = !pettyCashOpen"
        type="button"
        class="flex items-center w-full justify-between px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand"
      >
        <div class="flex items-center space-x-2">
          <i class="fa fa-wallet"></i>
          <span>Petty Cash</span>
        </div>
        <svg :class="['w-4 h-4 transition-transform', pettyCashOpen ? 'rotate-180' : '']" viewBox="0 0 24 24" fill="none">
          <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
        </svg>
      </button>

      <transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 max-h-0" enter-to-class="opacity-100 max-h-20" leave-active-class="transition-all duration-200" leave-from-class="opacity-100 max-h-20" leave-to-class="opacity-0 max-h-0">
        <ul v-show="pettyCashOpen" class="overflow-hidden space-y-1">
          <li><Link :href="route('cashier.petty-cashes')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"><i class="fa fa-money-bill"></i><span>Petty Cash</span></Link></li>
        </ul>
      </transition> -->

      <!-- REPORTS -->
      <!-- <button
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
            <Link :href="route('cashier.sales.report.index')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-calendar-day"></i>
              <span>Sales Report</span>
            </Link>
          </li>
        </ul>
      </transition> -->

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
            <Link :href="route('cashier.myrecords')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
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
      <ResponsiveNavLink :href="route('cashier.pos')">POS</ResponsiveNavLink>
      <ResponsiveNavLink :href="route('cashier.orders')">Orders</ResponsiveNavLink>
    </div>
  </template>
</template>