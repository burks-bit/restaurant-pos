<template>
  <AuthenticatedLayout>
    <div class="p-4 bg-gray-50 min-h-screen">

      <!-- HEADER -->
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-gray-800">
          <span class="fa fa-recycle mr-2 text-green-600"></span>
          Waste Monitoring
        </h1>

        <button
          @click="openModal"
          class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700"
        >
          <span class="fa fa-plus mr-1"></span>
          Log Waste
        </button>
      </div>

      <!-- FILTER -->
      <div class="mb-4 flex flex-wrap gap-4 items-center">
        <div class="flex items-center gap-2">
          <label class="text-sm font-medium text-gray-700">Date:</label>
          <input
            type="date"
            v-model="selectedDate"
            @change="filterByDate"
            class="border rounded px-2 py-1 text-sm"
          />
        </div>

        <input
          v-model="search"
          type="text"
          placeholder="Search name or remarks…"
          class="border rounded px-3 py-1 text-sm w-64"
        />
      </div>

      <!-- FLASH -->
      <div
        v-if="$page.props.flash?.success"
        class="mb-4 p-2 bg-green-100 text-green-700 rounded"
      >
        {{ $page.props.flash.success }}
      </div>

      <!-- SUMMARY CARD -->
      <div class="mb-4 inline-flex items-center gap-2 px-4 py-2 bg-white rounded shadow text-sm text-gray-600">
        <span class="fa fa-weight-hanging text-green-600"></span>
        Total Waste Today:
        <span class="font-bold text-green-700">{{ totalKg.toFixed(2) }} kg</span>
      </div>

      <!-- TABLE -->
      <div class="bg-white rounded shadow overflow-hidden">
        <div class="max-h-[60vh] overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-100 sticky top-0">
              <tr class="text-left">
                <th class="px-4 py-2">#</th>
                <th class="px-4 py-2">Date</th>
                <th class="px-4 py-2">Time</th>
                <th class="px-4 py-2 text-right">Kg</th>
                <th class="px-4 py-2">Name</th>
                <th class="px-4 py-2">Signature</th>
                <th class="px-4 py-2">Remarks</th>
                <th class="px-4 py-2 text-right">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="filteredWastes.length === 0">
                <td colspan="8" class="text-center py-6 text-gray-400">
                  No waste records found
                </td>
              </tr>

              <tr
                v-for="(w, index) in filteredWastes"
                :key="w.id"
                class="border-t hover:bg-gray-50"
              >
                <td class="px-4 py-2">{{ index + 1 }}</td>
                <td class="px-4 py-2">{{ formatDate(w.logged_at ?? w.created_at) }}</td>
                <td class="px-4 py-2">{{ formatTime(w.logged_at ?? w.created_at) }}</td>
                <td class="px-4 py-2 text-right font-semibold text-green-700">
                  {{ Number(w.kg).toFixed(2) }}
                </td>
                <td class="px-4 py-2">{{ w.name }}</td>
                <td class="px-4 py-2">
                  <!-- Signature image if stored as base64/url, else text fallback -->
                  <img
                    v-if="w.signature && w.signature.startsWith('data:')"
                    :src="w.signature"
                    alt="Signature"
                    class="h-8 max-w-[120px] object-contain border rounded"
                  />
                  <span v-else class="italic text-gray-400 text-xs">
                    {{ w.signature || '—' }}
                  </span>
                </td>
                <td class="px-4 py-2 max-w-[180px] truncate" :title="w.remarks">
                  {{ w.remarks || '—' }}
                </td>
                <td class="px-4 py-2 text-right">
                  <button
                    @click="openEditModal(w)"
                    class="px-2 py-1 text-xs bg-yellow-400 text-white rounded hover:bg-yellow-500"
                  >
                    <span class="fa fa-pencil mr-1"></span>Edit
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ADD MODAL -->
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center"
      >
        <div class="bg-white rounded shadow-lg w-[440px] p-5">
          <h2 class="text-lg font-bold mb-4">
            <span class="fa fa-plus-circle mr-2 text-green-600"></span>
            Log Waste
          </h2>

          <div class="flex flex-col gap-3">
            <!-- Date & Time -->
            <div class="flex gap-2">
              <div class="flex-1">
                <label class="text-xs text-gray-500 mb-1 block">Date</label>
                <input
                  type="date"
                  v-model="form.date"
                  class="border rounded px-2 py-1 text-sm w-full"
                />
              </div>
              <div class="flex-1">
                <label class="text-xs text-gray-500 mb-1 block">Time</label>
                <input
                  type="time"
                  v-model="form.time"
                  class="border rounded px-2 py-1 text-sm w-full"
                />
              </div>
            </div>

            <!-- Kg -->
            <div>
              <label class="text-xs text-gray-500 mb-1 block">Weight (kg)</label>
              <input
                type="number"
                step="0.01"
                min="0"
                v-model="form.kg"
                placeholder="0.00"
                class="border rounded px-2 py-1 text-sm w-full"
              />
            </div>

            <!-- Name -->
            <div>
              <label class="text-xs text-gray-500 mb-1 block">Name</label>
              <input
                type="text"
                v-model="form.name"
                placeholder="Personnel name"
                class="border rounded px-2 py-1 text-sm w-full"
              />
            </div>

            <!-- Signature (draw pad) -->
            <div>
              <label class="text-xs text-gray-500 mb-1 block">Signature</label>
              <canvas
                ref="signatureCanvas"
                width="380"
                height="100"
                class="border rounded bg-gray-50 cursor-crosshair w-full"
                @mousedown="startDraw"
                @mousemove="draw"
                @mouseup="stopDraw"
                @mouseleave="stopDraw"
                @touchstart.prevent="startDrawTouch"
                @touchmove.prevent="drawTouch"
                @touchend="stopDraw"
              ></canvas>
              <button
                @click="clearSignature"
                class="mt-1 text-xs text-red-500 hover:underline"
              >
                Clear Signature
              </button>
            </div>

            <!-- Remarks -->
            <div>
              <label class="text-xs text-gray-500 mb-1 block">Remarks</label>
              <textarea
                v-model="form.remarks"
                placeholder="Optional remarks…"
                rows="2"
                class="border rounded px-2 py-1 text-sm w-full"
              ></textarea>
            </div>

            <div class="flex justify-end gap-2 mt-2">
              <button @click="closeModal" class="px-3 py-1 bg-gray-200 rounded text-sm">
                Cancel
              </button>
              <button
                @click="submit"
                class="px-3 py-1 bg-green-600 text-white rounded text-sm"
              >
                <span class="fa fa-save mr-1"></span>Save
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- EDIT MODAL -->
      <div
        v-if="showEditModal"
        class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center"
      >
        <div class="bg-white rounded shadow-lg w-[440px] p-5">
          <h2 class="text-lg font-bold mb-4">
            <span class="fa fa-pencil mr-2 text-yellow-500"></span>
            Edit Waste Record
          </h2>

          <div class="flex flex-col gap-3">
            <div class="flex gap-2">
              <div class="flex-1">
                <label class="text-xs text-gray-500 mb-1 block">Date</label>
                <input
                  type="date"
                  v-model="editForm.date"
                  class="border rounded px-2 py-1 text-sm w-full"
                />
              </div>
              <div class="flex-1">
                <label class="text-xs text-gray-500 mb-1 block">Time</label>
                <input
                  type="time"
                  v-model="editForm.time"
                  class="border rounded px-2 py-1 text-sm w-full"
                />
              </div>
            </div>

            <div>
              <label class="text-xs text-gray-500 mb-1 block">Weight (kg)</label>
              <input
                type="number"
                step="0.01"
                min="0"
                v-model="editForm.kg"
                placeholder="0.00"
                class="border rounded px-2 py-1 text-sm w-full"
              />
            </div>

            <div>
              <label class="text-xs text-gray-500 mb-1 block">Name</label>
              <input
                type="text"
                v-model="editForm.name"
                placeholder="Personnel name"
                class="border rounded px-2 py-1 text-sm w-full"
              />
            </div>

            <!-- Signature pad for edit -->
            <div>
              <label class="text-xs text-gray-500 mb-1 block">Signature</label>
              <canvas
                ref="editSignatureCanvas"
                width="380"
                height="100"
                class="border rounded bg-gray-50 cursor-crosshair w-full"
                @mousedown="startDrawEdit"
                @mousemove="drawEdit"
                @mouseup="stopDrawEdit"
                @mouseleave="stopDrawEdit"
                @touchstart.prevent="startDrawEditTouch"
                @touchmove.prevent="drawEditTouch"
                @touchend="stopDrawEdit"
              ></canvas>
              <button
                @click="clearEditSignature"
                class="mt-1 text-xs text-red-500 hover:underline"
              >
                Clear Signature
              </button>
            </div>

            <div>
              <label class="text-xs text-gray-500 mb-1 block">Remarks</label>
              <textarea
                v-model="editForm.remarks"
                placeholder="Optional remarks…"
                rows="2"
                class="border rounded px-2 py-1 text-sm w-full"
              ></textarea>
            </div>

            <div class="flex justify-end gap-2 mt-2">
              <button @click="closeEditModal" class="px-3 py-1 bg-gray-200 rounded text-sm">
                Cancel
              </button>
              <button
                @click="submitEdit"
                class="px-3 py-1 bg-yellow-500 text-white rounded text-sm"
              >
                <span class="fa fa-save mr-1"></span>Save Changes
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import useRolePrefix from '@/Composables/useRolePrefix'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const { prefix } = useRolePrefix()

