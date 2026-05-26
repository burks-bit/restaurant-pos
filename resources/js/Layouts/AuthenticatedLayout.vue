<script setup>
import axios from 'axios'
import { ref, computed, onMounted } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'

import ApplicationLogo from '@/Components/ApplicationLogo.vue'

import AdminNavLinks from '../Components/NavLinks/AdminNavLinks.vue'
import ManagerNavLinks from '../Components/NavLinks/ManagerNavLinks.vue'
import CashierNavLinks from '../Components/NavLinks/CashierNavLinks.vue'
import FrontDoorNavLinks from '../Components/NavLinks/FrontdoorNavLinks.vue'
import PurchaserNavLinks from '../Components/NavLinks/PurchaserNavLinks.vue'
import KitchenNavLinks from '../Components/NavLinks/KitchenNavLinks.vue'
import FinanceNavLinks from '../Components/NavLinks/FinanceNavLinks.vue'
import HrNavLinks from '../Components/NavLinks/HRNavLinks.vue'

const page = usePage()
const user = page.props?.auth?.user || null

const appName = computed(() => page.props.appName)
const role = computed(() => user?.role ?? null)

const selectedPage = ref('')

const NAV_COMPONENTS = {
  0: AdminNavLinks,
  1: ManagerNavLinks,
  2: CashierNavLinks,
  3: FrontDoorNavLinks,
  4: PurchaserNavLinks,
  5: KitchenNavLinks,
  6: FinanceNavLinks,
  7: HrNavLinks,
  8: KitchenNavLinks, // Assuming cook uses the same nav as kitchen
  9: KitchenNavLinks, // Assuming line cook uses the same nav as kitchen
  10: KitchenNavLinks, // Assuming waiter uses the same nav as kitchen
  11: KitchenNavLinks, // Assuming waitress uses the same nav as kitchen
  12: KitchenNavLinks, // Assuming dishwasher uses the same nav as kitchen
  13: KitchenNavLinks, // Assuming head waiter uses the same nav as kitchen
}

const currentNav = computed(() => NAV_COMPONENTS[role.value] ?? null)

const sidebarOpen = ref(true)

const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
}

const userAccesses = ref([])

const fetchUserAccesses = async () => {
  try {
    const res = await axios.get('/user-accesses')

    userAccesses.value = res.data.user_accesses ?? []
  } catch (err) {
    console.error('Failed to fetch user accesses', err)
  }
}

const hasAccess = (routeName) => {
  return userAccesses.value.some(
    access => access.app_route?.route === routeName
  )
}

const accessiblePages = computed(() => {
  return userAccesses.value
    .filter(access => access.app_route?.route && access.app_route?.route_name)
    .map(access => ({
      label: access.app_route.route_name,
      route: access.app_route.route,
    }))
})

const goToPage = () => {
  if (!selectedPage.value) return

  if (hasAccess(selectedPage.value)) {
    router.visit(route(selectedPage.value))
  }
}

onMounted(() => {
  fetchUserAccesses()
})
</script>

<template>
  <div class="min-h-screen bg-neutral-secondary-soft">
    <div
      v-if="sidebarOpen"
      @click="sidebarOpen = false"
      class="fixed inset-0 z-40 bg-black/40 sm:hidden"
    ></div>

    <aside
      :class="[
        'fixed top-0 left-0 z-50 h-screen w-64 bg-neutral-primary-soft border-e border-default transition-transform duration-300',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full'
      ]"
    >
      <div class="flex h-full flex-col overflow-y-auto px-3 py-4">
        <ul class="space-y-2 font-medium">
          <ApplicationLogo />

          <component
            :is="currentNav"
            v-if="currentNav"
          />
        </ul>

        <div class="mt-auto border-t pl-2 pt-4">
          <Link
            :href="route('logout')"
            method="post"
            as="button"
            class="w-full rounded bg-gray-900 px-2 py-1.5 text-left text-white hover:bg-gray-700"
          >
            <div class="flex items-center space-x-2">
              <i class="fa fa-sign-out"></i>
              <span>Logout</span>
            </div>
          </Link>
        </div>
      </div>
    </aside>

    <div
      :class="[
        'min-h-screen bg-gray-50 transition-all duration-300',
        sidebarOpen ? 'sm:ml-64' : 'sm:ml-0'
      ]"
    >
      <header class="bg-teal-700 shadow-md">
        <div class="flex items-center justify-between px-6 py-4">
          <div class="flex items-center gap-4">
            <button
              @click="toggleSidebar"
              class="inline-flex items-center justify-center rounded-md p-2 text-white hover:bg-teal-800 focus:outline-none"
            >
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path
                  d="M4 6h16M4 12h16M4 18h16"
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                />
              </svg>
            </button>

            <h1 class="text-2xl font-bold tracking-wide text-white">
              {{ appName }}
            </h1>
          </div>

          <div class="flex items-center gap-3">
            <div class="text-sm font-medium text-white whitespace-nowrap">
              Logged in as {{ user?.name }}
            </div>

            <select
              v-model="selectedPage"
              @change="goToPage"
              class="rounded-md border border-white/30 bg-white px-3 py-1 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-white"
            >
              <option value="">Select accessible page</option>
              <option
                v-for="item in accessiblePages"
                :key="item.route"
                :value="item.route"
              >
                {{ item.label }} — {{ item.route }}
              </option>
            </select>
          </div>
        </div>
      </header>

      <div v-if="$slots.header" class="border-b bg-white px-6 py-5">
        <slot name="header" />
      </div>

      <main class="p-6">
        <slot />
      </main>
    </div>
  </div>
</template>