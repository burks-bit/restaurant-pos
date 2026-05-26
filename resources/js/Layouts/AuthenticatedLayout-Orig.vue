<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

import AdminNavLinks from '../Components/NavLinks/AdminNavLinks.vue';
import ManagerNavLinks from '../Components/NavLinks/ManagerNavLinks.vue';
import CashierNavLinks from '../Components/NavLinks/CashierNavLinks.vue';
import FrontDoorNavLinks from '../Components/NavLinks/FrontdoorNavLinks.vue';
import PurchaserNavLinks from '../Components/NavLinks/PurchaserNavLinks.vue';
import KitchenNavLinks from '../Components/NavLinks/KitchenNavLinks.vue';
import FinanceNavLinks from '../Components/NavLinks/FinanceNavLinks.vue';
import HrNavLinks from '../Components/NavLinks/HRNavLinks.vue';
import EmployeeNavLinks from '../Components/NavLinks/EmployeeNavLinks.vue';

const page = usePage();
const user = page.props?.auth?.user || null;
const role = computed(() => user?.role ?? null);

const showingNavigationDropdown = ref(false);

// Map role number to component
const NAV_COMPONENTS = {
  0: AdminNavLinks,
  1: ManagerNavLinks,
  2: CashierNavLinks,
  3: FrontDoorNavLinks,
  4: PurchaserNavLinks,
  5: KitchenNavLinks,
  6: FinanceNavLinks,
  7: HrNavLinks,
  8: EmployeeNavLinks,
};

const currentNav = computed(() => NAV_COMPONENTS[role.value] ?? null);
</script>


<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Navbar -->
    <nav class="border-b border-gray-100 bg-white">
      <!-- <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"> --> <!-- orig-->
      <div class="mx-auto max-w-7xl">
        <div class="flex h-16 justify-between items-center">
          <!-- Logo -->
          <div class="flex items-center">
            <Link :href="route('dashboard')">
              <ApplicationLogo class="h-9 w-auto fill-current text-gray-800" />
            </Link>

            <div class="ml-6 flex space-x-4">
              <component :is="currentNav" v-if="currentNav" />
            </div>
          </div>

          <!-- User Dropdown -->
          <div class="hidden sm:flex sm:items-center sm:ml-6">
            <Dropdown align="right" width="48">
              <template #trigger>
                <button
                  class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none"
                >
                  {{ user.name }}
                  <svg
                    class="ml-2 h-4 w-4"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </button>
              </template>

              <template #content>
                <!-- <DropdownLink :href="route('profile.edit')">Profile</DropdownLink> -->
                <DropdownLink :href="route('logout')" method="post" as="button">
                  Log Out
                </DropdownLink>
              </template>
            </Dropdown>
          </div>

          <!-- Hamburger for mobile -->
          <div class="-mr-2 flex items-center sm:hidden">
            <button
              @click="showingNavigationDropdown = !showingNavigationDropdown"
              class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none"
            >
              <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path
                  :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 6h16M4 12h16M4 18h16"
                />
                <path
                  :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Menu -->
      <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
        <div class="space-y-1 pb-3 pt-2">
          <!-- Add mobile nav links here -->
        </div>

        <div class="border-t border-gray-200 pb-1 pt-4">
          <div class="px-4">
            <div class="text-base font-medium text-gray-800">{{ user.name }}</div>
            <div class="text-sm font-medium text-gray-500">{{ user.email }}</div>
          </div>

          <div class="mt-3 space-y-1">
            <!-- <ResponsiveNavLink :href="route('profile.edit')">Profile</ResponsiveNavLink> -->
            <ResponsiveNavLink :href="route('logout')" method="post" as="button">Log Out</ResponsiveNavLink>
          </div>
        </div>
      </div>
    </nav>

    <!-- Header -->
    <header v-if="$slots.header" class="bg-white shadow">
      <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <slot name="header" />
      </div>
    </header>

    <!-- Page Content -->
    <main class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <div class="overflow-x-auto">
        <slot />
      </div>
    </main>

    <!-- Footer -->
    <footer class="p-4 text-center text-gray-500 text-sm border-t border-gray-200">
      © 2026 Hapag sa Balai. All rights reserved.
    </footer>
  </div>
</template>
