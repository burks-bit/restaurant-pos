<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50 max-h-screen">
        <h1 class="text-2xl font-semibold mb-4 text-gray-900">
          Users Management
        </h1>

        <div class="flex justify-between mb-4 flex-wrap gap-2">
          <input
            v-model="search"
            type="text"
            placeholder="Search users…"
            class="border rounded px-3 py-1 text-sm w-64 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
          />

          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-sm text-gray-600">
              Selected: <strong>{{ selectedUsers.length }}</strong>
            </span>

            <button
              class="flex items-center gap-1 text-sm bg-green-600 text-white px-2.5 py-1.5 rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="selectedUsers.length === 0"
              @click="printSelectedAccounts"
            >
              <i class="fa fa-print"></i>
              Print List of Accounts
            </button>

            <button
              class="flex items-center gap-1 text-sm bg-blue-600 text-white px-2.5 py-1.5 rounded hover:bg-blue-700"
              @click="createUser"
            >
              <i class="fa fa-user-plus"></i>
              Add User
            </button>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-auto max-h-[60vh]">
          <table class="min-w-full table-auto border-collapse">
            <thead class="sticky top-0 bg-gray-100 z-10">
              <tr class="text-gray-900 text-left">
                <th class="px-2 py-1 border w-12 text-center">
                  <input
                    type="checkbox"
                    :checked="isAllFilteredSelected"
                    :indeterminate="isIndeterminate"
                    @change="toggleCheckAll"
                  />
                </th>
                <th class="px-2 py-1 border">ID</th>
                <th class="px-2 py-1 border">Name</th>
                <th class="px-2 py-1 border">Email</th>
                <th class="px-2 py-1 border">Role</th>
                <th class="px-2 py-1 border">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="user in filteredUsers"
                :key="user.id"
                class="hover:bg-gray-50"
              >
                <td class="px-2 py-1 border text-center">
                  <input
                    type="checkbox"
                    :value="user.id"
                    v-model="selectedUserIds"
                  />
                </td>
                <td class="px-2 py-1 border">{{ user.id }}</td>
                <td class="px-2 py-1 border">{{ user.name }}</td>
                <td class="px-2 py-1 border">{{ user.email }}</td>
                <td class="px-2 py-1 border">
                  {{ ROLE_LABELS[user.role] ?? 'Unknown' }}
                </td>
                <td class="px-2 py-1 border flex gap-2">
                  <button
                    class="flex items-center gap-1 px-1.5 py-0.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                    @click="editUser(user)"
                  >
                    <span class="fa fa-edit"></span>
                    Edit
                  </button>
                </td>
              </tr>

              <tr v-if="filteredUsers.length === 0">
                <td colspan="6" class="text-center py-6 text-gray-400">
                  No users found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Add User Modal -->
        <div
          v-if="showAddModal"
          class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
          <div class="bg-white rounded-lg w-full max-w-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <i class="fa fa-user-plus"></i>
              Add New User
            </h2>

            <form class="space-y-4" @submit.prevent="saveUser">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input
                  type="text"
                  v-model="newForm.name"
                  class="w-full border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 text-sm"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input
                  type="email"
                  v-model="newForm.email"
                  class="w-full border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 text-sm"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input
                  type="password"
                  v-model="newForm.password"
                  class="w-full border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 text-sm"
                  required
                />
              </div>

              <div class="mb-3">
                <label class="block text-sm font-medium mb-1">Role</label>
                <select
                  v-model="newForm.role"
                  class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                >
                  <option value="0">Admin</option>
                  <option value="1">Manager</option>
                  <option value="2">Cashier</option>
                  <option value="3">Front Door</option>
                  <option value="4">Purchaser</option>
                  <option value="5">Kitchen</option>
                  <option value="6">Finance</option>
                  <option value="7">HR</option>
                  <option value="8">Employee</option>
                </select>
              </div>

              <div class="flex justify-end gap-2 mt-4">
                <button
                  type="button"
                  class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
                  @click="showAddModal = false"
                >
                  <span class="fa fa-close"></span> Cancel
                </button>
                <button
                  type="submit"
                  class="flex items-center gap-1 px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                  <span class="fa fa-save"></span>
                  Save
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Edit User Modal -->
        <div
          v-if="showEditModal"
          class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
        >
          <div class="bg-white rounded-lg w-full max-w-6xl p-6 max-h-[92vh] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between mb-4 border-b pb-3">
              <div>
                <h2 class="text-2xl font-semibold text-gray-900">
                  Edit User
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                  Update user details and route access
                </p>
              </div>

              <button
                type="button"
                class="px-3 py-2 bg-gray-100 rounded hover:bg-gray-200"
                @click="showEditModal = false"
              >
                <span class="fa fa-close"></span>
              </button>
            </div>

            <form class="flex-1 overflow-hidden flex flex-col" @submit.prevent="updateUser">
              <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 overflow-hidden flex-1">
                <!-- Left: user info -->
                <div class="xl:col-span-1 space-y-4 overflow-y-auto pr-1">
                  <div class="bg-gray-50 rounded-lg border p-4">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3">User Details</h3>

                    <div class="mb-3">
                      <label class="block text-sm font-medium mb-1">Name</label>
                      <input
                        type="text"
                        v-model="editForm.name"
                        class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                        required
                      />
                    </div>

                    <div class="mb-3">
                      <label class="block text-sm font-medium mb-1">Email</label>
                      <input
                        type="email"
                        v-model="editForm.email"
                        class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                        required
                      />
                    </div>

                    <div class="mb-3">
                      <label class="block text-sm font-medium mb-1">Password</label>
                      <input
                        type="password"
                        v-model="editForm.password"
                        placeholder="Leave blank to keep current password"
                        class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                      />
                    </div>

                    <div class="mb-1">
                      <label class="block text-sm font-medium mb-1">Role</label>
                      <select
                        v-model="editForm.role"
                        class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                      >
                        <option value="0">Admin</option>
                        <option value="1">Manager</option>
                        <option value="2">Cashier</option>
                        <option value="3">Front Door</option>
                        <option value="4">Purchaser</option>
                        <option value="5">Kitchen</option>
                        <option value="6">Finance</option>
                        <option value="7">HR</option>
                        <option value="8">Employee</option>
                      </select>
                    </div>
                  </div>

                  <div class="bg-blue-50 rounded-lg border border-blue-100 p-4">
                    <h3 class="text-sm font-semibold text-blue-900 mb-2">Selected Routes</h3>
                    <div class="text-2xl font-bold text-blue-700">
                      {{ editForm.route_ids.length }}
                    </div>
                    <p class="text-xs text-blue-700 mt-1">
                      Total checked route access
                    </p>
                  </div>
                </div>

                <!-- Right: route access -->
                <div class="xl:col-span-2 overflow-hidden flex flex-col">
                  <div class="bg-gray-50 rounded-lg border p-4 h-full flex flex-col overflow-hidden">
                    <div class="flex items-center justify-between mb-4">
                      <h3 class="text-sm font-semibold text-gray-800">Route Access</h3>

                      <div class="flex gap-2">
                        <button
                          type="button"
                          class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded hover:bg-blue-700"
                          @click="selectAllRoutes"
                        >
                          Select All
                        </button>
                        <button
                          type="button"
                          class="px-3 py-1.5 text-xs bg-gray-200 text-gray-800 rounded hover:bg-gray-300"
                          @click="clearAllRoutes"
                        >
                          Clear All
                        </button>
                      </div>
                    </div>

                    <div class="overflow-y-auto pr-1 space-y-4">
                      <div
                        v-for="group in groupedRoutes"
                        :key="group.key"
                        class="border rounded-lg bg-white"
                      >
                        <div class="flex items-center justify-between px-4 py-3 bg-gray-100 border-b">
                          <div>
                            <h4 class="font-semibold text-gray-900">
                              {{ group.label }}
                            </h4>
                            <p class="text-xs text-gray-500">
                              {{ group.routes.length }} routes
                            </p>
                          </div>

                          <div class="flex gap-2">
                            <button
                              type="button"
                              class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200"
                              @click="selectGroupRoutes(group)"
                            >
                              Check All
                            </button>
                            <button
                              type="button"
                              class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200"
                              @click="clearGroupRoutes(group)"
                            >
                              Clear
                            </button>
                          </div>
                        </div>

                        <div class="p-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                          <label
                            v-for="route in group.routes"
                            :key="route.id"
                            class="flex items-start gap-3 rounded border px-3 py-2 hover:bg-gray-50 cursor-pointer"
                          >
                            <input
                              type="checkbox"
                              :value="route.id"
                              v-model="editForm.route_ids"
                              class="mt-1"
                            />

                            <div class="min-w-0">
                              <div class="text-sm font-medium text-gray-900">
                                {{ route.route_name }}
                              </div>
                              <div class="text-xs text-gray-500 break-all">
                                {{ route.route }}
                              </div>
                            </div>
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="flex justify-end gap-2 mt-6 pt-4 border-t">
                <button
                  type="button"
                  class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
                  @click="showEditModal = false"
                >
                  <span class="fa fa-close"></span> Cancel
                </button>
                <button
                  type="submit"
                  class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                  <span class="fa fa-save"></span> Save
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
import { usePage } from '@inertiajs/vue3'
import { Inertia } from '@inertiajs/inertia'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const page = usePage()
const users = computed(() => page.props.users ?? [])
const app_routes = computed(() => page.props.app_routes ?? [])
const user_accesses = computed(() => page.props.user_accesses ?? [])

