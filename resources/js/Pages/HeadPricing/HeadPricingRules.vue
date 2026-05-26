<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <div class="min-h-screen bg-gray-50 p-4">
        <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900">
              Pricing Schemes Management
            </h1>
            <p class="text-sm text-gray-500">
              Manage pricing schemes and head pricing rules per scheme.
            </p>
          </div>

          <div class="flex flex-col gap-2 sm:flex-row">
            <input
              v-model="search"
              type="text"
              placeholder="Search scheme or rule..."
              class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 sm:w-72"
            />

            <button
              class="inline-flex items-center justify-center gap-2 rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
              @click="createScheme"
            >
              <i class="fa fa-plus"></i>
              Add Pricing Scheme
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6">
          <div
            v-for="scheme in filteredPricingSchemes"
            :key="scheme.id"
            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow"
          >
            <div class="flex flex-col gap-3 border-b bg-gray-50 px-4 py-4 md:flex-row md:items-start md:justify-between">
              <div>
                <div class="flex flex-wrap items-center gap-2">
                  <h2 class="text-lg font-semibold text-gray-900">
                    {{ scheme.name }}
                  </h2>

                  <span
                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="scheme.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                  >
                    {{ scheme.is_active ? 'Active' : 'Inactive' }}
                  </span>

                  <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium uppercase text-blue-700">
                    {{ scheme.type }}
                  </span>
                </div>

                <p class="mt-1 text-sm text-gray-500">
                  {{ scheme.description || 'No description' }}
                </p>
              </div>

              <div class="flex flex-wrap gap-2">
                <button
                  class="inline-flex items-center gap-1 rounded bg-blue-600 px-3 py-2 text-sm text-white hover:bg-blue-700"
                  @click="editScheme(scheme)"
                >
                  <i class="fa fa-edit"></i>
                  Edit Scheme
                </button>

                <button
                  class="inline-flex items-center gap-1 rounded bg-green-600 px-3 py-2 text-sm text-white hover:bg-green-700"
                  @click="createRule(scheme)"
                >
                  <i class="fa fa-plus"></i>
                  Add Rule
                </button>
              </div>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full border-collapse">
                <thead class="bg-gray-100">
                  <tr class="text-left text-sm text-gray-700">
                    <th class="border px-3 py-2">ID</th>
                    <th class="border px-3 py-2">Label</th>
                    <th class="border px-3 py-2">Conditions</th>
                    <th class="border px-3 py-2">Price</th>
                    <th class="border px-3 py-2">Active</th>
                    <th class="w-[1%] whitespace-nowrap border px-3 py-2">Actions</th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="rule in scheme.head_rules"
                    :key="rule.id"
                    class="text-sm hover:bg-gray-50"
                  >
                    <td class="border px-3 py-2">{{ rule.id }}</td>
                    <td class="border px-3 py-2 font-medium text-gray-900">{{ rule.label }}</td>
                    <td class="border px-3 py-2 text-gray-600">{{ getRuleConditionText(rule) }}</td>
                    <td class="border px-3 py-2">₱ {{ formatAmount(rule.price) }}</td>
                    <td class="border px-3 py-2">
                      <span
                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                        :class="rule.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                      >
                        {{ rule.is_active ? 'Yes' : 'No' }}
                      </span>
                    </td>
                    <td class="border px-3 py-2 whitespace-nowrap">
                      <div class="flex gap-2">
                        <button
                          class="inline-flex items-center gap-1 rounded bg-blue-600 px-2.5 py-1.5 text-xs text-white hover:bg-blue-700"
                          @click="editRule(scheme, rule)"
                        >
                          <i class="fa fa-edit"></i>
                          Edit
                        </button>

                        <button
                          class="inline-flex items-center gap-1 rounded bg-red-600 px-2.5 py-1.5 text-xs text-white hover:bg-red-700"
                          @click="deleteRule(rule)"
                        >
                          <i class="fa fa-trash"></i>
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>

                  <tr v-if="!scheme.head_rules || scheme.head_rules.length === 0">
                    <td colspan="6" class="border px-3 py-6 text-center text-sm text-gray-400">
                      No rules found for this pricing scheme.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div
            v-if="filteredPricingSchemes.length === 0"
            class="rounded-xl border border-gray-200 bg-white p-10 text-center text-gray-400 shadow"
          >
            No pricing schemes found.
          </div>
        </div>
      </div>
    </div>

    <!-- Add Pricing Scheme -->
    <div
      v-if="showAddSchemeModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
        <h2 class="mb-4 flex items-center gap-2 text-xl font-semibold text-gray-900">
          <i class="fa fa-plus"></i>
          Add Pricing Scheme
        </h2>

        <form class="space-y-4" @submit.prevent="saveScheme">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Name</label>
            <input
              v-model="newSchemeForm.name"
              type="text"
              class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600"
            />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Type</label>
            <select
              v-model="newSchemeForm.type"
              class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600"
            >
              <option value="regular">Regular</option>
              <option value="promo">Promo</option>
              <option value="event">Event</option>
            </select>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
            <textarea
              v-model="newSchemeForm.description"
              rows="3"
              class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600"
            ></textarea>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Active</label>
            <select
              v-model="newSchemeForm.is_active"
              class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600"
            >
              <option :value="1">Yes</option>
              <option :value="0">No</option>
            </select>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button
              type="button"
              class="rounded bg-gray-200 px-4 py-2 text-sm hover:bg-gray-300"
              @click="closeAddSchemeModal"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
            >
              Save Scheme
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Pricing Scheme -->
    <div
      v-if="showEditSchemeModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
        <h2 class="mb-4 flex items-center gap-2 text-xl font-semibold text-gray-900">
          <i class="fa fa-edit"></i>
          Edit Pricing Scheme
        </h2>

        <form class="space-y-4" @submit.prevent="updateScheme">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Name</label>
            <input
              v-model="editSchemeForm.name"
              type="text"
              class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600"
            />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Type</label>
            <select
              v-model="editSchemeForm.type"
              class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600"
            >
              <option value="regular">Regular</option>
              <option value="promo">Promo</option>
              <option value="event">Event</option>
            </select>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
            <textarea
              v-model="editSchemeForm.description"
              rows="3"
              class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600"
            ></textarea>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Active</label>
            <select
              v-model="editSchemeForm.is_active"
              class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600"
            >
              <option :value="1">Yes</option>
              <option :value="0">No</option>
            </select>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button
              type="button"
              class="rounded bg-gray-200 px-4 py-2 text-sm hover:bg-gray-300"
              @click="closeEditSchemeModal"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
            >
              Update Scheme
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add Rule -->
    <div
      v-if="showAddRuleModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <div class="w-full max-w-xl rounded-lg bg-white p-6 shadow-xl">
        <h2 class="mb-1 text-xl font-semibold text-gray-900">Add Rule</h2>
        <p class="mb-4 text-sm text-gray-500">
          Scheme: <span class="font-medium">{{ selectedScheme?.name }}</span>
        </p>

        <form class="space-y-4" @submit.prevent="saveRule">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Label</label>
            <input
              v-model="newRuleForm.label"
              type="text"
              class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600"
            />
          </div>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Min Age</label>
              <input v-model.number="newRuleForm.min_age" type="number" class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Max Age</label>
              <input v-model.number="newRuleForm.max_age" type="number" class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600" />
            </div>
          </div>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Min Height (ft)</label>
              <input v-model.number="newRuleForm.min_height" type="number" step="0.01" class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Max Height (ft)</label>
              <input v-model.number="newRuleForm.max_height" type="number" step="0.01" class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600" />
            </div>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Price</label>
            <input v-model.number="newRuleForm.price" type="number" step="0.01" class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600" />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Active</label>
            <select v-model="newRuleForm.is_active" class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600">
              <option :value="1">Yes</option>
              <option :value="0">No</option>
            </select>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="rounded bg-gray-200 px-4 py-2 text-sm hover:bg-gray-300" @click="closeAddRuleModal">
              Cancel
            </button>
            <button type="submit" class="rounded bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700">
              Save Rule
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Rule -->
    <div
      v-if="showEditRuleModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <div class="w-full max-w-xl rounded-lg bg-white p-6 shadow-xl">
        <h2 class="mb-1 text-xl font-semibold text-gray-900">Edit Rule</h2>
        <p class="mb-4 text-sm text-gray-500">
          Scheme: <span class="font-medium">{{ selectedScheme?.name }}</span>
        </p>

        <form class="space-y-4" @submit.prevent="updateRule">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Label</label>
            <input
              v-model="editRuleForm.label"
              type="text"
              class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600"
            />
          </div>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Min Age</label>
              <input v-model.number="editRuleForm.min_age" type="number" class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Max Age</label>
              <input v-model.number="editRuleForm.max_age" type="number" class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600" />
            </div>
          </div>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Min Height (ft)</label>
              <input v-model.number="editRuleForm.min_height" type="number" step="0.01" class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Max Height (ft)</label>
              <input v-model.number="editRuleForm.max_height" type="number" step="0.01" class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600" />
            </div>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Price</label>
            <input v-model.number="editRuleForm.price" type="number" step="0.01" class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600" />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Active</label>
            <select v-model="editRuleForm.is_active" class="w-full rounded border px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600">
              <option :value="1">Yes</option>
              <option :value="0">No</option>
            </select>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="rounded bg-gray-200 px-4 py-2 text-sm hover:bg-gray-300" @click="closeEditRuleModal">
              Cancel
            </button>
            <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
              Update Rule
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useHeadPricingRulesIndex } from '@/Composables/head_pricing_rules/useHeadPricingRulesIndex'

const {
  search,
  filteredPricingSchemes,

  showAddSchemeModal,
  showEditSchemeModal,
  showAddRuleModal,
  showEditRuleModal,

  selectedScheme,
  newSchemeForm,
  editSchemeForm,
  newRuleForm,
  editRuleForm,

  createScheme,
  closeAddSchemeModal,
  saveScheme,

  editScheme,
  closeEditSchemeModal,
  updateScheme,

  createRule,
  closeAddRuleModal,
  saveRule,

  editRule,
  closeEditRuleModal,
  updateRule,
  deleteRule,

  formatAmount,
  getRuleConditionText,
} = useHeadPricingRulesIndex()
</script>