<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <div class="flex justify-between items-center mb-4 flex-wrap gap-3">
        <div>
          <h1 class="text-2xl font-semibold">
            {{ employee.first_name }} {{ employee.last_name }}
          </h1>
          <p class="text-gray-500 text-sm">{{ employee.employee_code }}</p>
        </div>

        <div class="flex gap-2 items-center">
          <button
            @click="openBatchModal"
            class="flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded shadow-sm transition"
          >
            <i class="fa fa-plus-circle"></i>
            Post Batch Schedule
          </button>

          <button
            @click="goBackToEmployees"
            class="flex items-center gap-1 px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded shadow-sm transition"
          >
            Back to Employees
            <i class="fa fa-arrow-left"></i>
          </button>
        </div>
      </div>

      <div class="flex justify-between items-center mb-4 flex-wrap gap-3">
        <div class="flex gap-2">
          <button
            @click="prevMonth"
            class="flex items-center gap-1 px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded shadow-sm transition"
          >
            <i class="fa fa-chevron-left"></i>
            Prev
          </button>

          <span class="font-medium px-2">{{ monthNames[currentMonth] }} {{ currentYear }}</span>

          <button
            @click="nextMonth"
            class="flex items-center gap-1 px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded shadow-sm transition"
          >
            Next
            <i class="fa fa-chevron-right"></i>
          </button>
        </div>

        <div class="flex gap-2 items-center">
          <input
            type="date"
            v-model="printStartDate"
            class="border rounded px-2 py-1 text-sm"
            title="Start Date"
          />

          <input
            type="date"
            v-model="printEndDate"
            class="border rounded px-2 py-1 text-sm"
            title="End Date"
          />

          <button
            @click="printSchedule"
            class="flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded shadow-sm transition"
          >
            <i class="fa fa-print"></i>
            Print Schedule
          </button>
        </div>
      </div>

      <div class="bg-white shadow rounded-lg p-4">
        <div class="grid grid-cols-7 gap-1 text-center font-semibold bg-gray-100 p-1">
          <div v-for="day in weekDays" :key="day">{{ day }}</div>
        </div>

        <div class="grid grid-cols-7 gap-1 mt-1">
          <div
            v-for="dateObj in calendarDays"
            :key="dateObj.date"
            class="border h-28 p-1 relative overflow-hidden"
            :class="{
              'bg-gray-50': dateObj.isCurrentMonth,
              'bg-gray-200': !dateObj.isCurrentMonth
            }"
          >
            <div class="text-xs font-medium mb-1">{{ dateObj.day }}</div>

            <div
              v-for="schedule in getSchedules(dateObj.date)"
              :key="schedule.id"
              class="text-xs rounded px-1 py-1 mb-1 cursor-pointer"
              :class="scheduleBadgeClass(schedule.status)"
              @click="openModal(schedule)"
              :title="buildScheduleTitle(schedule)"
            >
              <div class="font-medium">
                {{ schedule.status }}
              </div>

              <div v-if="showTimeRange(schedule)" class="text-[0.7rem]">
                {{ formatTime(schedule.time_in) }} - {{ formatTime(schedule.time_out) }}
              </div>

              <div
                v-if="schedule.remarks && schedule.remarks.trim() !== ''"
                class="text-[0.65rem] mt-0.5 truncate"
              >
                {{ schedule.remarks }}
              </div>
            </div>

            <button
              v-if="dateObj.isCurrentMonth && !hasSchedule(dateObj.date)"
              @click="openModal({ schedule_date: dateObj.date })"
              class="absolute bottom-1 right-1 text-xs text-blue-600 hover:underline"
            >
              + Add
            </button>

            <button
              v-if="dateObj.isCurrentMonth && hasSchedule(dateObj.date)"
              @click="openExistingSchedule(dateObj.date)"
              class="absolute bottom-1 right-1 text-xs text-amber-600 hover:underline"
            >
              Update
            </button>
          </div>
        </div>
      </div>

      <!-- Single Schedule Modal -->
      <!-- Single Schedule Modal -->
<div
  v-if="showModal"
  class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
