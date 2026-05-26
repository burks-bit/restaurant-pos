<template>
  <AuthenticatedLayout>
    <div
      v-if="isLoading"
      class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50"
    >
      <div class="bg-white p-8 rounded-xl shadow-xl flex flex-col items-center">
        <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
        <p class="text-lg font-semibold text-gray-700">
          Fetching expenses. Please wait...
        </p>
      </div>
    </div>

    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50">

        <h1 class="text-2xl font-semibold mb-4 text-gray-900">
          Expenses
        </h1>

        <div class="flex gap-4 mb-4 text-sm font-medium text-gray-800">
          <div class="bg-white shadow rounded px-4 py-2 flex-1 text-center">
            Total Expenses
            <div class="text-xl font-bold">
              ₱{{ totalExpenses.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
            </div>
          </div>
        </div>

        <div class="flex flex-wrap gap-2 mb-4 items-end justify-between">
          <div class="flex flex-wrap gap-2 items-end">
            <input
              v-model="search"
              type="text"
              placeholder="Search description…"
              class="border rounded px-3 py-2 text-sm w-64"
            />

            <select v-model="selectedCategory" class="border rounded px-3 py-2 text-sm w-48">
              <option value="">All Categories</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>

            <input type="date" v-model="startDate" class="border rounded px-3 py-2 text-sm" />
            <input type="date" v-model="endDate" class="border rounded px-3 py-2 text-sm" />

            <button
              @click="applyFilters"
              class="flex items-center gap-1 text-sm bg-green-600 text-white px-3 py-2 rounded hover:bg-green-700"
            >
              <i class="fa fa-filter"></i> Filter
            </button>
          </div>

          <button
            class="flex items-center gap-1 text-sm bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700"
            @click="openAddExpenseModal"
          >
            <i class="fa fa-plus"></i> Add Expense
          </button>
        </div>

        <div class="bg-white rounded-lg shadow overflow-auto max-h-[60vh]">
          <table class="min-w-full table-auto border-collapse">
            <thead class="sticky top-0 bg-gray-100 z-10">
              <tr>
                <th class="px-2 py-1 border">#</th>
                <th class="px-2 py-1 border">Category</th>
                <th class="px-2 py-1 border">Description</th>
                <th class="px-2 py-1 border">Amount</th>
                <th class="px-2 py-1 border">Date</th>
                <th class="px-2 py-1 border">Posted By</th>
                <th class="px-2 py-1 border">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(expense, i) in expenses" :key="expense.id" class="hover:bg-gray-50">
                <td class="px-2 py-1 border">{{ i + 1 }}</td>
                <td class="px-2 py-1 border">{{ expense.category?.name }}</td>
                <td class="px-2 py-1 border">{{ expense.description }}</td>
                <td class="px-2 py-1 border">₱{{ Number(expense.amount).toFixed(2) }}</td>
                <td class="px-2 py-1 border">{{ expense.expense_date }}</td>
                <td class="px-2 py-1 border">{{ expense.creator?.name }}</td>
                <td class="px-2 py-1 border">
                  <button
                    @click="deleteExpense(expense.id)"
                    class="text-red-600 hover:underline text-sm"
                  >
                    Delete
                  </button>
                </td>
              </tr>

              <tr v-if="expenses.length === 0">
                <td colspan="7" class="text-center py-6 text-gray-400">
                  No expenses found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-if="showAddExpenseModal"
          class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
          <div class="bg-white rounded-lg w-full max-w-md p-6">
            <h2 class="text-xl font-semibold mb-4">Add Expense</h2>

            <form @submit.prevent="submitExpense" class="space-y-4">
              <div>
                <label class="block text-sm font-medium mb-1">Category</label>
                <select
                  v-model="newExpense.expense_category_id"
                  class="w-full border rounded px-3 py-2 text-sm"
                  required
                >
                  <option value="" disabled>Select Category</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Description</label>
                <input
                  v-model="newExpense.description"
                  type="text"
                  class="w-full border rounded px-3 py-2 text-sm"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Amount</label>
                <input
                  v-model="newExpense.amount"
                  type="number"
                  min="0"
                  step="0.01"
                  class="w-full border rounded px-3 py-2 text-sm"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Date</label>
                <input
                  v-model="newExpense.expense_date"
                  type="date"
                  class="w-full border rounded px-3 py-2 text-sm"
                  required
                />
              </div>

              <div class="flex justify-end gap-2 mt-4">
                <button
                  type="button"
                  class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
                  @click="closeAddExpenseModal"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                  Save
                </button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useExpensesIndex } from '@/Composables/expenses/useExpensesIndex'

const {
  expenses,
  categories,
  search,
  selectedCategory,
  startDate,
  endDate,
  isLoading,
  showAddExpenseModal,
  newExpense,
  totalExpenses,
  openAddExpenseModal,
  closeAddExpenseModal,
  submitExpense,
  applyFilters,
  deleteExpense,
} = useExpensesIndex()
</script>

<style scoped>
body {
  font-family: 'Poppins', sans-serif;
}
</style>