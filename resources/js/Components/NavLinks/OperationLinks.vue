<script setup>
import { computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'

const page = usePage()

const role = computed(() => page.props.auth?.user?.role ?? null)
</script>

<template>
  <div class="flex gap-4">
    <template v-if="role === 0">
      <Link href="/dashboard" class="nav-link">Dashboard</Link>
      <Link :href="route('admin.pos')" class="nav-link">POS</Link>
      <Link :href="route('admin.orders')" class="nav-link">Orders</Link>
      <Link :href="route('admin.petty-cashes')" class="nav-link">Petty Cash</Link>
      <Link :href="route('admin.reports')" class="nav-link-active">Reports</Link>
      <Link
          :href="route('logout')"
          method="post"
          as="button"
          class="pl-1 pr-1 bg-red-500 text-xs text-white font-semibold rounded-lg hover:bg-red-600 transition duration-200"
      >
          <span class="fa fa-sign-out"></span> Logout
      </Link>
    </template>

    <template v-else-if="role === 1">
      <Link href="/dashboard" class="nav-link">Dashboard</Link>
      <Link :href="route('manager.pos')" class="nav-link">POS</Link>
      <Link :href="route('manager.orders')" class="nav-link">Orders</Link>
      <Link :href="route('manager.petty-cashes')" class="nav-link">Petty Cash</Link>
      <Link :href="route('manager.reports')" class="nav-link-active">Reports</Link>
      <Link
          :href="route('logout')"
          method="post"
          as="button"
          class="pl-1 pr-1 bg-red-500 text-xs text-white font-semibold rounded-lg hover:bg-red-600 transition duration-200"
      >
          <span class="fa fa-sign-out"></span> Logout
      </Link>
    </template>

    <template v-else-if="role === 2">
      <Link :href="route('cashier.pos')" class="nav-link">POS</Link>
      <Link :href="route('cashier.orders')" class="nav-link">Orders</Link>
      <Link :href="route('cashier.petty-cashes')" class="nav-link">Petty Cash</Link>
      <Link :href="route('cashier.reports')" class="nav-link-active">Reports</Link>
      <Link
          :href="route('logout')"
          method="post"
          as="button"
          class="pl-1 pr-1 bg-red-500 text-xs text-white font-semibold rounded-lg hover:bg-red-600 transition duration-200"
      >
          <span class="fa fa-sign-out"></span> Logout
      </Link>
    </template>

  </div>
</template>

<style scoped>
.nav-link {
  @apply text-sm text-gray-600 hover:underline;
}

.nav-link-active {
  @apply text-sm font-semibold text-blue-600;
}
</style>

