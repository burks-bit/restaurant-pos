<script setup>
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
</script>

<template>
  <!-- ================= DESKTOP ================= -->
  <div class="hidden sm:flex sm:items-center sm:ml-6 space-x-4">

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
        <DropdownLink :href="route('finance.employees.index')">
          Employees
        </DropdownLink>
      </template>
    </Dropdown>

    <!-- DTR -->
     <Dropdown align="left" width="56">
      <template #trigger>
        <button
          class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none transition"
        >
          Daily Time Record
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
        <DropdownLink :href="route('finance.dtr')">
          DTR
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
        
        <DropdownLink :href="route('finance.inventory.items')">
          Inventory Items
        </DropdownLink>

        <DropdownLink :href="route('finance.inventory')">
          Inventory Summary
        </DropdownLink>
      </template>
    </Dropdown>

    <!-- Expenses -->
    <Dropdown align="left" width="48">
      <template #trigger>
        <button
          class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none transition"
        >
          Expenses
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
        
        <DropdownLink :href="route('finance.expenses')">
          Expenses
        </DropdownLink>
      </template>
    </Dropdown>

  </div>

  <!-- ================= MOBILE ================= -->
  <div class="sm:hidden space-y-1 mt-2">

    <!-- <ResponsiveNavLink :href="route('finance.pos')">POS</ResponsiveNavLink>
    <ResponsiveNavLink :href="route('finance.orders')">Orders</ResponsiveNavLink>
    <ResponsiveNavLink :href="route('finance.menus')">Menus</ResponsiveNavLink> -->
    <!-- <ResponsiveNavLink :href="route('finance.ingredients')">Ingredients</ResponsiveNavLink>
    <ResponsiveNavLink :href="route('finance.inventory')">Inventory</ResponsiveNavLink>
    <ResponsiveNavLink :href="route('finance.users')">Accounts</ResponsiveNavLink> -->

  </div>
</template>