const current_shift = computed(() => page.props.current_shift ?? null)

const props = defineProps({
  current_shift: Object,
})

// ─── State ──────────────────────────────────────────────────────────────────
const showModal     = ref(false)
const showEditModal = ref(false)
const search        = ref('')
const selectedDate  = ref(new Date().toISOString().slice(0, 10))
const localWastes   = ref(page.props.wastes ?? [])

// ─── Form ────────────────────────────────────────────────────────────────────
const now = new Date()
const form = ref({
  shift_id:  current_shift.value?.id ?? null,
  date:      now.toISOString().slice(0, 10),
  time:      now.toTimeString().slice(0, 5),
  kg:        '',
  name:      '',
  signature: '',
  remarks:   '',
})

const editForm = ref({
  id:        null,
  date:      '',
  time:      '',
  kg:        '',
  name:      '',
  signature: '',
  remarks:   '',
})

// ─── Computed ─────────────────────────────────────────────────────────────────
const filteredWastes = computed(() =>
  localWastes.value.filter(w =>
    (w.name?.toLowerCase().includes(search.value.toLowerCase())) ||
    (w.remarks?.toLowerCase().includes(search.value.toLowerCase()))
  )
)

const totalKg = computed(() =>
  filteredWastes.value.reduce((sum, w) => sum + Number(w.kg || 0), 0)
)

