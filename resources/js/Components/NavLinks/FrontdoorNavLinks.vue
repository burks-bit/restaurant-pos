<script setup>
import { computed, ref } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'

const page = usePage()
const user = page.props?.auth?.user || null
const role = computed(() => user?.role ?? null)

// Collapsible states (if any submenus needed in future)
const tableOpen = ref(false)
const biometrixOpen = ref(false)
</script>

<template>
  <template v-if="role === 3">
    <div class="space-y-2">
      <!-- DASHBOARD -->
      <Link :href="route('dashboard')" class="flex items-center w-full px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand space-x-2">
        <i class="fa fa-tachometer-alt"></i>
        <span>Dashboard</span>
      </Link>

      <!-- TABLE OCCUPANCY -->
      <Link :href="route('frontdoor.table_admission')" class="flex items-center w-full px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand space-x-2">
        <i class="fa fa-table"></i>
        <span>Customer Registration</span>
      </Link>
      <Link :href="route('frontdoor.table_occupancies')" class="flex items-center w-full px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand space-x-2">
        <i class="fa fa-table"></i>
        <span>Table Sessions</span>
      </Link>
      <Link :href="route('frontdoor.reservations.index')" class="flex items-center w-full px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand space-x-2">
        <i class="fa fa-list"></i>
        <span>Reservations</span>
      </Link>


      <!-- FUTURE SUBMENU EXAMPLE (if needed) -->
      <!--
      <button
        @click="tableOpen = !tableOpen"
        type="button"
        class="flex items-center w-full justify-between px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand"
      >
        <div class="flex items-center space-x-2">
          <i class="fa fa-table"></i>
          <span>Table Section</span>
        </div>
        <svg :class="['w-4 h-4 transition-transform', tableOpen ? 'rotate-180' : '']" viewBox="0 0 24 24" fill="none">
          <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
        </svg>
      </button>

      <transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 max-h-0" enter-to-class="opacity-100 max-h-40" leave-active-class="transition-all duration-200" leave-from-class="opacity-100 max-h-40" leave-to-class="opacity-0 max-h-0">
        <ul v-show="tableOpen" class="overflow-hidden space-y-1">
          <li>
            <Link :href="route('frontdoor.some_route')" class="pl-8 block px-2 py-1.5 rounded-base hover:bg-gray-200 flex items-center space-x-2">
              <i class="fa fa-square"></i>
              <span>Submenu Item</span>
            </Link>
          </li>
        </ul>
      </transition>
      -->

      <!-- BIOMETRIX -->
      <button
        @click="biometrixOpen = !biometrixOpen"
        type="button"
        class="flex items-center w-full justify-between px-3 py-2 rounded-base hover:bg-gray-200 hover:text-fg-brand"
      >
        <div class="flex items-center space-x-2">
          <i class="fa fa-clock"></i>
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

    <!-- MOBILE SIMPLE LINKS -->
    <div class="sm:hidden space-y-1 mt-4">
      <ResponsiveNavLink :href="route('dashboard')">Dashboard</ResponsiveNavLink>
      <ResponsiveNavLink :href="route('frontdoor.table_occupancies')">Table Occupancy</ResponsiveNavLink>
    </div>
  </template>
</template>