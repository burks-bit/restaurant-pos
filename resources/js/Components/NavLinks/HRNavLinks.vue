<script setup>
import { computed, ref } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'

const props = defineProps({
  menu: Boolean
})

const emit = defineEmits(['toggle'])

const page = usePage()
const user = page.props?.auth?.user || null
const role = computed(() => user?.role ?? null)

// Collapsible states
const employeeOpen = ref(false)
const dtrOpen = ref(false)
const biometrixOpen = ref(false)

// Toggle parent-controlled sidebar
const toggle = () => {
  emit('toggle')
}
</script>

<template>
  <template v-if="role === 7">

    <div class="space-y-2">

      <!-- DASHBOARD -->
      <Link :href="route('dashboard')" 
        class="flex items-center w-full px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand space-x-2">
        <i class="fa fa-tachometer-alt"></i>
        <span>Dashboard</span>
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
              <Link :href="route('hr.employees.daily-logs')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
                <i class="fa fa-clock"></i>
                <span>Logs</span>
              </Link>
          </li>
          <li>
            <Link :href="route('hr.employees.index')" 
              class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-user"></i>
              <span>Employees</span>
            </Link>
          </li>
          <!-- <li>
              <Link :href="route('hr.employees.overtimes')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
                <i class="fa fa-clock"></i>
                <span>Overtime Requests</span>
              </Link>
          </li>
          <li>
            <Link :href="route('hr.users')" 
              class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-id-badge"></i>
              <span>User Accounts</span>
            </Link>
          </li> -->
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
              :href="route('hr.generate-payroll')" 
              class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"
            >
              <i class="fa fa-calculator"></i>
              <span>Generate Payroll</span>
            </Link>
          </li>

          <li>
            <Link
              :href="route('hr.payroll.index')" 
              class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2"
            >
              <i class="fa fa-file-invoice-dollar"></i>
              <span>Payroll Index</span>
            </Link>
          </li>

        </ul>
      </transition>

      <!-- MY BIOMETRIX (Optional) -->
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
        enter-to-class="opacity-100 max-h-40"
        leave-active-class="transition-all duration-200"
        leave-from-class="opacity-100 max-h-40"
        leave-to-class="opacity-0 max-h-0"
      >
        <ul v-show="biometrixOpen" class="overflow-hidden space-y-1">
          <li>
            <Link :href="route('hr.myrecords')" 
              class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-calendar-day"></i>
              <span>My Records</span>
            </Link>
          </li>
        </ul>
      </transition>

      <Link href="/profile" class="flex items-center w-full px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand space-x-2">
        <i class="fa fa-key"></i>
        <span>Reset Password</span>
      </Link>

    </div>

    <!-- MOBILE SIMPLE -->
    <div class="sm:hidden space-y-1 mt-4">
      <ResponsiveNavLink :href="route('dashboard')">Dashboard</ResponsiveNavLink>
      <ResponsiveNavLink :href="route('hr.employees.index')">Employees</ResponsiveNavLink>
      <ResponsiveNavLink :href="route('hr.generate-payroll')">DTR</ResponsiveNavLink>
    </div>

  </template>
</template>