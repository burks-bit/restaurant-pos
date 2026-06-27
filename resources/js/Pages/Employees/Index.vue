<template>
  <AuthenticatedLayout>
    <div class="container mx-auto py-6">
      <div class="p-4 bg-gray-50">

        <!-- Page Title -->
        <h1 class="text-2xl font-semibold mb-4 text-gray-900">
          Employees
        </h1>

        <!-- Search + Add -->
        <div class="flex justify-between mb-4 flex-wrap gap-2">
          <input
            v-model="search"
            type="text"
            placeholder="Search employees…"
            class="border rounded px-3 py-1 text-sm w-64 focus:outline-none focus:ring-2 focus:ring-blue-600"
          />
          

          <div class="flex gap-2">
            <!-- Replace the select -->
            <select v-model="selectedBranch" class="border rounded px-4 py-2 text-sm w-64">
              <option value="">All Branches</option>  <!-- 👈 add this -->
              <option
                v-for="branch in branches"
                :key="branch.id"
                :value="branch.id"
              >
                {{ branch.name }}{{ branch.main == 1 ? ' - Main Branch' : '' }}
              </option>
            </select>

            <button
              class="flex items-center gap-1 text-sm bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700"
              @click="createEmployee"
            >
              <span class="fa fa-plus"></span>
              New Employee
            </button>
            <button
              class="flex items-center gap-1 text-sm bg-gray-800 text-white px-3 py-1.5 rounded hover:bg-gray-700"
              @click="printPosAccounts"
            >
              <span class="fa fa-print"></span>
              Print POS Accounts
            </button>
            <button
              class="flex items-center gap-1 text-sm bg-gray-800 text-white px-3 py-1.5 rounded hover:bg-gray-700"
              @click="printEmployeePersonalDetails"
            >
              <span class="fa fa-print"></span>
              Print Employee Personal Details
            </button>
            <button
              class="flex items-center gap-1 text-sm bg-gray-800 text-white px-3 py-1.5 rounded hover:bg-gray-700"
              @click="view201File(selectedEmployee)"
              :disabled="!selectedEmployee"
            >
              <span class="fa fa-list"></span> 201 File
            </button>
          </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow overflow-auto max-h-[60vh]">
          <table class="min-w-full table-auto border-collapse text-sm">
            <thead class="sticky top-0 bg-gray-100">
              <tr class="text-left">
                <th class="border px-2 py-1 w-12">ID</th>
                <th class="border px-2 py-1">Code</th>
                <th class="border px-2 py-1">Name</th>
                <th class="border px-2 py-1 w-24">Status</th>
                <th class="border px-2 py-1 w-1 whitespace-nowrap">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="employee in filteredEmployees"
                :key="employee.id"
                @click="selectedEmployee = employee"
                :class="{ 'bg-gray-100': selectedEmployee?.id === employee.id }"
              >
                <td class="border px-2 py-1">{{ employee.id }}</td>
                <td class="border px-2 py-1">{{ employee.employee_code }}</td>
                <td class="border px-2 py-1">
                  {{ employee.first_name }} {{ employee.last_name }}
                </td>
                <td class="border px-2 py-1 capitalize">
                  {{ employee.status }}
                </td>

                <!-- ✅ FIXED ACTIONS -->
                <td class="border px-2 py-1 whitespace-nowrap">
                  <div class="inline-flex gap-2">
                    <button
                      class="px-2 py-1 bg-pink-600 text-white rounded hover:bg-pink-700"
                      @click.stop="viewSystemAccount(employee)"
                    >
                      <span class="fa fa-eye"></span>
                    </button>
                    <button
                      class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
                      @click.stop="editEmployee(employee)"
                    >
                      <span class="fa fa-edit"></span> Edit
                    </button>

                    <button
                      class="px-2 py-1 bg-orange-600 text-white rounded hover:bg-orange-700"
                      @click.stop="viewEmployment(employee)"
                    >
                      <span class="fa fa-file"></span> Employment
                    </button>

                    <Link
                      :href="route(`${prefix}.employees.schedule`, { employee: employee.id })"
                      class="px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700 inline-block"
                    >
                      <span class="fa fa-eye"></span> Schedule
                    </Link>

                    <button
                      class="px-2 py-1 bg-violet-600 text-white rounded hover:bg-violet-700"
                      @click.stop="openDTR(employee)"
                    >
                      <span class="fa fa-clock"></span> DTR
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="filteredEmployees.length === 0">
                <td colspan="5" class="text-center py-6 text-gray-400">
                  No employees found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ================= EMPLOYEE MODAL ================= -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg w-full max-w-xl p-6">
            <h2 class="text-xl font-semibold mb-4">{{ isEditing ? 'Edit Employee' : 'Add Employee' }}</h2>
            <form @submit.prevent="submit">
              <div class="grid grid-cols-2 gap-4">

                <!-- Name & Contact Fields -->
                <input v-model="form.first_name" placeholder="First Name" class="border rounded px-3 py-2 text-sm" required />
                <input v-model="form.last_name" placeholder="Last Name" class="border rounded px-3 py-2 text-sm" required />
                <input v-model="form.middle_name" placeholder="Middle Name" class="border rounded px-3 py-2 text-sm" />
                <input v-model="form.email" type="email" placeholder="Email" class="border rounded px-3 py-2 text-sm" />
                <input v-model="form.contact_number" placeholder="Contact Number" class="border rounded px-3 py-2 text-sm" />
                
                <!-- Birth, Gender, Address -->
                <input v-model="form.birth_date" type="date" placeholder="Birth Date" class="border rounded px-3 py-2 text-sm" />
                <select v-model="form.gender" class="border rounded px-3 py-2 text-sm">
                  <!-- <option value="">Select Gender</option> -->
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                  <!-- <option value="other">Other</option> -->
                </select>
                <input v-model="form.address" placeholder="Address" class="border rounded px-3 py-2 text-sm" />
                
                <!-- Account Access -->
                <select v-model="form.access" class="border rounded px-3 py-2 text-sm">
                  <option
                    v-for="(label, value) in roles"
                    :key="value"
                    :value="Number(value)"
                  >
                    {{ label }}
                  </option>
                </select>

                <!-- Status -->
                <select v-model="form.status" class="border rounded px-3 py-2 text-sm">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
                
              </div>

              <div class="flex justify-end gap-2 mt-6">
                <button type="button" class="px-4 py-1 bg-gray-200 rounded" @click="closeEmployeeModal">
                  <span class="fa fa-close"></span> Cancel
                </button>
                <button type="submit" class="px-4 py-1 bg-blue-600 text-white rounded">
                  <span class="fa fa-save"></span>
                  Save
                </button>
              </div>
            </form>
          </div>
        </div>

        <div v-if="showAccountModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
          <div class="bg-white rounded-lg w-full max-w-xl p-6 shadow-lg">
            <!-- Header -->
            <h2 class="text-xl font-semibold mb-4">System Account Detail</h2>

            <!-- Account Info -->
            <div class="space-y-2 text-gray-700">
              <p><span class="font-large text-bold">NAME:</span> {{ systemAccount.user?.name }}</p>
              <p><span class="font-medium">USERNAME:</span> {{ systemAccount.user?.email }}</p>
              <p><span class="font-medium">DEFAULT PASSWORD:</span> 1</p>
            </div>

            <!-- Info Note -->
            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded text-sm text-gray-600">
              <strong>Note:</strong> If this is a newly created employee profile, the default password is <span class="font-semibold">1</span>.  
              For existing accounts with forgotten passwords, please contact the system administrator.
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-2 mt-6">
              <button 
                type="button" 
                class="px-2 py-1 hover:bg-gray-100 rounded transition"
                @click="closeAccountModal"
              >
                <span class="fa fa-close mr-1"></span> Cancel
              </button>
            </div>
          </div>
        </div>

        <div
          v-if="showAddEmploymentModal"
          class="fixed inset-0 z-[200] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
        >
          <div
            class="w-full max-w-3xl bg-white rounded-2xl shadow-2xl overflow-hidden"
          >
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50">
              <h3 class="text-lg font-semibold text-gray-800">
                Add Employment
              </h3>

              <button
                @click="showAddEmploymentModal = false"
                class="w-9 h-9 flex items-center justify-center rounded-lg hover:bg-gray-200 transition"
              >
                <span class="fa fa-times text-gray-500"></span>
              </button>
            </div>

            <!-- Body -->
            <div class="p-6 max-h-[75vh] overflow-y-auto">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Position -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Position
                  </label>

                  <select
                    v-model="newEmployment.position"
                    class="w-full border rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-400 outline-none"
                  >
                    <option disabled value="">
                      Select Position
                    </option>

                    <option
                      v-for="(label, value) in roles"
                      :key="value"
                      :value="Number(value)"
                    >
                      {{ label }}
                    </option>
                  </select>
                </div>

                <!-- Department -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Department
                  </label>

                  <input
                    v-model="newEmployment.department"
                    placeholder="Enter department"
                    class="w-full border rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-400 outline-none"
                  />
                </div>

                <!-- Hire Date -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Hire Date
                  </label>

                  <input
                    type="date"
                    v-model="newEmployment.hire_date"
                    class="w-full border rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-400 outline-none"
                  />
                </div>

                <!-- Salary -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Salary
                  </label>

                  <input
                    type="number"
                    v-model="newEmployment.salary"
                    placeholder="0.00"
                    class="w-full border rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-400 outline-none"
                  />
                </div>

                <!-- Daily Rate -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Daily Rate
                  </label>

                  <input
                    type="number"
                    v-model="newEmployment.daily_rate"
                    placeholder="0.00"
                    class="w-full border rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-400 outline-none"
                  />
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t bg-gray-50">
              <button
                @click="showAddEmploymentModal = false"
                class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-100 transition"
              >
                <span class="fa fa-times mr-1"></span>
                Cancel
              </button>

              <button
                class="px-5 py-2.5 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition"
                @click="saveEmployment"
              >
                <span class="fa fa-save mr-1"></span>
                Save Employment
              </button>
            </div>
          </div>
        </div>

        <!-- ================= EMPLOYMENT MODAL ================= -->
        <div
          v-if="showEmploymentModal"
          class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50"
        >
          <div class="bg-white rounded-2xl shadow-xl w-full max-w-5xl p-8 overflow-auto max-h-[92vh]">

            <!-- HEADER -->
            <div class="flex justify-between items-center mb-6">
              <h2 class="text-2xl font-bold text-gray-800">
                Employment History - <span class="text-blue-600">{{ selectedEmployee?.first_name }} {{ selectedEmployee?.last_name }}</span>
              </h2>
              <div class="flex items-center gap-4">
                <button
                  @click="addEmployment"
                  class="px-2 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                >
                  <span class="fa fa-plus"></span> New Employment
                </button>
                
                <!-- Close Button -->
                <button
                  @click="closeEmploymentModal"
                  class="text-red-400 hover:text-red-700 transition font-bold"
                >
                  ✕
                </button>
              </div>
            </div>
            
            <div class="mb-6">
              
            </div>

            <!-- ================= EMPLOYMENT TABLE ================= -->
            <div class="bg-gray-50 rounded-xl p-4 mb-8">
              <h3 class="font-semibold text-gray-700 mb-3">Employment Records</h3>

              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="border-b text-gray-600">
                      <th class="py-2 text-left">Position</th>
                      <th class="py-2 text-left">Department</th>
                      <th class="py-2 text-left">Hire Date</th>
                      <th class="py-2 text-left">Daily Rate</th>
                      <th class="py-2 text-left">Salary</th>
                      <th class="py-2 text-center">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="detail in selectedEmployee?.employment_details"
                      :key="detail.id"
                      class="border-b hover:bg-gray-100 transition cursor-pointer"
                      :class="{ 'bg-blue-50': selectedEmployment?.id === detail.id }"
                    >
                      <td class="py-2">
                        {{ roles[detail.position] || '—' }}
                      </td>
                      <td>{{ detail.department }}</td>
                      <td>{{ detail.hire_date ? new Date(detail.hire_date).toLocaleDateString('en-PH') : '—' }}</td>
                      <td>₱ {{ detail.daily_rate }}</td>
                      <td>₱ {{ detail.salary }}</td>
                      <td class="text-center">
                        <button
                          @click.stop="editEmployment(detail)"
                          class="px-2 py-1 bg-yellow-500 text-white rounded-lg text-xs hover:bg-yellow-600 transition"
                        >
                          <span class="fa fa-edit"></span>
                          Edit
                        </button>
                      </td>
                    </tr>

                    <tr v-if="!selectedEmployee?.employment_details?.length">
                      <td colspan="6" class="text-center py-4 text-gray-400">
                        No employment records found.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- ================= EDIT EMPLOYMENT ================= -->
            <div v-if="selectedEmployment" class="bg-blue-50 border border-blue-100 rounded-xl p-6 mb-8">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Edit Employment</h3>

              <!-- FORM -->
              <div class="grid grid-cols-2 gap-4 mb-6">
                <!-- <input v-model="employmentForm.position" placeholder="Position"
                  class="border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-400 outline-none" /> -->

                <select v-model="employmentForm.position" class="border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-400 outline-none">
                  <option
                    v-for="(label, value) in roles"
                    :key="value"
                    :value="Number(value)"
                  >
                    {{ label }}
                  </option>
                </select>
                
                <input v-model="employmentForm.department" placeholder="Department"
                  class="border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-400 outline-none" />
                <input type="date" v-model="employmentForm.hire_date"
                  class="border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-400 outline-none" />
                <input type="number" v-model="employmentForm.salary" placeholder="Salary"
                  class="border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-400 outline-none" />
                <input type="number" v-model="employmentForm.daily_rate" placeholder="Daily Rate"
                  class="border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-400 outline-none" />
              </div>

              <!-- DOCUMENTS -->
              <div class="bg-white rounded-xl border p-4">
                <h4 class="font-semibold text-gray-700 mb-3">Employment Document</h4>

                <div class="space-y-3">
                  <div
                    v-for="doc in documentTypes"
                    :key="doc"
                    class="flex items-center justify-between border rounded-lg px-4 py-2"
                  >
                    <div class="flex items-center gap-3">
                      <span class="text-sm font-medium text-gray-700">{{ doc }}</span>

                      <span
                        v-if="getDocument(doc)"
                        class="px-2 py-0.5 text-xs bg-green-100 text-green-700 rounded-full"
                      >
                        Uploaded
                        <a
                          :href="`/storage/${getDocument(doc).file_path}`"
                          target="_blank"
                          class="text-blue-600 underline hover:text-blue-800"
                        >
                          View
                        </a>
                      </span>
                      <span
                        v-else
                        class="px-2 py-0.5 text-xs bg-gray-200 text-gray-600 rounded-full"
                      >
                        Not Uploaded
                      </span>
                    </div>

                    <div class="flex gap-2">
                      <input
                        type="file"
                        :ref="el => fileInputs[doc] = el"
                        class="hidden"
                        @change="handleFileUpload(doc, $event)"
                      />

                      <button
                        @click="() => fileInputs[doc]?.click()"
                        class="px-3 py-1 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                      >
                        <span class="fa fa-file"></span>
                        {{ getDocument(doc) ? 'Replace' : 'Upload' }}
                      </button>

                      <button
                        v-if="getDocument(doc)"
                        @click="removeDocument(doc)"
                        class="px-3 py-1 text-xs bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                      > 
                        <span class="fa fa-remove"></span>
                        Remove
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ACTIONS -->
              <div class="flex justify-end gap-3 mt-6">
                <button
                  class="px-2 py-1 bg-gray-200 rounded-lg text-sm hover:bg-gray-300 transition"
                  @click="cancelEditEmployment"
                >
                  <span class="fa fa-close"></span>
                  Cancel
                </button>

                <button
                  class="px-2 py-1 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition"
                  @click="updateEmployment"
                >
                  <span class="fa fa-save"></span>
                  Save Changes
                </button>
              </div>
            </div>

          </div>
        </div>

        <!-- ================= 201 FILE MODAL ================= -->
        <div v-if="show201FileModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg w-full max-w-3xl p-6 overflow-auto max-h-[90vh]">
            <h2 class="text-xl font-semibold mb-4">
              201 File - {{ selectedEmployee?.first_name }} {{ selectedEmployee?.last_name }}
            </h2>

            <ul class="list-disc pl-5 text-sm space-y-1">
              <li>
                <strong>Personal Data:</strong> Application form, CV/Resume, Birth certificate, Photos
              </li>
              <li>
                <strong>Employment Records:</strong> Appointment letters, Job description, Service records, Contract
              </li>
              <li>
                <strong>Government Documents:</strong> TIN, SSS, PhilHealth, Pag-IBIG
              </li>
              <li>
                <strong>Performance & Training:</strong> Evaluations, Training records, Certificates
              </li>
              <li>
                <strong>Corrective Actions:</strong> Written reprimands, Memo, Incident reports
              </li>
              <li>
                <strong>Separation Documents:</strong> Resignation letter, Clearance forms, Final pay
              </li>
            </ul>

            <div class="flex justify-end gap-2 mt-6">
              <button class="px-4 py-1 bg-gray-200 rounded" @click="close201FileModal">
                <span class="fa fa-close"></span>
                Close
              </button>
            </div>
          </div>
        </div>

        <!-- ================= DTR MODAL ================= -->
        <div v-if="showDTRModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg w-full max-w-7xl p-6 overflow-auto max-h-[90vh]">

            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-semibold">
                DTR - {{ selectedEmployee?.first_name }} {{ selectedEmployee?.last_name }}
              </h2>
              <button @click="closeDTRModal" class="text-gray-400 hover:text-gray-700">✕</button>
            </div>
            
            <div class="flex items-center justify-end gap-2 mb-4 flex-wrap">
              <!-- Start Date -->
              <div>
                <label class="text-sm mr-1">From:</label>
                <input
                  type="date"
                  v-model="payrollStartDate"
                  class="border rounded px-2 py-1 text-sm"
                />
              </div>

              <!-- End Date -->
              <div>
                <label class="text-sm mr-1">To:</label>
                <input
                  type="date"
                  v-model="payrollEndDate"
                  class="border rounded px-2 py-1 text-sm"
                />
              </div>

              <!-- Filter Schedule Button -->
              <button
                @click="filterSchedules"
                class="px-2 py-1 bg-gray-600 text-white rounded hover:bg-gray-700"
              >
                <span class="fa fa-filter"></span>
                Filter Schedule
              </button>

              <!-- Print Button -->
              <!-- <button
                @click="printPayroll"
                class="px-2 py-1 bg-gray-700 text-white rounded hover:bg-gray-800"
              >
                <span class="fa fa-print mr-1"></span> Print Payroll Copy
              </button> -->
            </div>


            <v-form ref="dtrForm">
              <div class="border rounded-lg shadow-sm">

              <!-- SCROLL CONTAINER -->
              <div class="max-h-[500px] overflow-y-auto relative">

                <table class="min-w-full text-sm text-gray-700">

                  <!-- STICKY HEADER -->
                  <thead class="bg-gray-200 text-xs uppercase tracking-wider text-gray-700 sticky top-0 z-10">
                    <tr>
                      <th class="px-4 py-3 text-left">Date</th>
                      <th class="px-4 py-3 text-center">Scheduled In</th>
                      <th class="px-4 py-3 text-center">Scheduled Out</th>
                      <th class="px-4 py-3 text-center">Actual In</th>
                      <th class="px-4 py-3 text-center">Actual Out</th>
                      <th class="px-4 py-3 text-center">Late</th>
                      <th class="px-4 py-3 text-center">Undertime</th>
                      <th class="px-4 py-3 text-center">Overtime</th>
                    </tr>
                  </thead>

                  <!-- BODY -->
                  <tbody>
                    <!-- ✅ LOADER ROW -->
                    <tr v-if="isLoading">
                      <td colspan="8" class="py-10 text-center">
                        <div class="flex flex-col items-center justify-center">
                          <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
                          <p class="text-lg font-semibold text-gray-700">
                            Fetching chedules and DTR. Please wait...
                          </p>
                        </div>
                      </td>
                    </tr>
                    
                    <tr
                      v-else
                      v-for="sch in displayedSchedules"
                      :key="sch.id"
                      class="border-t transition"
                      :class="rowClass(sch)"
                    >
                      <!-- DATE -->
                      <td class="px-4 py-3 font-medium">
                        {{ formatDate(sch.schedule_date) }}
                      </td>

                      <!-- CONDITIONAL DISPLAY BASED ON STATUS -->
                      <template v-if="sch.status === 'Scheduled'">
                        <td class="px-4 py-3 text-center">{{ sch.time_in || '-' }}</td>
                        <td class="px-4 py-3 text-center">{{ sch.time_out || '-' }}</td>

                        <td class="px-4 py-3 text-center">
                          <input
                            type="time"
                            v-model="sch.actual_time_in"
                            :disabled="sch.status !== 'Scheduled'"
                            class="border rounded px-2 py-1 text-sm bg-white focus:ring-1 focus:ring-gray-400 focus:outline-none disabled:bg-gray-100"
                          />
                        </td>

                        <td class="px-4 py-3 text-center">
                          <input
                            type="time"
                            v-model="sch.actual_time_out"
                            :disabled="sch.status !== 'Scheduled'"
                            class="border rounded px-2 py-1 text-sm bg-white focus:ring-1 focus:ring-gray-400 focus:outline-none disabled:bg-gray-100"
                          />
                        </td>

                        <td class="px-4 py-3 text-center font-semibold" :class="lateClass(sch)">
                          {{ calculateLate(sch.time_in, sch.actual_time_in) }}
                        </td>

                        <td class="px-4 py-3 text-center font-semibold" :class="undertimeClass(sch)">
                          {{ calculateUndertime(sch.time_out, sch.actual_time_out) }}
                        </td>

                        <!-- <td class="px-4 py-3 text-center font-semibold">
                          <button
                            @click.stop="openOtModal(sch)"
                            class="px-3 py-1 text-xs bg-indigo-500 text-white rounded hover:bg-indigo-600"
                          >File OT</button>
                        </td> -->
                        <td class="px-4 py-3 text-center font-semibold">
                          <button
                            v-if="!sch.overtime"
                            @click.stop="openOtModal(sch)"
                            class="px-3 py-1 text-xs bg-indigo-500 text-white rounded hover:bg-indigo-600"
                          >
                            File OT
                          </button>

                          <button
                            v-else
                            @click.stop="viewOtDetails(sch.overtime)"
                            class="px-3 py-1 text-xs bg-green-500 text-white rounded hover:bg-green-600"
                          >
                            View OT ({{ sch.overtime.total_hours }} hrs)
                          </button>
                        </td>
                      </template>

                      <template v-else-if="sch.status === 'Day Off'">
                        <td colspan="7" class="px-4 py-3 text-left text-gray-500 font-medium">
                          Status: Day Off
                        </td>
                      </template>

                      <template v-else-if="sch.status === 'Absent' || sch.status === 'Leave'">
                        <td colspan="7" class="px-4 py-3 text-left text-red-600 font-medium">
                          Status: {{ sch.status }} | {{ sch.remarks ? ' Reason:  ' + sch.remarks : '' }}
                        </td>
                      </template>
                    </tr>

                    <tr v-if="displayedSchedules.length === 0">
                      <td colspan="8" class="text-center py-6 text-gray-400">
                        No schedules found
                      </td>
                    </tr>
                  </tbody>
 
                  <!-- <tbody>
                    <tr
                      v-for="sch in displayedSchedules"
                      :key="sch.id"
                      class="border-t transition"
                      :class="rowClass(sch)"
                    >
                      <td class="px-4 py-3 font-medium">
                        {{ formatDate(sch.schedule_date) }}
                      </td>

                      <td class="px-4 py-3">
                        {{ sch.shift }}
                      </td>

                      <td class="px-4 py-3 text-center">
                        {{ sch.time_in || '-' }}
                      </td>

                      <td class="px-4 py-3 text-center">
                        {{ sch.time_out || '-' }}
                      </td>

                      <td class="px-4 py-3 text-center">
                        <input
                          type="time"
                          v-model="sch.actual_time_in"
                          :disabled="sch.status !== 'Scheduled'"
                          class="border rounded px-2 py-1 text-sm bg-white focus:ring-1 focus:ring-gray-400 focus:outline-none disabled:bg-gray-100"
                        />
                      </td>

                      <td class="px-4 py-3 text-center">
                        <input
                          type="time"
                          v-model="sch.actual_time_out"
                          :disabled="sch.status !== 'Scheduled'"
                          class="border rounded px-2 py-1 text-sm bg-white focus:ring-1 focus:ring-gray-400 focus:outline-none disabled:bg-gray-100"
                        />
                      </td>

                      <td class="px-4 py-3 text-center font-semibold"
                          :class="lateClass(sch)">
                        {{ calculateLate(sch.time_in, sch.actual_time_in) }}
                      </td>

                      <td class="px-4 py-3 text-center font-semibold"
                          :class="undertimeClass(sch)">
                        {{ calculateUndertime(sch.time_out, sch.actual_time_out) }}
                      </td>
                    </tr>
                  </tbody> -->

                </table>
              </div>

              <!-- FIXED SUMMARY FOOTER -->
              <div class="bg-gray-100 border-t px-4 py-3 flex justify-end gap-10 text-sm font-semibold">
                <div>
                  Total late:
                  <span class="text-red-700 ml-2">{{ totalLate }}</span>
                </div>
                <div>
                  Total Undertime:
                  <span class="text-orange-700 ml-2">{{ totalUndertime }}</span>
                </div>
              </div>
            </div>
              <div class="flex justify-end gap-2 mt-4">
                <button @click="saveDTR" class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                  <span class="fa fa-save"></span> Save DTR
                </button>
              </div>
            </v-form>
          </div>
        </div>

        <!-- ================= OT MODAL ================= -->
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

                <!-- <div>
                  <label class="block font-medium">Date</label>
                  <input
                    type="date"
                    v-model="otForm.ot_date"
                    class="w-full border rounded px-3 py-2"
                    required
                  />
                </div> -->

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
                  :disabled="isLoadingOT"
                  type="submit"
                  class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 
                        disabled:opacity-60 disabled:cursor-not-allowed 
                        flex items-center justify-center gap-2"
                >
                  <!-- Spinner -->
                  <svg
                    v-if="isLoadingOT"
                    class="animate-spin h-4 w-4 text-white"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                  >
                    <circle
                      class="opacity-25"
                      cx="12"
                      cy="12"
                      r="10"
                      stroke="currentColor"
                      stroke-width="4"
                    />
                    <path
                      class="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                    />
                  </svg>

                  <span>
                    {{ isLoadingOT ? 'Submitting...' : 'Submit OT' }}
                  </span>
                </button>
              </div>

            </form>

            <div v-if="selectedOt">
              <p><strong>Date:</strong> {{ selectedOt.ot_date }}</p>
              <p><strong>Start:</strong> {{ selectedOt.start_time }}</p>
              <p><strong>End:</strong> {{ selectedOt.end_time }}</p>
              <p><strong>Total Hours:</strong> {{ selectedOt.total_hours }}</p>
              <p><strong>Status:</strong> {{ selectedOt.status }}</p>
            </div>

          </div>
        </div>

        <!-- ================= OT DETAILS MODAL ================= -->
        <div
          v-if="showOtDetailsModal"
          class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50"
        >
          <div class="bg-white w-full max-w-md rounded-xl shadow-2xl p-6 relative">

            <!-- Header -->
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-lg font-bold text-gray-800">
                Overtime Details
              </h2>
              <button
                @click="closeOtDetailsModal"
                class="text-gray-500 hover:text-gray-700 text-xl"
              >
                ✕
              </button>
            </div>

            <!-- Body -->
            <div v-if="selectedOt" class="space-y-3 text-sm text-gray-700">

              <div class="flex justify-between">
                <span class="font-medium">Date:</span>
                <span>{{ selectedOt.ot_date }}</span>
              </div>

              <div class="flex justify-between">
                <span class="font-medium">Start Time:</span>
                <span>{{ selectedOt.start_time }}</span>
              </div>

              <div class="flex justify-between">
                <span class="font-medium">End Time:</span>
                <span>{{ selectedOt.end_time }}</span>
              </div>

              <div class="flex justify-between">
                <span class="font-medium">Total Hours:</span>
                <span class="font-semibold text-indigo-600">
                  {{ selectedOt.total_hours }} hrs
                </span>
              </div>

              <div class="flex justify-between">
                <span class="font-medium">Type:</span>
                <span>{{ selectedOt.type }}</span>
              </div>

              <div class="flex justify-between">
                <span class="font-medium">Status:</span>
                <span
                  class="px-2 py-0.5 rounded text-xs font-semibold"
                  :class="{
                    'bg-green-100 text-green-700': selectedOt.status === 'approved',
                    'bg-yellow-100 text-yellow-700': selectedOt.status === 'pending',
                    'bg-red-100 text-red-700': selectedOt.status === 'rejected'
                  }"
                >
                  {{ selectedOt.status }}
                </span>
              </div>

              <div v-if="selectedOt.remarks">
                <span class="font-medium block mb-1">Remarks:</span>
                <p class="bg-gray-100 p-2 rounded text-xs">
                  {{ selectedOt.remarks }}
                </p>
              </div>

            </div>

            <!-- Footer -->
            <div class="mt-6 text-right">
              <button
                @click="closeOtDetailsModal"
                class="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700"
              >
                Close
              </button>
            </div>

          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useEmployeesIndex } from '@/Composables/employees/useEmployeesIndex'