// ─── Helpers ─────────────────────────────────────────────────────────────────
function formatDate(datetime) {
  return new Date(datetime).toLocaleDateString('en-PH', {
    year: 'numeric', month: 'short', day: 'numeric',
  })
}

function formatTime(datetime) {
  return new Date(datetime).toLocaleTimeString('en-PH', {
    hour: '2-digit', minute: '2-digit',
  })
}

// ─── API ─────────────────────────────────────────────────────────────────────
async function filterByDate() {
  try {
    const response = await axios.get(
      route(`${prefix.value}.waste_monitoring.fetch`),
      { params: { date: selectedDate.value } }
    )
    localWastes.value = response.data.wastes
  } catch (error) {
    console.error(error)
    alert('Failed to load waste records.')
  }
}

async function submit() {
  try {
    form.value.signature = getSignatureDataUrl(signatureCanvas.value)
    await axios.post(route(`${prefix.value}.waste_monitoring.store`), form.value)
    resetForm()
    closeModal()
    await filterByDate()
  } catch (error) {
    console.error(error)
    if (error.response?.data?.errors) {
      alert(Object.values(error.response.data.errors).flat().join('\n'))
    } else {
      alert('Something went wrong while saving.')
    }
  }
}

async function submitEdit() {
  try {
    editForm.value.signature = getSignatureDataUrl(editSignatureCanvas.value)
    await axios.put(
      route(`${prefix.value}.waste_monitoring.update`, editForm.value.id),
      {
        date:      editForm.value.date,
        time:      editForm.value.time,
        kg:        editForm.value.kg,
        name:      editForm.value.name,
        signature: editForm.value.signature,
        remarks:   editForm.value.remarks,
      }
    )
    closeEditModal()
    await filterByDate()
  } catch (error) {
    console.error(error)
    if (error.response?.data?.errors) {
      alert(Object.values(error.response.data.errors).flat().join('\n'))
    } else {
      alert('Something went wrong while updating.')
    }
  }
}

// ─── Modal handlers ──────────────────────────────────────────────────────────
function openModal() {
  showModal.value = true
  nextTick(() => initCanvas(signatureCanvas.value))
}

function closeModal() {
  showModal.value = false
}

