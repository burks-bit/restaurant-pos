<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">

      <div class="flex justify-between items-center mb-4 flex-wrap gap-3">
        <div>
          <h1 class="text-2xl font-semibold">{{ employee.first_name }} {{ employee.last_name }}</h1>
          <p class="text-gray-500 text-sm">{{ employee.employee_code }}</p>
        </div>

        <div class="flex gap-2 items-center">
          <button
            @click="printSchedule"
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
            class="border h-24 p-1 relative"
            :class="{
              'bg-gray-50': dateObj.isCurrentMonth,
              'bg-gray-200': !dateObj.isCurrentMonth
            }"
          >
            <div class="text-xs font-medium mb-1">{{ dateObj.day }}</div>

            <div
              v-for="schedule in getSchedules(dateObj.date)"
              :key="schedule.id"
              class="text-xs rounded px-1 py-0.5 mb-1 cursor-pointer"
              :class="{
                'bg-green-200 text-green-800': schedule.shift === 'Morning' && schedule.status === 'Scheduled',
                'bg-indigo-200 text-indigo-800': schedule.shift === 'Night' && schedule.status === 'Scheduled',
                'bg-red-200 text-red-800': schedule.status === 'Absent',
                'bg-yellow-200 text-yellow-800': schedule.status === 'Leave',
                'bg-gray-300 text-gray-700': schedule.status === 'Day Off'
              }"
              @click="openModal(schedule)"
              :title="schedule.status + (schedule.remarks && schedule.remarks.trim() !== '' ? ': ' + schedule.remarks : '')"
            >
              <div>
                <template v-if="['Absent', 'Leave', 'Day Off'].includes(schedule.status)">
                  {{ schedule.status }}
                </template>
                <template v-else-if="!schedule.time_in && !schedule.time_out">
                  Off Duty
                </template>
                <template v-else>
                  {{ schedule.shift }} ({{ schedule.time_in?.slice(0,5) }} - {{ schedule.time_out?.slice(0,5) }})
                </template>
              </div>

              <div
                v-if="schedule.remarks && schedule.remarks.trim() !== ''"
                class="text-[0.6rem] text-gray-700 mt-0.5"
              >
                {{ schedule.remarks }}
              </div>
            </div>

            <button
              v-if="dateObj.isCurrentMonth"
              @click="openModal({ schedule_date: dateObj.date })"
              class="absolute bottom-1 right-1 text-xs text-blue-600 hover:underline"
            >
              + Add
            </button>
          </div>
        </div>
      </div>

      <div
        v-if="showModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      >
        <div class="bg-white rounded-lg w-full max-w-md p-6">
          <h2 class="font-semibold mb-4">{{ form.id ? 'Edit Schedule' : 'Add Schedule' }}</h2>

          <div class="space-y-3">
            <input type="date" v-model="form.schedule_date" class="w-full border rounded px-3 py-2 text-sm" />

            <select v-model="form.shift" class="w-full border rounded px-3 py-2 text-sm">
              <option value="Morning">Morning</option>
              <option value="Night">Night</option>
            </select>

            <input type="time" v-model="form.time_in" class="w-full border rounded px-3 py-2 text-sm" />
            <input type="time" v-model="form.time_out" class="w-full border rounded px-3 py-2 text-sm" />

            <select v-model="form.status" class="w-full border rounded px-3 py-2 text-sm">
              <option value="Scheduled">Scheduled</option>
              <option value="Absent">Absent</option>
              <option value="Leave">Leave</option>
              <option value="Day Off">Day Off</option>
            </select>

            <input
              type="text"
              v-model="form.remarks"
              placeholder="Remarks / Reason"
              class="w-full border rounded px-3 py-2 text-sm"
            />
          </div>

          <div class="flex justify-end gap-2 mt-6">
            <button @click="closeModal" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
            <button @click="saveSchedule" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
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

  currentMonth,
  currentYear,
  weekDays,
  monthNames,
  calendarDays,

  printStartDate,
  printEndDate,
  printSchedule,

  getSchedules,

  prevMonth,
  nextMonth,
  goBackToEmployees,
} = useEmployeeSchedule()
</script>