const search = ref('')
const showAddModal = ref(false)
const showEditModal = ref(false)
const selectedUserIds = ref([])

const ROLE_LABELS = {
  0: 'Admin',
  1: 'Manager',
  2: 'Cashier',
  3: 'Front Door',
  4: 'Purchaser',
  5: 'Kitchen',
  6: 'Finance',
  7: 'HR',
  8: 'Employee',
}

const GROUP_LABELS = {
  dashboard: 'Shared',
  profile: 'Shared',
  admin: 'Admin',
  manager: 'Manager',
  cashier: 'Cashier',
  frontdoor: 'Front Door',
  purchaser: 'Purchaser',
  kitchen: 'Kitchen',
  finance: 'Finance',
  hr: 'HR',
}

const newForm = ref({
  name: '',
  email: '',
  password: '',
  role: 2,
})

const editForm = ref({
  id: null,
  name: '',
  email: '',
  password: '',
  role: 2,
  route_ids: [],
})

const filteredUsers = computed(() => {
  if (!search.value) return users.value

  return users.value.filter((u) =>
    u.name.toLowerCase().includes(search.value.toLowerCase()) ||
    u.email.toLowerCase().includes(search.value.toLowerCase())
  )
})

const selectedUsers = computed(() => {
  return filteredUsers.value.filter((user) =>
    selectedUserIds.value.includes(user.id)
  )
})

