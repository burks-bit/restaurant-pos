<template>
  <AuthenticatedLayout>

    <div
      v-if="isLoading"
      class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50"
    >
      <div class="bg-white p-8 rounded-xl shadow-xl flex flex-col items-center">
        <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
        <p class="text-lg font-semibold text-gray-700">
          Generating Expense Report. Please wait...
        </p>
      </div>
    </div>

    <div class="">
      <div class="p-4 bg-gray-50">
        <h1 class="text-2xl font-semibold mb-4 text-gray-900">
          Expense Report
        </h1>

        <div class="flex flex-wrap gap-3 mb-4 items-end">
          <div class="flex flex-col">
            <label class="text-sm text-gray-600">Category</label>
            <select
              v-model="selectedCategory"
              class="border rounded px-3 py-2 text-sm w-48"
            >
              <option value="">All Categories</option>
              <option
                v-for="cat in categories"
                :key="cat.id"
                :value="cat.id"
              >
                {{ cat.name }}
              </option>
            </select>
          </div>

          <div class="flex flex-col">
            <label class="text-sm text-gray-600">Start Date</label>
            <input
              type="date"
              v-model="startDate"
              class="border rounded px-3 py-2 text-sm w-40"
            />
          </div>

          <div class="flex flex-col">
            <label class="text-sm text-gray-600">End Date</label>
            <input
              type="date"
              v-model="endDate"
              class="border rounded px-3 py-2 text-sm w-40"
            />
          </div>

          <button
            @click="fetchExpenses"
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm flex items-center gap-2"
          >
            <span class="fa fa-database"></span>
            Fetch Data
          </button>

          <button
            @click="generatePdfReport"
            :disabled="expenses.length === 0"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm flex items-center gap-2 disabled:opacity-50"
          >
            <span class="fa fa-print"></span>
            Generate PDF Report
          </button>
        </div>

        <div class="bg-white rounded-lg shadow overflow-auto max-h-[55vh]">
          <table class="min-w-full table-auto border-collapse">
            <thead class="sticky top-0 bg-gray-100 z-10">
              <tr class="text-left text-sm font-semibold">
                <th class="px-2 py-2 border">ID</th>
                <th class="px-2 py-2 border">Date</th>
                <th class="px-2 py-2 border">Category</th>
                <th class="px-2 py-2 border">Description</th>
                <th class="px-2 py-2 border">Amount</th>
                <th class="px-2 py-2 border">Created By</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="expense in expenses"
                :key="expense.id"
                class="hover:bg-gray-50 text-sm"
              >
                <td class="px-2 py-2 border">{{ expense.id }}</td>
                <td class="px-2 py-2 border">{{ expense.expense_date }}</td>
                <td class="px-2 py-2 border">{{ expense.category?.name }}</td>
                <td class="px-2 py-2 border font-medium">
                  {{ expense.description }}
                </td>
                <td class="px-2 py-2 border font-semibold text-red-600">
                  ₱{{ Number(expense.amount).toFixed(2) }}
                </td>
                <td class="px-2 py-2 border">
                  {{ expense.creator?.name }}
                </td>
              </tr>

              <tr v-if="expenses.length > 0" class="bg-gray-100 font-bold">
                <td colspan="4" class="px-2 py-2 border text-right">
                  Total Expenses:
                </td>
                <td class="px-2 py-2 border text-red-700">
                  ₱{{ grandTotal.toFixed(2) }}
                </td>
                <td class="px-2 py-2 border"></td>
              </tr>

              <tr v-if="expenses.length === 0">
                <td colspan="6" class="text-center py-6 text-gray-400">
                  No expense records found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useExpenseReport } from '@/Composables/reports/useExpenseReport'

const {
  categories,
  selectedCategory,
  startDate,
  endDate,
  isLoading,
  expenses,
  grandTotal,
  fetchExpenses,
  generatePdfReport,
} = useExpenseReport()
</script>