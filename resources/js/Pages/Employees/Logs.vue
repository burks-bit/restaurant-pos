<template>
    <AuthenticatedLayout>
        <div class="container mx-auto py-6">
            <div class="p-4 bg-gray-50">

                <!-- Page Title -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">
                            <span class="fa fa-clipboard-list text-blue-600 mr-2"></span>
                            Employee Attendance Logs
                        </h1>
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ filteredLogs.length }} record{{ filteredLogs.length === 1 ? '' : 's' }} found
                            <span v-if="search"> for "{{ search }}"</span>
                            <span v-if="dateFilter"> on {{ formatDateLabel(dateFilter) }}</span>
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <!-- Date Filter -->
                        <div class="relative w-full sm:w-48">
                            <span class="fa fa-calendar-day absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></span>
                            <input
                                v-model="dateFilter"
                                type="date"
                                class="w-full pl-9 pr-9 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <button
                                v-if="dateFilter"
                                @click="dateFilter = ''"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                title="Clear date"
                            >
                                <span class="fa fa-times text-sm"></span>
                            </button>
                        </div>

                        <!-- Search Input -->
                        <div class="relative w-full sm:w-72">
                            <span class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></span>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search name, code, or status..."
                                class="w-full pl-9 pr-9 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <button
                                v-if="search"
                                @click="search = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            >
                                <span class="fa fa-times text-sm"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow border border-gray-200 overflow-auto max-h-[70vh]">
                    <table class="min-w-full table-auto border-collapse text-sm">
                        <thead class="sticky top-0 bg-gray-100 z-10">
                            <tr class="text-left text-gray-600 uppercase text-xs tracking-wide">
                                <th class="px-4 py-3 border-b border-gray-200">
                                    <span class="fa fa-user mr-1.5 text-gray-400"></span>Employee
                                </th>
                                <th class="px-4 py-3 border-b border-gray-200">
                                    <span class="fa fa-calendar-alt mr-1.5 text-gray-400"></span>Schedule
                                </th>
                                <th class="px-4 py-3 border-b border-gray-200">
                                    <span class="fa fa-sign-in-alt mr-1.5 text-gray-400"></span>Clock In
                                </th>
                                <th class="px-4 py-3 border-b border-gray-200">
                                    <span class="fa fa-sign-out-alt mr-1.5 text-gray-400"></span>Clock Out
                                </th>
                                <th class="px-4 py-3 border-b border-gray-200">
                                    <span class="fa fa-info-circle mr-1.5 text-gray-400"></span>Status
                                </th>
                                <th class="px-4 py-3 border-b border-gray-200 text-right">
                                    <span class="fa fa-cog mr-1.5 text-gray-400"></span>Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="log in filteredLogs"
                                :key="log.id"
                                class="odd:bg-white even:bg-gray-50/60 hover:bg-blue-50/60 transition-colors"
                            >
                                <!-- Employee -->
                                <td class="px-4 py-3 align-middle">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-9 h-9 rounded-full bg-blue-100 text-blue-700 font-semibold text-xs shrink-0">
                                            {{ initials(log) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-800 leading-tight">
                                                {{ log.last_name }}, {{ log.first_name }}
                                            </div>
                                            <div class="text-xs text-gray-400">
                                                <span class="fa fa-id-badge mr-1"></span>{{ log.employee_code }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Schedule -->
                                <td class="px-4 py-3 align-middle whitespace-nowrap">
                                    <div class="text-gray-700 font-medium">
                                        <span class="fa fa-calendar-alt text-gray-400 mr-1"></span>
                                        {{ log.schedule_date ?? '—' }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        <span class="fa fa-clock text-gray-400 mr-1"></span>
                                        {{ log.time_in ?? '—' }} <span class="text-gray-400">–</span> {{ log.time_out ?? '—' }}
                                    </div>
                                </td>

                                <!-- Clock In -->
                                <td class="px-4 py-3 align-middle whitespace-nowrap">
                                    <span v-if="log.actual_time_in" class="text-gray-700">
                                        <span class="fa fa-sign-in-alt text-green-500 mr-1"></span>
                                        {{ log.actual_time_in }}
                                    </span>
                                    <span v-else class="text-gray-400 italic">
                                        <span class="fa fa-minus-circle mr-1"></span>Not yet
                                    </span>
                                </td>

                                <!-- Clock Out -->
                                <td class="px-4 py-3 align-middle whitespace-nowrap">
                                    <span v-if="log.actual_time_out" class="text-gray-700">
                                        <span class="fa fa-sign-out-alt text-red-400 mr-1"></span>
                                        {{ log.actual_time_out }}
                                    </span>
                                    <span v-else class="text-gray-400 italic">
                                        <span class="fa fa-minus-circle mr-1"></span>Not yet
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-3 align-middle">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                                        :class="statusBadge(log).class"
                                    >
                                        <span class="fa" :class="statusBadge(log).icon"></span>
                                        {{ statusBadge(log).label }}
                                    </span>
                                </td>

                                <!-- Action -->
                                <td class="px-4 py-3 align-middle text-right">
                                    <button
                                        @click="openAttendancePhotos(log)"
                                        class="inline-flex items-center justify-center w-8 h-8 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded transition-colors mr-2"
                                        title="View Attendance Photos"
                                    >
                                        <span class="fa fa-images"></span>
                                    </button>

                                    <Link
                                        :href="route('hr.employees.schedule', { employee: log.employee_id })"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-md hover:bg-green-700 transition-colors"
                                    >
                                        <span class="fa fa-eye"></span> Schedule
                                    </Link>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="filteredLogs.length === 0">
                                <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                                    <span class="fa fa-inbox text-3xl mb-2 block text-gray-300"></span>
                                    <template v-if="search || dateFilter">No logs match the current filters.</template>
                                    <template v-else>No attendance logs found.</template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Attendance Photos Modal -->
            <div
                v-if="attendancePhotoDialog"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            >
                <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl">
                    <div class="flex items-center justify-between px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold">
                            <span class="fa fa-images mr-2 text-blue-600"></span>
                            Attendance Photos
                        </h2>

                        <button
                            @click="attendancePhotoDialog = false"
                            class="text-gray-500 hover:text-gray-700"
                        >
                            <span class="fa fa-times"></span>
                        </button>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Time In -->
                            <div>
                                <h3 class="font-medium mb-3">
                                    <span class="fa fa-sign-in-alt text-green-600 mr-2"></span>
                                    Time In Photo
                                </h3>

                                <img
                                    v-if="selectedLog?.time_in_photo"
                                    :src="`/storage/${selectedLog.time_in_photo}`"
                                    class="w-full border rounded-lg"
                                />

                                <div
                                    v-else
                                    class="border rounded-lg p-8 text-center text-gray-500 bg-gray-50"
                                >
                                    No Time In photo available.
                                </div>
                            </div>

                            <!-- Time Out -->
                            <div>
                                <h3 class="font-medium mb-3">
                                    <span class="fa fa-sign-out-alt text-red-600 mr-2"></span>
                                    Time Out Photo
                                </h3>

                                <img
                                    v-if="selectedLog?.time_out_photo"
                                    :src="`/storage/${selectedLog.time_out_photo}`"
                                    class="w-full border rounded-lg"
                                />

                                <div
                                    v-else
                                    class="border rounded-lg p-8 text-center text-gray-500 bg-gray-50"
                                >
                                    No Time Out photo available.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t flex justify-end">
                        <button
                            @click="attendancePhotoDialog = false"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>


    </AuthenticatedLayout>
</template>

<script setup>
import { ref, watch, computed} from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
  logs: Array,
  filters: Object, // { date: '2026-07-04' } from the controller
})

const search = ref('')
const dateFilter = ref(props.filters?.date ?? '')

// Push the date to the server instead of filtering client-side —
// the service already handles schedule_date / shift_end_date matching in SQL.
watch(dateFilter, (value) => {
    router.get(route('hr.employees.daily-logs'), { date: value || undefined }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
})

const statusBadge = (log) => {
    if (!log.actual_time_in) {
        return { label: 'No Clock-in', class: 'bg-gray-100 text-gray-500', icon: 'fa-ban' }
    }
    if (!log.actual_time_out) {
        return { label: 'Clocked In', class: 'bg-blue-100 text-blue-700', icon: 'fa-walking' }
    }
    if (log.time_in && log.actual_time_in > log.time_in) {
        return { label: 'Late', class: 'bg-amber-100 text-amber-700', icon: 'fa-exclamation-triangle' }
    }
    return { label: 'Present', class: 'bg-green-100 text-green-700', icon: 'fa-check-circle' }
}

// Now only handles the text search — logs arriving from props are already
// date-filtered server-side.
const filteredLogs = computed(() => {
    const term = search.value.trim().toLowerCase()
    if (!term) return props.logs ?? []

    return (props.logs ?? []).filter((log) => {
        const fullName = `${log.first_name ?? ''} ${log.last_name ?? ''}`.toLowerCase()
        const code = (log.employee_code ?? '').toLowerCase()
        const status = statusBadge(log).label.toLowerCase()

        return fullName.includes(term) || code.includes(term) || status.includes(term)
    })
})

const formatDateLabel = (isoDate) => {
    if (!isoDate) return ''
    const [year, month, day] = isoDate.split('-').map(Number)
    const d = new Date(year, month - 1, day)
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

const initials = (log) => {
    const first = log.first_name?.charAt(0) ?? ''
    const last = log.last_name?.charAt(0) ?? ''
    return `${first}${last}`.toUpperCase()
}

const attendancePhotoDialog = ref(false)
const selectedLog = ref(null)

const openAttendancePhotos = (log) => {
    selectedLog.value = log
    attendancePhotoDialog.value = true
}
</script>