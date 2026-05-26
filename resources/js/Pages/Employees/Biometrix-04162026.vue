<template>
  <AuthenticatedLayout>
    <div class="p-1">
      <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6 gap-4 sm:gap-0">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">
            Welcome, {{ employee?.first_name }} {{ employee?.last_name }} 👋
          </h1>
          <p class="text-gray-500 text-sm">
            Here is your attendance dashboard.
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white shadow rounded-xl p-6 space-y-6 md:col-span-1">
          <div class="text-center">
            <h2 class="text-xl font-semibold text-gray-700">
              Current Date & Time
            </h2>
            <p class="text-3xl font-bold text-indigo-600 mt-2">
              {{ currentDateTime }}
            </p>
          </div>

          <div class="flex flex-col sm:flex-row justify-center gap-4">
            <button
              @click="clockIn"
              class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition w-full sm:w-auto"
            >
              <i class="fa fa-sign-in-alt"></i> Clock In
            </button>

            <button
              @click="clockOut"
              class="px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition w-full sm:w-auto"
            >
              <i class="fa fa-sign-out-alt"></i> Clock Out
            </button>
          </div>
        </div>

        <div class="bg-white shadow rounded-xl p-6 md:col-span-2">
          <h2 class="text-xl font-semibold text-gray-700 mb-4">
            Schedule
          </h2>

          <div v-if="employee?.schedules?.length">
            <div class="max-h-96 overflow-y-auto border rounded-lg overflow-x-auto">
              <table class="min-w-[600px] text-sm text-left text-gray-600 w-full">
                <thead class="bg-gray-100 text-xs uppercase sticky top-0 z-10">
                  <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Shift</th>
                    <th class="px-4 py-3">Time In</th>
                    <th class="px-4 py-3">Time Out</th>
                    <th class="px-4 py-3">Status</th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="schedule in employee.schedules"
                    :key="schedule.id"
                    @click="openModal(schedule)"
                    class="border-b hover:bg-indigo-50 cursor-pointer transition"
                    :class="isToday(schedule.schedule_date) ? 'bg-indigo-100 font-semibold' : ''"
                  >
                    <td class="px-4 py-3">
                      {{ formatDate(schedule.schedule_date) }}
                    </td>

                    <td class="px-4 py-3">
                      {{ schedule.shift ?? '—' }}
                    </td>

                    <td class="px-4 py-3">
                      {{ schedule.time_in ?? '—' }}
                    </td>

                    <td class="px-4 py-3">
                      {{ schedule.time_out ?? '—' }}
                    </td>

                    <td class="px-4 py-3">
                      {{ schedule.status }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-else class="text-gray-500">
            No schedules available.
          </div>
        </div>
      </div>
    </div>

    <!-- Attendance Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4"
      @click.self="closeModal"
    >
      <div class="bg-white w-full max-w-full sm:max-w-lg rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-700">
          Actual Attendance
        </h3>

        <div class="space-y-3 text-sm">
          <div>
            <span class="font-semibold">Date:</span>
            {{ formatDate(selectedSchedule.schedule_date) }}
          </div>

          <div>
            <span class="font-semibold">Actual Time In: </span>
            <span v-if="selectedSchedule.actual_time_in">
              {{ selectedSchedule.actual_time_in }}
            </span>
            <span v-else class="text-red-500 font-medium">
              No Time In
            </span>
          </div>

          <div>
            <span class="font-semibold">Actual Time Out: </span>
            <span v-if="selectedSchedule.actual_time_out">
              {{ selectedSchedule.actual_time_out }}
            </span>
            <span v-else class="text-red-500 font-medium">
              No Time Out
            </span>
          </div>
        </div>

        <div class="mt-6 text-right">
          <button
            @click="closeModal"
            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700"
          >
            Close
          </button>
        </div>
      </div>
    </div>

    <!-- OT Modal -->
    <div
      v-if="showOtModal"
      class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4"
      @click.self="closeOtModal"
    >
      <div class="bg-white w-full max-w-md rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-700">
          File Overtime Form
        </h3>

        <form @submit.prevent="submitOt">
          <div class="space-y-4 text-sm">
            <div>
              <label class="block font-medium">Start Time</label>
              <input
                type="time"
                v-model="otForm.start_time"
                class="w-full border rounded px-3 py-2"
                required
              />
            </div>

            <div>
              <label class="block font-medium">End Time</label>
              <input
                type="time"
                v-model="otForm.end_time"
                class="w-full border rounded px-3 py-2"
                required
              />
            </div>

            <div>
              <label class="block font-medium">Type</label>
              <select
                v-model="otForm.type"
                class="w-full border rounded px-3 py-2"
                required
              >
                <option value="regular_day">Regular Day</option>
                <option value="rest_day">Rest Day</option>
                <option value="regular_holiday">Regular Holiday</option>
                <option value="special_holiday">Special Holiday</option>
                <option value="rest_day_regular_holiday">Rest Day Regular Holiday</option>
                <option value="rest_day_special_holiday">Rest Day Special Holiday</option>
              </select>
            </div>

            <div>
              <label class="block font-medium">Remarks</label>
              <textarea
                v-model="otForm.remarks"
                class="w-full border rounded px-3 py-2"
              ></textarea>
            </div>
          </div>

          <div class="mt-6 flex justify-end gap-3">
            <button
              type="button"
              @click="closeOtModal"
              class="px-4 py-2 bg-gray-500 text-white rounded"
            >
              Cancel
            </button>

            <button
              type="submit"
              class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700"
            >
              Submit OT
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Toast -->
    <div
      v-if="toast.show"
      class="fixed top-5 right-5 z-50 px-4 py-3 rounded-lg shadow-lg text-white transition"
      :class="toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'"
    >
      {{ toast.message }}
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useEmployeeAttendance } from '@/Composables/employees/useEmployeeAttendance'

const {
  employee,
  currentDateTime,
  toast,

  showModal,
  selectedSchedule,
  openModal,
  closeModal,

  showOtModal,
  otForm,
  closeOtModal,
  submitOt,

  clockIn,
  clockOut,

  isToday,
  formatDate,
} = useEmployeeAttendance()
</script>