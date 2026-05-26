<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50 min-h-screen">

        <!-- Header -->
        <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
          <h1 class="text-2xl font-semibold text-gray-900">
            System Configuration
          </h1>

          <button
            class="flex items-center gap-1 px-3 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
            @click="openAddModal"
          >
            <i class="fa fa-plus"></i>
            New Configuration
          </button>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow overflow-auto max-h-[70vh]">
          <table class="min-w-full table-auto border-collapse">
            <thead class="sticky top-0 bg-gray-100 z-10">
              <tr class="text-left text-gray-900">
                <th class="px-3 py-2 border">ID</th>
                <th class="px-3 py-2 border">Name</th>
                <th class="px-3 py-2 border">Description</th>
                <th class="px-3 py-2 border">Value</th>
                <th class="px-3 py-2 border">Status</th>
                <th class="px-3 py-2 border">File</th>
                <th class="px-3 py-2 border">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="config in configurations"
                :key="config.id"
                class="hover:bg-gray-50"
              >
                <td class="px-3 py-2 border">{{ config.id }}</td>

                <td class="px-3 py-2 border font-medium">
                  {{ config.name }}
                </td>

                <td class="px-3 py-2 border text-sm text-gray-600">
                  {{ config.description }}
                </td>

                <td class="px-3 py-2 border">
                  <span class="px-2 py-1 text-xs rounded bg-gray-100">
                    {{ config.value }}
                  </span>
                </td>

                <td class="px-3 py-2 border">
                  <span
                    v-if="Number(config.status) === 1"
                    class="text-green-600 font-semibold"
                  >
                    Enabled
                  </span>
                  <span
                    v-else
                    class="text-red-600 font-semibold"
                  >
                    Disabled
                  </span>
                </td>

                <td class="px-3 py-2 border text-sm">
                  <template v-if="config.file_path">
                    <a
                      :href="`/storage/${config.file_path}`"
                      target="_blank"
                      class="text-blue-600 hover:underline"
                    >
                      View File
                    </a>
                  </template>
                  <template v-else>
                    <span class="text-gray-400">No file</span>
                  </template>
                </td>

                <td class="px-3 py-2 border">
                  <button
                    class="flex items-center gap-1 px-2 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                    @click="editConfig(config)"
                  >
                    <i class="fa fa-edit"></i>
                    Edit
                  </button>
                </td>
              </tr>

              <tr v-if="configurations.length === 0">
                <td colspan="7" class="text-center py-6 text-gray-400">
                  No configurations found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Add Modal -->
        <div
          v-if="showAddModal"
          class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
          <div class="bg-white rounded-lg w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <i class="fa fa-plus"></i>
              New Configuration
            </h2>

            <form @submit.prevent="saveConfig" class="space-y-4">
              <div>
                <label class="block text-sm font-medium mb-1">Name</label>
                <input
                  v-model="newForm.name"
                  type="text"
                  class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Description</label>
                <textarea
                  v-model="newForm.description"
                  rows="3"
                  class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                ></textarea>
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Value</label>
                <input
                  v-model="newForm.value"
                  type="text"
                  class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Status</label>
                <select
                  v-model="newForm.status"
                  class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                >
                  <option :value="1">Enabled</option>
                  <option :value="0">Disabled</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Upload File</label>
                <input
                  type="file"
                  @change="handleNewFileUpload"
                  class="w-full border rounded px-3 py-2 text-sm bg-white"
                />
                <p v-if="newForm.fileName" class="text-xs text-gray-500 mt-1">
                  Selected: {{ newForm.fileName }}
                </p>
              </div>

              <div class="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
                  @click="showAddModal = false"
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

        <!-- Edit Modal -->
        <div
          v-if="showEditModal"
          class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
          <div class="bg-white rounded-lg w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
            <h2 class="text-xl font-semibold mb-4">
              Edit Configuration
            </h2>

            <form @submit.prevent="updateConfig" class="space-y-4">
              <div>
                <label class="block text-sm font-medium mb-1">Name</label>
                <input
                  v-model="editForm.name"
                  type="text"
                  class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Description</label>
                <textarea
                  v-model="editForm.description"
                  rows="3"
                  class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                ></textarea>
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Value</label>
                <input
                  v-model="editForm.value"
                  type="text"
                  class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Status</label>
                <select
                  v-model="editForm.status"
                  class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                >
                  <option :value="1">Enabled</option>
                  <option :value="0">Disabled</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Current File</label>
                <div class="text-sm">
                  <template v-if="editForm.current_file_path">
                    <a
                      :href="`/storage/${editForm.current_file_path}`"
                      target="_blank"
                      class="text-blue-600 hover:underline"
                    >
                      View Current File
                    </a>
                  </template>
                  <template v-else>
                    <span class="text-gray-400">No file uploaded</span>
                  </template>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Replace File</label>
                <input
                  type="file"
                  @change="handleEditFileUpload"
                  class="w-full border rounded px-3 py-2 text-sm bg-white"
                />
                <p v-if="editForm.fileName" class="text-xs text-gray-500 mt-1">
                  Selected: {{ editForm.fileName }}
                </p>
              </div>

              <div class="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
                  @click="showEditModal = false"
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
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const page = usePage()
const configurations = computed(() => page.props.configurations ?? [])

const showAddModal = ref(false)
const showEditModal = ref(false)

const newForm = ref({
  name: '',
  description: '',
  value: '',
  status: 1,
  file: null,
  fileName: '',
})

const editForm = ref({
  id: null,
  name: '',
  description: '',
  value: '',
  status: 1,
  file: null,
  fileName: '',
  current_file_path: '',
})

const resetNewForm = () => {
  newForm.value = {
    name: '',
    description: '',
    value: '',
    status: 1,
    file: null,
    fileName: '',
  }
}

const openAddModal = () => {
  resetNewForm()
  showAddModal.value = true
}

const handleNewFileUpload = (event) => {
  const file = event.target.files[0]
  newForm.value.file = file ?? null
  newForm.value.fileName = file ? file.name : ''
}

const handleEditFileUpload = (event) => {
  const file = event.target.files[0]
  editForm.value.file = file ?? null
  editForm.value.fileName = file ? file.name : ''
}

const saveConfig = () => {
  const formData = new FormData()
  formData.append('name', newForm.value.name)
  formData.append('description', newForm.value.description ?? '')
  formData.append('value', newForm.value.value)
  formData.append('status', newForm.value.status)

  if (newForm.value.file) {
    formData.append('file', newForm.value.file)
  }

  router.post('/admin/configurations', formData, {
    forceFormData: true,
    onSuccess: () => {
      showAddModal.value = false
      resetNewForm()
    },
  })
}

const editConfig = (config) => {
  editForm.value = {
    id: config.id,
    name: config.name ?? '',
    description: config.description ?? '',
    value: config.value ?? '',
    status: Number(config.status),
    file: null,
    fileName: '',
    current_file_path: config.file_path ?? '',
  }
  showEditModal.value = true
}

const updateConfig = () => {
  const formData = new FormData()
  formData.append('_method', 'put')
  formData.append('name', editForm.value.name)
  formData.append('description', editForm.value.description ?? '')
  formData.append('value', editForm.value.value)
  formData.append('status', editForm.value.status)

  if (editForm.value.file) {
    formData.append('file', editForm.value.file)
  }

  router.post(`/admin/configurations/${editForm.value.id}`, formData, {
    forceFormData: true,
    onSuccess: () => {
      showEditModal.value = false
    },
  })
}
</script>

<style scoped>
body {
  font-family: 'Poppins', sans-serif;
}
</style>
