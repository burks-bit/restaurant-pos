<script setup>
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
</script>

<template>
  <!-- ================= DESKTOP ================= -->
  <div class="hidden sm:flex sm:items-center sm:ml-6 space-x-4">

    <!-- POS ACCESS -->
    <Dropdown align="left" width="48">
      <template #trigger>
        <button
          class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none transition"
        >
          POS Access
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
        <DropdownLink :href="route('manager.pos')">
          POS
        </DropdownLink>

        <DropdownLink :href="route('manager.orders')">
          Orders
        </DropdownLink>
      </template>
    </Dropdown>


    <!-- MANAGEMENT -->
    <Dropdown align="left" width="56">
      <template #trigger>
        <button
          class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none transition"
        >
          Management
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
        <DropdownLink :href="route('manager.menus')">
          Menus
        </DropdownLink>

        <DropdownLink :href="route('manager.vouchers.index')">
          Gift Vouchers
        </DropdownLink>

        <DropdownLink :href="route('manager.head-pricing-rules.index')">
          Head Pricing
        </DropdownLink>
      </template>
    </Dropdown>

    <!-- EMPLOYEE -->
    <Dropdown align="left" width="56">
      <template #trigger>
        <button
          class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none transition"
        >
          Employee
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
        <DropdownLink :href="route('manager.employees.index')">
          Schedules
        </DropdownLink>
      </template>
    </Dropdown>


    <!-- INVENTORY -->
    <Dropdown align="left" width="48">
      <template #trigger>
        <button
          class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none transition"
        >
          Inventory
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
        <DropdownLink :href="route('manager.inventory.items')">
          Inventory Items
        </DropdownLink>

        <DropdownLink :href="route('manager.inventory')">
          Inventory Summary
        </DropdownLink>
      </template>
    </Dropdown>

    <!-- PETTY CASH -->
    <Dropdown align="left" width="48">
      <template #trigger>
        <button
          class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none transition"
        >
          Petty Cash
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
        <DropdownLink :href="route('manager.petty-cashes')">
          Petty Cash
        </DropdownLink>
      </template>
    </Dropdown>

  </div>

  <!-- ================= MOBILE ================= -->
  <div class="sm:hidden space-y-1 mt-2">

    <ResponsiveNavLink :href="route('manager.pos')">POS</ResponsiveNavLink>
    <ResponsiveNavLink :href="route('manager.orders')">Orders</ResponsiveNavLink>
    <ResponsiveNavLink :href="route('manager.menus')">Menus</ResponsiveNavLink>
    <!-- <ResponsiveNavLink :href="route('manager.ingredients')">Ingredients</ResponsiveNavLink>
    <ResponsiveNavLink :href="route('manager.inventory')">Inventory</ResponsiveNavLink>
    <ResponsiveNavLink :href="route('manager.users')">Accounts</ResponsiveNavLink> -->

  </div>
</template>