const isAllFilteredSelected = computed(() => {
  if (filteredUsers.value.length === 0) return false
  return filteredUsers.value.every((user) =>
    selectedUserIds.value.includes(user.id)
  )
})

const isIndeterminate = computed(() => {
  return selectedUsers.value.length > 0 && !isAllFilteredSelected.value
})

const groupedRoutes = computed(() => {
  const groups = {}

  for (const route of app_routes.value) {
    const key = getRouteGroupKey(route.route)

    if (!groups[key]) {
      groups[key] = {
        key,
        label: GROUP_LABELS[key] ?? formatGroupLabel(key),
        routes: [],
      }
    }

    groups[key].routes.push(route)
  }

  const order = [
    'dashboard',
    'profile',
    'cashier',
    'frontdoor',
    'manager',
    'purchaser',
    'kitchen',
    'finance',
    'hr',
    'admin',
    'other',
  ]

  return Object.values(groups).sort((a, b) => {
    const aIndex = order.indexOf(a.key) === -1 ? 999 : order.indexOf(a.key)
    const bIndex = order.indexOf(b.key) === -1 ? 999 : order.indexOf(b.key)
    return aIndex - bIndex
  })
})

function getRouteGroupKey(routeName) {
  if (!routeName) return 'other'

  const parts = routeName.split('.')
  const first = parts[0]

  if (GROUP_LABELS[first]) return first
  return 'other'
}

