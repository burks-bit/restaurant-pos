<script setup>
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
</script>

<template>
  <!-- Desktop Links -->
  <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
    <NavLink :href="route('cashier.pos')">POS</NavLink>
    <NavLink :href="route('cashier.orders')">Orders</NavLink>
  </div>

  <!-- Mobile Links -->
  <div class="space-y-1 sm:hidden">
    <ResponsiveNavLink :href="route('cashier.pos')">POS</ResponsiveNavLink>
    <ResponsiveNavLink :href="route('cashier.orders')">Orders</ResponsiveNavLink>
  </div>
</template>
