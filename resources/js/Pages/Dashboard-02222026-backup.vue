<script setup>
import { computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, usePage } from '@inertiajs/vue3'

const page = usePage()

const user = computed(() => page.props?.auth?.user ?? null)
const role = computed(() => user.value?.role ?? null)
const stats = computed(() => page.props?.stats ?? {})

const ROLE_LABELS = {
  0: 'Admin',
  1: 'Manager',
  2: 'Cashier',
  3: 'Front Door',
  4: 'Purchaser',
  5: 'Kitchen',
  6: 'Finance',
  7: 'HR',
}

const roleName = computed(() => ROLE_LABELS[role.value] ?? 'User')
</script>


<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">
        {{ roleName }} Dashboard
      </h2>
    </template>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">

      <!-- Orders Today -->
      <div class="border-2 border-indigo-600 bg-indigo-100 text-indigo-900 p-6 rounded-2xl shadow-lg hover:shadow-2xl transition duration-300">
        <div class="flex justify-between items-center">
          <h3 class="text-sm uppercase tracking-wide opacity-80 font-bold">Orders Today</h3>
          <span class="text-2xl">🧾</span>
        </div>
        <p class="text-4xl font-bold mt-4">{{ stats.orders_today }}</p>
      </div>

      <!-- User Accounts -->
      <div class="border-2 border-emerald-600 bg-emerald-100 text-emerald-900 p-6 rounded-2xl shadow-lg hover:shadow-2xl transition duration-300">
        <div class="flex justify-between items-center">
          <h3 class="text-sm uppercase tracking-wide opacity-80 font-bold">User Accounts</h3>
          <span class="text-2xl">👤</span>
        </div>
        <p class="text-4xl font-bold mt-4">{{ stats.users_count }}</p>
      </div>

      <!-- Ingredients -->
      <div class="border-2 border-amber-600 bg-amber-100 text-amber-900 p-6 rounded-2xl shadow-lg hover:shadow-2xl transition duration-300">
        <div class="flex justify-between items-center">
          <h3 class="text-sm uppercase tracking-wide opacity-80 font-bold">Ingredients</h3>
          <span class="text-2xl">🥬</span>
        </div>
        <p class="text-4xl font-bold mt-4">{{ stats.ingredients_count }}</p>
      </div>

      <!-- Low Stock -->
      <div class="border-2 border-rose-600 bg-rose-100 text-rose-900 p-6 rounded-2xl shadow-lg hover:shadow-2xl transition duration-300">
        <div class="flex justify-between items-center">
          <h3 class="text-sm uppercase tracking-wide opacity-80 font-bold">Low Stock</h3>
          <span class="text-2xl">⚠️</span>
        </div>
        <p class="text-4xl font-bold mt-4">{{ stats.low_stock_count }}</p>
      </div>

      <!-- Menus -->
      <div class="border-2 border-violet-600 bg-violet-100 text-violet-900 p-6 rounded-2xl shadow-lg hover:shadow-2xl transition duration-300">
        <div class="flex justify-between items-center">
          <h3 class="text-sm uppercase tracking-wide opacity-80 font-bold">Menus</h3>
          <span class="text-2xl">📋</span>
        </div>
        <p class="text-4xl font-bold mt-4">{{ stats.menus_count }}</p>
      </div>

      <!-- Tables -->
      <div class="border-2 border-slate-800 bg-slate-100 text-slate-900 p-6 rounded-2xl shadow-lg hover:shadow-2xl transition duration-300">
        <div class="flex justify-between items-center">
          <h3 class="text-sm uppercase tracking-wide opacity-80 font-bold">Tables</h3>
          <span class="text-2xl">🍽️</span>
        </div>
        <p class="text-4xl font-bold mt-4">{{ stats.tables_count }}</p>
      </div>

    </div>

  </AuthenticatedLayout>
</template>
