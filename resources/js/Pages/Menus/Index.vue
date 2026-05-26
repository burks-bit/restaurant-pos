<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50 min-h-screen">
        <h1 class="text-2xl font-semibold mb-4 text-gray-900">
          Menu Management
        </h1>

        <div class="flex justify-between mb-4 flex-wrap gap-2">
          <input
            v-model="search"
            type="text"
            placeholder="Search menu…"
            class="border rounded px-3 py-1 text-sm w-64 focus:ring-2 focus:ring-blue-600"
          />

          <button
            class="flex items-center gap-2 text-sm bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700"
            @click="createMenu"
          >
            <i class="fa fa-plus"></i>
            Add Menu
          </button>
        </div>

        <div class="bg-white rounded-lg shadow overflow-auto max-h-[60vh]">
          <table class="min-w-full table-auto border-collapse text-sm">
            <thead class="sticky top-0 bg-gray-100 z-10">
              <tr>
                <th class="px-2 py-1 border">ID</th>
                <th class="px-2 py-1 border">Image</th>
                <th class="px-2 py-1 border">Name</th>
                <th class="px-2 py-1 border">Category</th>
                <th class="px-2 py-1 border">Price</th>
                <th class="px-2 py-1 border">Available</th>
                <th class="px-2 py-1 border">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="menu in filteredMenus" :key="menu.id">
                <td class="px-2 py-1 border">{{ menu.id }}</td>

                <td class="px-2 py-1 border">
                  <img
                    v-if="menu.image_path"
                    :src="`/storage/${menu.image_path}`"
                    class="w-12 h-12 object-cover rounded"
                  />
                  <span v-else class="text-gray-400 text-xs">No Image</span>
                </td>

                <td class="px-2 py-1 border">{{ menu.name }}</td>
                <td class="px-2 py-1 border">{{ getCategoryName(menu.category_id) }}</td>
                <td class="px-2 py-1 border">₱{{ Number(menu.price).toFixed(2) }}</td>
                <td class="px-2 py-1 border">
                  <span :class="menu.is_available ? 'text-green-600' : 'text-red-600'">
                    {{ menu.is_available ? 'Yes' : 'No' }}
                  </span>
                </td>
                <td class="px-2 py-1 border flex gap-2">
                  <button
                    class="bg-blue-600 text-white px-2 py-1 rounded text-sm"
                    @click="editMenu(menu)"
                  >
                    <span class="fa fa-edit"></span>
                    Edit
                  </button>
                  <button
                    class="bg-red-600 text-white px-2 py-1 rounded text-sm"
                    @click="deleteMenu(menu)"
                  >
                    <span class="fa fa-trash"></span>
                    Delete
                  </button>
                </td>
              </tr>

              <tr v-if="filteredMenus.length === 0">
                <td colspan="7" class="text-center py-6 text-gray-400">
                  No menu items found
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div v-if="showAddModal" class="modal">
      <div class="modal-box">
        <h2 class="text-xl font-semibold mb-4">Add Menu</h2>

        <input type="file" accept="image/*" @change="onImageChange($event, 'add')" class="input" />
        <img v-if="addImagePreview" :src="addImagePreview" class="preview-img" />

        <input v-model="newForm.name" placeholder="Name" class="input" />
        <input v-model="newForm.price" type="number" step="0.01" placeholder="Price" class="input" />

        <select v-model="newForm.category_id" class="input">
          <option disabled value="">Select Category</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">
            {{ cat.name }}
          </option>
        </select>

        <label>
          <input type="checkbox" v-model="newForm.is_available" /> Available
        </label>

        <div class="flex justify-end gap-2 mt-4">
          <button @click="closeAddModal" class="btn-gray">
            <span class="fa fa-close"></span>
            Cancel
          </button>
          <button @click="addMenu" class="btn-blue">
            <span class="fa fa-save"></span>
            Save
          </button>
        </div>
      </div>
    </div>

    <div v-if="showModal" class="modal">
      <div class="modal-box">
        <h2 class="text-xl font-semibold mb-4">Edit Menu</h2>

        <input type="file" accept="image/*" @change="onImageChange($event, 'edit')" />
        <img v-if="editImagePreview" :src="editImagePreview" class="preview-img" />

        <input v-model="editForm.name" class="input" />
        <input v-model="editForm.price" type="number" step="0.01" class="input" />

        <select v-model="editForm.category_id" class="input">
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">
            {{ cat.name }}
          </option>
        </select>

        <label>
          <input type="checkbox" v-model="editForm.is_available" /> Available
        </label>

        <div class="flex justify-end gap-2 mt-4">
          <button @click="closeEditModal" class="btn-gray">
            <span class="fa fa-close"></span>
            Cancel
          </button>
          <button @click="updateMenu" class="btn-blue">
            <span class="fa fa-save"></span>
            Update
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useMenusIndex } from '@/Composables/menus/useMenusIndex'

const {
  categories,
  search,
  showModal,
  showAddModal,
  addImagePreview,
  editImagePreview,
  newForm,
  editForm,
  filteredMenus,
  getCategoryName,
  onImageChange,
  createMenu,
  closeAddModal,
  addMenu,
  editMenu,
  closeEditModal,
  updateMenu,
  deleteMenu,
} = useMenusIndex()
</script>

<style scoped>
.modal {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50;
}
.modal-box {
  @apply bg-white rounded-lg w-full max-w-lg p-6;
}
.input {
  @apply w-full border rounded px-3 py-2 mb-3;
}
.preview-img {
  @apply w-24 h-24 object-cover rounded my-2;
}
.btn-blue {
  @apply bg-blue-600 text-white px-3 py-1 rounded;
}
.btn-gray {
  @apply bg-gray-300 px-3 py-1 rounded;
}
</style>