>
  <div class="bg-white rounded-lg w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
    <h2 class="font-semibold mb-4">
      {{ form.id ? 'Edit Schedule' : 'Add Schedule' }}
    </h2>

    <!-- Attendance Photos (Edit mode only, when at least one photo exists) -->
    <div v-if="form.id && (form.time_in_photo || form.time_out_photo)" class="mb-5">
      <p class="text-sm font-medium text-gray-600 mb-2">Attendance Photos</p>
      <div class="grid grid-cols-2 gap-3">

        <!-- Time In Photo -->
        <div class="flex flex-col gap-1">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-gray-500">Time In</span>
            <span v-if="form.actual_time_in" class="text-xs text-blue-600 font-medium">
              {{ form.actual_time_in }}
            </span>
            <span v-else class="text-xs text-gray-400">—</span>
          </div>
          <a
            v-if="form.time_in_photo"
            :href="`/storage/${form.time_in_photo}`"
            target="_blank"
            class="block relative group"
          >
            <img
              :src="`/storage/${form.time_in_photo}`"
              alt="Time In Photo"
              class="w-full object-cover rounded border border-gray-200"
              style="aspect-ratio: 4/3;"
            />
            <span class="absolute bottom-1.5 right-1.5 text-[10px] bg-black bg-opacity-55 text-white px-2 py-0.5 rounded opacity-0 group-hover:opacity-100 transition">
              View full
            </span>
          </a>
          <div
            v-else
            class="w-full rounded border border-gray-200 bg-gray-50 flex flex-col items-center justify-center gap-1 text-gray-400"
            style="aspect-ratio: 4/3;"
          >
            <i class="fa fa-camera text-lg"></i>
            <span class="text-xs">No photo yet</span>
          </div>
        </div>

        <!-- Time Out Photo -->
        <div class="flex flex-col gap-1">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-gray-500">Time Out</span>
            <span v-if="form.actual_time_out" class="text-xs text-blue-600 font-medium">
              {{ form.actual_time_out }}
            </span>
            <span v-else class="text-xs text-gray-400">—</span>
          </div>
          <a
            v-if="form.time_out_photo"
            :href="`/storage/${form.time_out_photo}`"
            target="_blank"
            class="block relative group"
          >
            <img
              :src="`/storage/${form.time_out_photo}`"
              alt="Time Out Photo"
              class="w-full object-cover rounded border border-gray-200"
              style="aspect-ratio: 4/3;"
            />
            <span class="absolute bottom-1.5 right-1.5 text-[10px] bg-black bg-opacity-55 text-white px-2 py-0.5 rounded opacity-0 group-hover:opacity-100 transition">
              View full
            </span>
          </a>
          <div
            v-else
            class="w-full rounded border border-gray-200 bg-gray-50 flex flex-col items-center justify-center gap-1 text-gray-400"
            style="aspect-ratio: 4/3;"
          >
            <i class="fa fa-camera text-lg"></i>
            <span class="text-xs">No photo yet</span>
          </div>
        </div>

      </div>
    </div>

    <!-- Divider (only shown when photos are present) -->
    <hr v-if="form.id && (form.time_in_photo || form.time_out_photo)" class="mb-4 border-gray-100" />

    <!-- Form Fields -->
    <div class="space-y-3">
      <div>
        <label class="block text-sm font-medium mb-1">Schedule Date</label>
        <input
          type="date"
          v-model="form.schedule_date"
          class="w-full border rounded px-3 py-2 text-sm"
        />
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Status</label>
        <select v-model="form.status" class="w-full border rounded px-3 py-2 text-sm">
          <option value="Scheduled">Scheduled</option>
          <option value="Day Off">Day Off</option>
          <option value="Leave">Leave</option>
          <option value="Absent">Absent</option>
        </select>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium mb-1">Clock In</label>
          <input
            type="time"
            v-model="form.time_in"
            class="w-full border rounded px-3 py-2 text-sm"
            :disabled="!requiresTime(form.status)"
          />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Clock Out</label>
          <input
            type="time"
            v-model="form.time_out"
            class="w-full border rounded px-3 py-2 text-sm"
            :disabled="!requiresTime(form.status)"
          />
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Remarks</label>
        <textarea
          v-model="form.remarks"
          rows="3"
          placeholder="Remarks"
          class="w-full border rounded px-3 py-2 text-sm"
        ></textarea>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-end gap-2 mt-6">
      <button @click="closeModal" class="px-4 py-2 bg-gray-200 rounded">
        Cancel
      </button>
      <button @click="saveSchedule" class="px-4 py-2 bg-blue-600 text-white rounded">
        Save
      </button>
    </div>
  </div>
</div>

      <!-- Batch Schedule Modal -->
      <div
        v-if="showBatchModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      >
        <div class="bg-white rounded-lg w-full max-w-lg p-6">
          <h2 class="font-semibold mb-4">Post Batch Schedule</h2>

          <div class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium mb-1">Start Date</label>
                <input
                  type="date"
                  v-model="batchForm.start_date"
                  class="w-full border rounded px-3 py-2 text-sm"
                />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">End Date</label>
                <input
                  type="date"
                  v-model="batchForm.end_date"
                  class="w-full border rounded px-3 py-2 text-sm"
                />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Status</label>
              <select v-model="batchForm.status" class="w-full border rounded px-3 py-2 text-sm">
                <option value="Scheduled">Scheduled</option>
                <option value="Day Off">Day Off</option>
                <option value="Leave">Leave</option>
                <option value="Absent">Absent</option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium mb-1">Clock In</label>
                <input
                  type="time"
                  v-model="batchForm.time_in"
                  class="w-full border rounded px-3 py-2 text-sm"
                  :disabled="!requiresTime(batchForm.status)"
                />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Clock Out</label>
                <input
                  type="time"
                  v-model="batchForm.time_out"
                  class="w-full border rounded px-3 py-2 text-sm"
                  :disabled="!requiresTime(batchForm.status)"
                />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Remarks</label>
              <textarea
                v-model="batchForm.remarks"
                rows="3"
                placeholder="Remarks"
                class="w-full border rounded px-3 py-2 text-sm"
              ></textarea>
            </div>
          </div>

          <div class="flex justify-end gap-2 mt-6">
            <button @click="closeBatchModal" class="px-4 py-2 bg-gray-200 rounded">
              Cancel
            </button>
            <button @click="saveBatchSchedule" class="px-4 py-2 bg-green-600 text-white rounded">
              Post Schedule
            </button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useEmployeeSchedule } from '@/Composables/employees/useEmployeeSchedule'

const {
  employee,

  showModal,
  form,
  openModal,
  closeModal,
  saveSchedule,

  showBatchModal,
  batchForm,
  openBatchModal,
  closeBatchModal,
  saveBatchSchedule,

  currentMonth,
  currentYear,
  weekDays,
  monthNames,
  calendarDays,

  printStartDate,
  printEndDate,
  printSchedule,

  getSchedules,
  hasSchedule,
  openExistingSchedule,
  formatTime,
  requiresTime,
  showTimeRange,
  scheduleBadgeClass,
  buildScheduleTitle,

  prevMonth,
  nextMonth,
  goBackToEmployees,
} = useEmployeeSchedule()
</script>