function formatGroupLabel(key) {
  if (!key) return 'Other'
  return key.charAt(0).toUpperCase() + key.slice(1)
}

function toggleCheckAll(event) {
  const checked = event.target.checked
  const filteredIds = filteredUsers.value.map((user) => user.id)

  if (checked) {
    selectedUserIds.value = [...new Set([...selectedUserIds.value, ...filteredIds])]
  } else {
    selectedUserIds.value = selectedUserIds.value.filter(
      (id) => !filteredIds.includes(id)
    )
  }
}

function createUser() {
  newForm.value = { name: '', email: '', password: '', role: 2 }
  showAddModal.value = true
}

function editUser(user) {
  const selectedAccessRouteIds = (user_accesses.value ?? [])
    .filter((access) => access.user_id === user.id)
    .map((access) => access.route_id)

  editForm.value = {
    id: user.id,
    name: user.name,
    email: user.email,
    role: String(user.role),
    password: '',
    route_ids: selectedAccessRouteIds,
  }

  showEditModal.value = true
}

function selectAllRoutes() {
  editForm.value.route_ids = app_routes.value.map((route) => route.id)
}

function clearAllRoutes() {
  editForm.value.route_ids = []
}

function selectGroupRoutes(group) {
  const groupIds = group.routes.map((route) => route.id)
  editForm.value.route_ids = [...new Set([...editForm.value.route_ids, ...groupIds])]
}

function clearGroupRoutes(group) {
  const groupIds = group.routes.map((route) => route.id)
  editForm.value.route_ids = editForm.value.route_ids.filter(
    (id) => !groupIds.includes(id)
  )
}

function saveUser() {
  Inertia.post('/admin/users', newForm.value, {
    onSuccess: () => (showAddModal.value = false),
  })
}

function updateUser() {
  Inertia.put(`/admin/users/${editForm.value.id}`, editForm.value, {
    onSuccess: () => (showEditModal.value = false),
  })
}

function printSelectedAccounts() {
  if (selectedUsers.value.length === 0) {
    alert('Please select at least one account to print.')
    return
  }

  const printWindow = window.open('', '_blank', 'width=900,height=700')

  const rows = selectedUsers.value
    .map(
      (user, index) => `
        <tr>
          <td>${index + 1}</td>
          <td>${user.name}</td>
          <td>${user.email}</td>
          <td>1</td>
        </tr>
      `
    )
    .join('')

  printWindow.document.write(`
    <html>
      <head>
        <title>Printed List of Accounts</title>
        <style>
          body {
            font-family: Arial, sans-serif;
            padding: 24px;
            color: #111827;
          }
          h1 {
            margin-bottom: 6px;
            font-size: 22px;
          }
          .sub {
            margin-bottom: 18px;
            color: #6b7280;
            font-size: 13px;
          }
          table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
          }
          th, td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            text-align: left;
            font-size: 13px;
          }
          th {
            background: #f3f4f6;
          }
          .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #6b7280;
          }
        </style>
      </head>
      <body>
        <h1>List of Selected Accounts</h1>
        <div class="sub">Total selected accounts: ${selectedUsers.value.length}</div>

        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Default Password</th>
            </tr>
          </thead>
          <tbody>
            ${rows}
          </tbody>
        </table>

        <div class="footer">
          Printed on: ${new Date().toLocaleString()}
        </div>
      </body>
    </html>
  `)

  printWindow.document.close()
  printWindow.focus()
  printWindow.print()
}
</script>

<style scoped>
body {
  font-family: 'Poppins', sans-serif;
}
</style>