const props = defineProps({
  employees: Array,
  roles: Object,
  branches: Array,
})

const {
  prefix,
  filteredEmployees,
  search,

  showModal,
  showEmploymentModal,
  show201FileModal,
  showDTRModal,
  showOtModal,
  showOtDetailsModal,

  isLoading,
  isLoadingOT,
  isEditing,

  selectedEmployee,
  selectedEmployment,
  selectedOt,

  payrollStartDate,
  payrollEndDate,

  form,
  systemAccount,
  showAccountModal,
  employmentForm,
  newEmployment,
  otForm,

  documentTypes,
  fileInputs,

  displayedSchedules,
  totalLate,
  totalUndertime,

  createEmployee,
  editEmployee,
  viewSystemAccount,
  closeEmployeeModal,
  submit,
  deleteEmployee,

  viewEmployment,
  closeEmploymentModal,
  closeAccountModal,
  editEmployment,
  cancelEditEmployment,
  updateEmployment,
  saveEmployment,

  getDocument,
  handleFileUpload,
  removeDocument,

  view201File,
  close201FileModal,

  openDTR,
  closeDTRModal,
  saveDTR,

  calculateLate,
  calculateUndertime,
  formatDate,
  rowClass,
  lateClass,
  undertimeClass,
  filterSchedules,
  printPayroll,

  openOtModal,
  closeOtModal,
  submitOt,
  viewOtDetails,
  closeOtDetailsModal,

  roles,
  branches,
  selectedBranch,
  addEmployment,
  showAddEmploymentModal
} = useEmployeesIndex(props)

  const printPosAccounts = () => {
    const url = route('hr.print-pos-accounts')
    window.open(url, '_blank')
  }

  const printEmployeePersonalDetails = () => {
    const url = route('hr.print-employee-personal-details')
    window.open(url, '_blank')
  }
</script>