function openEditModal(w) {
  const dt = new Date(w.logged_at ?? w.created_at)
  editForm.value = {
    id:        w.id,
    date:      dt.toISOString().slice(0, 10),
    time:      dt.toTimeString().slice(0, 5),
    kg:        w.kg,
    name:      w.name,
    signature: w.signature ?? '',
    remarks:   w.remarks ?? '',
  }
  showEditModal.value = true
  nextTick(() => {
    initCanvas(editSignatureCanvas.value)
    // Pre-draw existing signature if base64
    if (w.signature?.startsWith('data:')) {
      const img = new Image()
      img.onload = () => {
        const ctx = editSignatureCanvas.value.getContext('2d')
        ctx.drawImage(img, 0, 0)
      }
      img.src = w.signature
    }
  })
}

function closeEditModal() {
  showEditModal.value = false
}

function resetForm() {
  const n = new Date()
  form.value.kg        = ''
  form.value.name      = ''
  form.value.signature = ''
  form.value.remarks   = ''
  form.value.date      = n.toISOString().slice(0, 10)
  form.value.time      = n.toTimeString().slice(0, 5)
}

// ─── Signature canvas (Add) ──────────────────────────────────────────────────
const signatureCanvas = ref(null)
let isDrawing = false

function initCanvas(canvas) {
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  ctx.clearRect(0, 0, canvas.width, canvas.height)
  ctx.strokeStyle = '#1a1a1a'
  ctx.lineWidth   = 2
  ctx.lineCap     = 'round'
}

function getPos(canvas, e) {
  const rect = canvas.getBoundingClientRect()
  const scaleX = canvas.width  / rect.width
  const scaleY = canvas.height / rect.height
  return {
    x: (e.clientX - rect.left) * scaleX,
    y: (e.clientY - rect.top)  * scaleY,
  }
}

function startDraw(e) {
  isDrawing = true
  const ctx = signatureCanvas.value.getContext('2d')
  const pos = getPos(signatureCanvas.value, e)
  ctx.beginPath()
  ctx.moveTo(pos.x, pos.y)
}
function draw(e) {
  if (!isDrawing) return
  const ctx = signatureCanvas.value.getContext('2d')
  const pos = getPos(signatureCanvas.value, e)
  ctx.lineTo(pos.x, pos.y)
  ctx.stroke()
}
function stopDraw() { isDrawing = false }
function clearSignature() { initCanvas(signatureCanvas.value) }

function startDrawTouch(e) {
  isDrawing = true
  const touch = e.touches[0]
  const ctx = signatureCanvas.value.getContext('2d')
  const pos = getPos(signatureCanvas.value, touch)
  ctx.beginPath(); ctx.moveTo(pos.x, pos.y)
}
function drawTouch(e) {
  if (!isDrawing) return
  const touch = e.touches[0]
  const ctx = signatureCanvas.value.getContext('2d')
  const pos = getPos(signatureCanvas.value, touch)
  ctx.lineTo(pos.x, pos.y); ctx.stroke()
}

// ─── Signature canvas (Edit) ──────────────────────────────────────────────────
const editSignatureCanvas = ref(null)
let isDrawingEdit = false

function startDrawEdit(e) {
  isDrawingEdit = true
  const ctx = editSignatureCanvas.value.getContext('2d')
  const pos = getPos(editSignatureCanvas.value, e)
  ctx.beginPath(); ctx.moveTo(pos.x, pos.y)
}
function drawEdit(e) {
  if (!isDrawingEdit) return
  const ctx = editSignatureCanvas.value.getContext('2d')
  const pos = getPos(editSignatureCanvas.value, e)
  ctx.lineTo(pos.x, pos.y); ctx.stroke()
}
function stopDrawEdit() { isDrawingEdit = false }
function clearEditSignature() { initCanvas(editSignatureCanvas.value) }

function startDrawEditTouch(e) {
  isDrawingEdit = true
  const touch = e.touches[0]
  const ctx = editSignatureCanvas.value.getContext('2d')
  const pos = getPos(editSignatureCanvas.value, touch)
  ctx.beginPath(); ctx.moveTo(pos.x, pos.y)
}
function drawEditTouch(e) {
  if (!isDrawingEdit) return
  const touch = e.touches[0]
  const ctx = editSignatureCanvas.value.getContext('2d')
  const pos = getPos(editSignatureCanvas.value, touch)
  ctx.lineTo(pos.x, pos.y); ctx.stroke()
}

// ─── Export signature as base64 ───────────────────────────────────────────────
function getSignatureDataUrl(canvas) {
  if (!canvas) return ''
  return canvas.toDataURL('image/png')
}

// ─── Init ─────────────────────────────────────────────────────────────────────
onMounted(() => filterByDate())
</script>