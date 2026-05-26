<script setup>
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
</script>

<template>
  <!-- Desktop Links -->
  <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
    <NavLink :href="route('frontdoor.table_occupancies')">Table Occupany</NavLink>
  </div>

  <!-- Mobile Links -->
  <div class="space-y-1 sm:hidden">
    <!-- <ResponsiveNavLink :href="route('frontdoor.pos')">POS</ResponsiveNavLink> -->
  </div>
</template>
