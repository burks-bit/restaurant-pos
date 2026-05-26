<template>
  <AuthenticatedLayout>
    <!-- FULL PAGE LOADER -->
    <div
        v-if="isLoading"
        class="fixed inset-0 bg-black bg-opacity-50 flex flex-col items-center justify-center z-[9999]"
    >
        <div class="bg-white p-8 rounded-xl shadow-xl flex flex-col items-center">
            <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
            <p class="text-lg font-semibold text-gray-700">
              {{ actionType === 'in' ? 'Clocking in... please wait.' : 'Clocking out... please wait.' }}
            </p>
        </div>
    </div>

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
              @click="openCamera('in')"
              class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition w-full sm:w-auto"
            >
              <i class="fa fa-sign-in-alt"></i> Clock In
            </button>

            <button
              @click="openCamera('out')"
              class="px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition w-full sm:w-auto"
            >
              <i class="fa fa-sign-out-alt"></i> Clock Out
            </button>
          </div>
        </div>

        <!-- SCHEDULE -->
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
                    <td class="px-4 py-3">{{ schedule.shift ?? '—' }}</td>
                    <td class="px-4 py-3">{{ schedule.time_in ?? '—' }}</td>
                    <td class="px-4 py-3">{{ schedule.time_out ?? '—' }}</td>
                    <td class="px-4 py-3">{{ schedule.status }}</td>
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

          <div class="flex items-center gap-2">
            <span class="font-semibold">Actual Time In: </span>

            <span v-if="selectedSchedule.actual_time_in">
              {{ selectedSchedule.actual_time_in }}
            </span>

            <span v-else class="text-red-500 font-medium">
              No Time In
            </span>

            <a 
              v-if="selectedSchedule.time_in_photo"
              :href="`/storage/${selectedSchedule.time_in_photo}`"
              target="_blank"
              class="text-blue-500 flex items-center"
            >
              <span class="fa fa-eye"></span>
            </a>
          </div>
          

          <div class="flex items-center gap-2">
            <span class="font-semibold">Actual Time Out: </span>

            <span v-if="selectedSchedule.actual_time_out">
              {{ selectedSchedule.actual_time_out }}
            </span>

            <span v-else class="text-red-500 font-medium">
              No Time Out
            </span>

            <a 
              v-if="selectedSchedule.time_out_photo"
              :href="`/storage/${selectedSchedule.time_out_photo}`"
              target="_blank"
              class="text-blue-500 flex items-center"
            >
              <span class="fa fa-eye"></span>
            </a>
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

    <!-- CAMERA MODAL -->
    <div
      v-if="showCamera"
      class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 p-4"
    >
      <div class="bg-white rounded-xl p-4 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-3 text-gray-700">
          Capture Photo
        </h3>

        <video ref="video" autoplay playsinline class="w-full rounded mb-3"></video>
        <canvas ref="canvas" class="hidden"></canvas>

        <div class="flex justify-between gap-2">
          <button
            @click="capturePhoto"
            class="px-4 py-2 bg-green-600 text-white rounded"
          >
            Capture
          </button>

          <button
            @click="closeCamera"
            class="px-4 py-2 bg-gray-500 text-white rounded"
          >
            Cancel
          </button>
        </div>
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
import { ref, nextTick } from 'vue'

const {
  employee,
  currentDateTime,
  toast,
  clockIn,
  clockOut,
  isToday,
  formatDate,
  isLoading,
  
  showModal,
  selectedSchedule,
  openModal,
  closeModal,
} = useEmployeeAttendance()

/* ================= CAMERA ================= */
const showCamera = ref(false)
const video = ref(null)
const canvas = ref(null)
let stream = null
const actionType = ref(null) // 'in' or 'out'

const openCamera = async (type) => {
  actionType.value = type
  showCamera.value = true


  await nextTick() // ✅ wait for <video> to mount

  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: true,
      audio: false,
    })

    video.value.srcObject = stream
  } catch (err) {
    toast.value = {
      show: true,
      message: 'Camera access denied',
      type: 'error',
    }
  }
}

const closeCamera = () => {
  showCamera.value = false

  if (stream) {
    stream.getTracks().forEach(track => track.stop())
  }
}

const capturePhoto = () => {
  const ctx = canvas.value.getContext('2d')

  canvas.value.width = video.value.videoWidth
  canvas.value.height = video.value.videoHeight

  ctx.drawImage(video.value, 0, 0)

  canvas.value.toBlob(async (blob) => {
    const formData = new FormData()

    // ✅ dynamic filename
    const fileName = actionType.value === 'out' 
      ? 'clockout.jpg' 
      : 'clockin.jpg'

    formData.append('photo', blob, fileName)

    // ✅ call correct API
    if (actionType.value === 'out') {
      await clockOut(formData)
    } else {
      await clockIn(formData)
    }

    closeCamera()
  }, 'image/jpeg')
}
</script>