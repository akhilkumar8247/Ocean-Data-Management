<template>
  <div class="content-card">
    <!-- Page Header & Global Search / Add Actions -->
    <div class="toolbar">
      <SearchBar 
        v-model="filters.search" 
        placeholder="Search requests by name, org, remarks..."
        @search="handleSearch" 
      />
      
      <button class="btn btn-primary" @click="openAddModal">
        <PlusIcon :size="16" />
        Add Data Request
      </button>
    </div>

    <!-- Filter Panel -->
    <FilterPanel :show-reset="isFilterActive" @reset="resetFilters">
      <!-- Category Filter -->
      <div class="form-group">
        <label class="form-label">Category</label>
        <select v-model="filters.categoryCode" class="form-control" @change="fetchRequests">
          <option value="">All Categories</option>
          <option 
            v-for="cat in refData.categories" 
            :key="cat.categoryCode" 
            :value="cat.categoryCode"
          >
            {{ cat.categoryName }}
          </option>
        </select>
      </div>

      <!-- Status Filter -->
      <div class="form-group">
        <label class="form-label">Status</label>
        <select v-model="filters.statusCode" class="form-control" @change="fetchRequests">
          <option value="">All Statuses</option>
          <option 
            v-for="st in refData.statusReq" 
            :key="st.statusCode" 
            :value="st.statusCode"
          >
            {{ st.statusName }}
          </option>
        </select>
      </div>

      <!-- Date Received Start Filter -->
      <FormInput
        v-model="filters.dateStart"
        id="filter-date-start"
        label="Date Received From"
        type="date"
        @change="fetchRequests"
      />

      <!-- Date Received End Filter -->
      <FormInput
        v-model="filters.dateEnd"
        id="filter-date-end"
        label="Date Received To"
        type="date"
        @change="fetchRequests"
      />
    </FilterPanel>

    <!-- Data Table -->
    <DataTable
      :headers="tableHeaders"
      :items="requests"
      :loading="loading"
      :sort-by="filters.sortBy"
      :sort-order="filters.sortOrder"
      id-key="requestId"
      @sort="handleSort"
    >
      <!-- Contact Name: dropdown when editing -->
      <template #cell(cName)="{ item }">
        <template v-if="editingRowId === item.requestId">
          <FormSelect
            v-model="inlineEditForm.cId"
            id="req-inline-contact"
            :options="contactOptions"
            searchable
            class="inline-select-searchable"
          />
        </template>
        <template v-else>{{ item.cName }}</template>
      </template>

      <!-- Email: input when editing -->
      <template #cell(emailId)="{ item }">
        <template v-if="editingRowId === item.requestId">
          <input type="text" v-model="inlineEditForm.emailId" class="form-control inline-input" />
        </template>
        <template v-else>{{ item.emailId }}</template>
      </template>

      <!-- Org Name: dropdown when editing -->
      <template #cell(orgName)="{ item }">
        <template v-if="editingRowId === item.requestId">
          <select v-model="inlineEditForm.orgCode" class="form-control inline-select">
            <option v-for="opt in organizationOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
          <div style="font-size: 0.8rem; color: #64748b; margin-top: 0.25rem;">
            {{ inlineEditForm.countryName }}
          </div>
        </template>
        <template v-else>
          <div style="font-weight: 500;">{{ item.orgName }}</div>
          <div style="font-size: 0.8rem; color: #64748b; margin-top: 0.125rem;">{{ item.countryName }}</div>
        </template>
      </template>


      <!-- Category: dropdown when editing -->
      <template #cell(categoryName)="{ item }">
        <template v-if="editingRowId === item.requestId">
          <select v-model="inlineEditForm.categoryCode" class="form-control inline-select">
            <option v-for="opt in categoryOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </template>
        <template v-else>{{ item.categoryName }}</template>
      </template>

      <!-- Date Received: date input when editing -->
      <template #cell(dateReceived)="{ item }">
        <template v-if="editingRowId === item.requestId">
          <input type="date" v-model="inlineEditForm.dateReceived" class="form-control inline-input" />
        </template>
        <template v-else>{{ item.dateReceived }}</template>
      </template>

      <!-- Date Provided: date input when editing -->
      <template #cell(dateProvided)="{ item }">
        <template v-if="editingRowId === item.requestId">
          <input type="date" v-model="inlineEditForm.dateProvided" class="form-control inline-input" />
        </template>
        <template v-else>{{ item.dateProvided || '—' }}</template>
      </template>

      <!-- Status: dropdown with badge when not editing -->
      <template #cell(statusName)="{ item }">
        <template v-if="editingRowId === item.requestId">
          <select v-model="inlineEditForm.statusCode" class="form-control inline-select">
            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </template>
        <template v-else>
          <span class="badge" :class="getStatusBadgeClass(item.statusName)">{{ item.statusName }}</span>
        </template>
      </template>

      <!-- Size: input when editing -->
      <template #cell(size)="{ item }">
        <template v-if="editingRowId === item.requestId">
          <input type="number" step="0.01" v-model="inlineEditForm.size" class="form-control inline-input" style="width: 80px;" />
        </template>
        <template v-else>{{ item.size !== null && item.size !== undefined ? `${item.size} MB` : '—' }}</template>
      </template>

      <!-- Cost: input when editing -->
      <template #cell(cost)="{ item }">
        <template v-if="editingRowId === item.requestId">
          <input type="number" step="0.01" v-model="inlineEditForm.cost" class="form-control inline-input" style="width: 80px;" />
        </template>
        <template v-else>{{ item.cost !== null && item.cost !== undefined ? `₹${item.cost}` : '—' }}</template>
      </template>

      <!-- Programs: list or multiselect when editing -->
      <template #cell(programs)="{ item }">
        <template v-if="editingRowId === item.requestId">
          <MultiSelectPrograms
            v-model="inlineEditForm.programCodes"
            :options="programOptions"
            placeholder="Select..."
            class="inline-multiselect"
          />
        </template>
        <template v-else>
          <div class="table-program-pills">
            <span 
              v-for="prog in item.programs" 
              :key="prog.prgmCode" 
              class="badge-program"
            >
              {{ prog.prgmName }}
            </span>
            <span v-if="!item.programs || item.programs.length === 0" class="text-muted">—</span>
          </div>
        </template>
      </template>

      <!-- Remarks: text input when editing -->
      <template #cell(remarks)="{ item }">
        <template v-if="editingRowId === item.requestId">
          <input type="text" v-model="inlineEditForm.remarks" class="form-control inline-input" placeholder="Remarks..." />
        </template>
        <template v-else>{{ item.remarks }}</template>
      </template>

      <!-- Actions: Save/Cancel when editing, View/Edit/Delete otherwise -->
      <template #cell(actions)="{ item }">
        <div class="btn-group">
          <template v-if="editingRowId === item.requestId">
            <button class="btn btn-primary inline-save-btn" @click="saveInlineEdit" title="Save changes">
              <CheckIcon :size="13" /> Save
            </button>
            <button class="btn btn-secondary inline-cancel-btn" @click="cancelInlineEdit" title="Cancel">
              <XIcon :size="13" /> Cancel
            </button>
          </template>
          <template v-else>
            <button class="btn btn-secondary inline-action-btn" @click="viewDetails(item)" title="View details">
              <EyeIcon :size="13" /> View
            </button>
            <button class="btn btn-primary inline-action-btn" @click="startInlineEdit(item)" title="Update row inline">
              <EditIcon :size="13" /> Update
            </button>
          </template>
        </div>
      </template>
    </DataTable>

    <!-- Pagination -->
    <Pagination
      v-model:page="filters.page"
      :limit="filters.limit"
      :total="totalRecords"
      :total-pages="totalPages"
      @update:page="fetchRequests"
    />

    <!-- ============================================== -->
    <!-- 1. DATA REQUEST FORM MODAL (ADD / EDIT) -->
    <!-- ============================================== -->
    <Modal 
      :is-open="showFormModal" 
      :title="isEditMode ? 'Edit Data Request' : 'Add Data Request'" 
      size="lg"
      @close="showFormModal = false"
    >
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
        <!-- Contact Dropdown -->
        <FormSelect
          v-model="requestForm.cId"
          id="req-contact"
          label="Contact"
          :options="contactOptions"
          :error="formErrors.cId"
          required
          searchable
          action-text="+ Add New Contact"
          @action="showContactModal = true"
          @create-new="handleQuickContactCreate"
        />

        <!-- Contact Email -->
        <FormInput
          v-model="requestForm.emailId"
          id="req-email"
          label="Contact Email"
          type="email"
          :error="formErrors.emailId"
          required
        />

        <!-- Organization Name -->
        <FormSelect
          v-model="requestForm.orgCode"
          id="req-org-code"
          label="Organization"
          :options="organizationOptions"
          :error="formErrors.orgCode"
          required
          searchable
        />

        <!-- Country Name -->
        <FormInput
          v-model="requestForm.countryName"
          id="req-country-name"
          label="Country Name"
          disabled
        />


        <!-- Category Dropdown -->
        <FormSelect
          v-model="requestForm.categoryCode"
          id="req-category"
          label="Category"
          :options="categoryOptions"
          :error="formErrors.categoryCode"
          required
        />

        <!-- Date Received -->
        <FormInput
          v-model="requestForm.dateReceived"
          id="req-date-received"
          label="Date Received"
          type="date"
          :error="formErrors.dateReceived"
          required
        />

        <!-- Date Provided -->
        <FormInput
          v-model="requestForm.dateProvided"
          id="req-date-provided"
          label="Date Provided"
          type="date"
          :error="formErrors.dateProvided"
        />

        <!-- Size (MB) -->
        <FormInput
          v-model="requestForm.size"
          id="req-size"
          label="Size (MB)"
          type="number"
          step="0.01"
          :error="formErrors.size"
        />

        <!-- Cost (Rs.) -->
        <FormInput
          v-model="requestForm.cost"
          id="req-cost"
          label="Cost (Rs.)"
          type="number"
          step="0.01"
          :error="formErrors.cost"
        />

        <!-- Status -->
        <FormSelect
          v-model="requestForm.statusCode"
          id="req-status"
          label="Status"
          :options="statusOptions"
          :error="formErrors.statusCode"
          required
        />

        <!-- Programs (Multi-Select) -->
        <MultiSelectPrograms
          v-model="requestForm.programCodes"
          id="req-programs"
          label="Associated Programs"
          :options="programOptions"
          :error="formErrors.programCodes"
          placeholder="Select programs..."
        />
      </div>

      <!-- Details & Remarks -->
      <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
        <FormTextarea
          v-model="requestForm.details"
          id="req-details"
          label="Request Details"
          :rows="2"
          :error="formErrors.details"
          placeholder="Enter detailed technical request information..."
        />

        <FormTextarea
          v-model="requestForm.remarks"
          id="req-remarks"
          label="Remarks"
          :rows="2"
          :error="formErrors.remarks"
          required
          placeholder="Enter processing notes, tracking logs..."
        />
      </div>

      <template #footer>
        <button class="btn btn-secondary" @click="showFormModal = false">
          Cancel
        </button>
        <button class="btn btn-primary" @click="saveRequest">
          {{ isEditMode ? 'Update Request' : 'Create Request' }}
        </button>
      </template>
    </Modal>

    <!-- ============================================== -->
    <!-- 2. ADD CONTACT MODAL (CASCADING - LEVEL 1) -->
    <!-- ============================================== -->
    <Modal 
      :is-open="showContactModal" 
      title="Add New Contact" 
      size="md"
      @close="showContactModal = false"
    >
      <div style="display: flex; flex-direction: column; gap: 1rem;">
        <FormInput
          v-model="contactForm.cName"
          id="c-name"
          label="Contact Name"
          :error="contactErrors.cName"
          required
          placeholder="Enter name (e.g., John Smith)"
        />

        <FormInput
          v-model="contactForm.emailId"
          id="c-email"
          label="Email Address"
          type="email"
          :error="contactErrors.emailId"
          required
          placeholder="e.g., john.smith@domain.com (.com, .org, .net, .in)"
        />

        <FormSelect
          v-model="contactForm.orgCode"
          id="c-org"
          label="Organization"
          :options="organizationOptions"
          :error="contactErrors.orgCode"
          required
          searchable
          action-text="+ Add New Organization"
          @action="showOrgModal = true"
        />
      </div>

      <template #footer>
        <button class="btn btn-secondary" @click="showContactModal = false">
          Cancel
        </button>
        <button class="btn btn-primary" @click="saveContact">
          Save Contact
        </button>
      </template>
    </Modal>

    <!-- ============================================== -->
    <!-- 3. ADD ORGANIZATION MODAL (CASCADING - LEVEL 2) -->
    <!-- ============================================== -->
    <Modal 
      :is-open="showOrgModal" 
      title="Add New Organization" 
      size="md"
      @close="showOrgModal = false"
    >
      <div style="display: flex; flex-direction: column; gap: 1rem;">
        <FormInput
          v-model="orgForm.orgCode"
          id="o-code"
          label="Organization Code"
          type="text"
          :error="orgErrors.orgCode"
          required
          placeholder="Enter unique alphanumeric code (e.g., ORG001)"
        />

        <FormInput
          v-model="orgForm.orgName"
          id="o-name"
          label="Organization Name"
          :error="orgErrors.orgName"
          required
          placeholder="Enter organization name (letters/spaces only)"
        />

        <FormInput
          v-model="orgForm.address"
          id="o-address"
          label="Address"
          :error="orgErrors.address"
          required
          placeholder="Enter street, city details..."
        />

        <FormSelect
          v-model="orgForm.countryCode"
          id="o-country"
          label="Country"
          :options="countryOptions"
          :error="orgErrors.countryCode"
          required
        />
      </div>

      <template #footer>
        <button class="btn btn-secondary" @click="showOrgModal = false">
          Cancel
        </button>
        <button class="btn btn-primary" @click="saveOrganization">
          Save Organization
        </button>
      </template>
    </Modal>



    <!-- ============================================== -->
    <!-- 5. DELETE CONFIRMATION DIALOG -->
    <!-- ============================================== -->
    <ConfirmDialog
      :is-open="showDeleteConfirm"
      title="Delete Request"
      :message="`Are you sure you want to delete data request ID #${selectedRequest?.requestId} for ${selectedRequest?.cName}? This action cannot be undone.`"
      confirm-text="Delete"
      cancel-text="Keep Request"
      @confirm="deleteRequest"
      @cancel="showDeleteConfirm = false"
    />

    <!-- ============================================== -->
    <!-- 6. VIEW DETAILS MODAL -->
    <!-- ============================================== -->
    <Modal 
      :is-open="showViewModal" 
      title="Data Request Details" 
      size="md"
      @close="showViewModal = false"
    >
      <div v-if="selectedRequest" class="details-view">
        <div class="detail-row">
          <span class="detail-label">Request ID:</span>
          <span class="detail-val">#{{ selectedRequest.requestId }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Contact Name:</span>
          <span class="detail-val">{{ selectedRequest.cName }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Email Address:</span>
          <span class="detail-val">{{ selectedRequest.emailId }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Organization:</span>
          <span class="detail-val">{{ selectedRequest.orgName }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Country:</span>
          <span class="detail-val">{{ selectedRequest.countryName }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Category:</span>
          <span class="detail-val">{{ selectedRequest.categoryName }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Date Received:</span>
          <span class="detail-val">{{ selectedRequest.dateReceived }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Date Provided:</span>
          <span class="detail-val">{{ selectedRequest.dateProvided || 'Not Provided Yet' }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Status:</span>
          <span class="badge" :class="getStatusBadgeClass(selectedRequest.statusName)">
            {{ selectedRequest.statusName }}
          </span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Size (MB):</span>
          <span class="detail-val">{{ selectedRequest.size !== null && selectedRequest.size !== undefined ? `${selectedRequest.size} MB` : '—' }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Cost (Rs.):</span>
          <span class="detail-val">{{ selectedRequest.cost !== null && selectedRequest.cost !== undefined ? `₹${selectedRequest.cost}` : '—' }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Associated Programs:</span>
          <div class="detail-val program-pills">
            <span 
              v-for="prog in selectedRequest.programs" 
              :key="prog.prgmCode" 
              class="badge-program"
            >
              {{ prog.prgmName }}
            </span>
            <span v-if="!selectedRequest.programs || selectedRequest.programs.length === 0" class="text-muted">
              None Linked
            </span>
          </div>
        </div>
        <div class="detail-section">
          <span class="detail-label">Request Details:</span>
          <p class="detail-block">{{ selectedRequest.details || 'No details provided.' }}</p>
        </div>
        <div class="detail-section">
          <span class="detail-label">Remarks:</span>
          <p class="detail-block">{{ selectedRequest.remarks }}</p>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-primary" @click="showViewModal = false">
          Close
        </button>
      </template>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { 
  Plus as PlusIcon, 
  Eye as EyeIcon, 
  Edit as EditIcon, 
  Check as CheckIcon,
  X as XIcon
} from '@lucide/vue';

// Services & Types
import requestService from '../services/requestService';
import type { GetRequestsParams } from '../services/requestService';
import referenceService from '../services/referenceService';
import type { DataRequest, ReferenceData } from '../types';

// Reusable components
import SearchBar from '../components/SearchBar.vue';
import FilterPanel from '../components/FilterPanel.vue';
import DataTable from '../components/DataTable.vue';
import Pagination from '../components/Pagination.vue';
import Modal from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import FormInput from '../components/FormInput.vue';
import FormSelect from '../components/FormSelect.vue';
import FormTextarea from '../components/FormTextarea.vue';
import MultiSelectPrograms from '../components/MultiSelectPrograms.vue';

const emit = defineEmits<{
  (e: 'show-toast', type: 'success' | 'error', text: string): void;
}>();

// List States
const requests = ref<DataRequest[]>([]);
const loading = ref(false);
const totalRecords = ref(0);
const totalPages = ref(1);

const filters = reactive<Required<GetRequestsParams>>({
  search: '',
  categoryCode: '',
  statusCode: '',
  dateStart: '',
  dateEnd: '',
  sortBy: 'requestId',
  sortOrder: 'desc',
  page: 1,
  limit: 20
});

// Reference data for dropdowns
const refData = ref<ReferenceData>({
  countries: [],
  organizations: [],
  contacts: [],
  categories: [],
  programs: [],
  statusReq: [],
  statusRes: []
});

// Modal Toggles
const showFormModal = ref(false);
const showContactModal = ref(false);
const showOrgModal = ref(false);
const showDeleteConfirm = ref(false);
const showViewModal = ref(false);

const isEditMode = ref(false);
const selectedRequest = ref<DataRequest | null>(null);

// Inline editing state
const editingRowId = ref<number | null>(null);
const inlineEditForm = reactive({
  cId: 0 as number,
  emailId: '',
  orgCode: '',
  orgName: '',
  countryName: '',
  dateReceived: '',
  dateProvided: null as string | null,
  size: null as number | null,
  cost: null as number | null,
  categoryCode: '' as string,
  statusCode: '' as string,
  remarks: '',
  programCodes: [] as string[]
});

interface WritableRequest extends Omit<DataRequest, 'requestId'> {
  emailId: string;
  orgCode: string;
  orgName: string;
  countryName: string;
  size: number | null;
  cost: number | null;
}

// Forms States
const requestForm = ref<WritableRequest>({
  cId: '' as any,
  emailId: '',
  orgCode: '',
  orgName: '',
  countryName: '',
  dateReceived: '',
  dateProvided: null,
  size: null,
  cost: null,
  categoryCode: '' as any,
  details: '',
  statusCode: 'P' as any,
  remarks: '',
  programCodes: [],
  programs: []
});


const contactForm = reactive({
  cName: '',
  emailId: '',
  orgCode: '' as any
});

const orgForm = reactive({
  orgCode: '' as any,
  orgName: '',
  address: '',
  countryCode: '' as any
});

// Validation Errors
const formErrors = reactive<Record<string, string>>({});
const contactErrors = reactive<Record<string, string>>({});
const orgErrors = reactive<Record<string, string>>({});

// Headers config for DataTable
const tableHeaders = [
  { key: 'requestId', label: 'Req ID', sortable: true, width: '90px' },
  { key: 'cName', label: 'Contact Name', sortable: true },
  { key: 'emailId', label: 'Email', sortable: true },
  { key: 'orgName', label: 'Organization', sortable: true },
  { key: 'categoryName', label: 'Category', sortable: true },
  { key: 'dateReceived', label: 'Date Received', sortable: true, width: '130px' },
  { key: 'dateProvided', label: 'Date Provided', sortable: true, width: '130px' },
  { key: 'statusName', label: 'Status', sortable: true, width: '110px' },
  { key: 'size', label: 'Size (MB)', sortable: true, width: '100px' },
  { key: 'cost', label: 'Cost (Rs.)', sortable: true, width: '100px' },
  { key: 'programs', label: 'Associated Programs', sortable: false, width: '180px' },
  { key: 'remarks', label: 'Remarks', sortable: true },
  { key: 'actions', label: 'Actions', sortable: false, width: '150px' }
];

// Computed dropdown arrays converted for FormSelect
const contactOptions = computed(() => 
  refData.value.contacts.map(c => ({
    value: c.cId,
    label: `${c.cName} (${c.emailId}) - ${c.orgName}`
  }))
);

const categoryOptions = computed(() => 
  refData.value.categories.map(c => ({
    value: c.categoryCode,
    label: c.categoryName
  }))
);

const statusOptions = computed(() => 
  refData.value.statusReq.map(s => ({
    value: s.statusCode,
    label: s.statusName
  }))
);

const programOptions = computed(() => 
  refData.value.programs.map(p => ({
    value: p.prgmCode,
    label: p.prgmName
  }))
);

const countryOptions = computed(() => 
  refData.value.countries.map(c => ({
    value: c.countryCode,
    label: c.countryName
  }))
);



const organizationOptions = computed(() => 
  refData.value.organizations.map(o => ({
    value: o.orgCode,
    label: `${o.orgName} (${o.countryName})`
  }))
);

// Check if any filters are active
const isFilterActive = computed(() => {
  return filters.categoryCode !== '' || 
         filters.statusCode !== '' || 
         filters.dateStart !== '' || 
         filters.dateEnd !== '';
});

// Lifecycle
onMounted(() => {
  fetchReferenceData();
  fetchRequests();
});

// Operations
const fetchReferenceData = async () => {
  try {
    const res = await referenceService.getAllReferences();
    refData.value = res.data;
  } catch (error: any) {
    emit('show-toast', 'error', 'Failed to load reference dropdown lists: ' + (error.response?.data?.error || error.message));
  }
};

const fetchRequests = async () => {
  loading.value = true;
  try {
    const res = await requestService.getRequests(filters);
    requests.value = res.data.data;
    totalRecords.value = res.data.total;
    totalPages.value = res.data.totalPages;
  } catch (error: any) {
    emit('show-toast', 'error', 'Failed to load data requests: ' + (error.response?.data?.error || error.message));
  } finally {
    loading.value = false;
  }
};

const handleSort = (key: string) => {
  if (filters.sortBy === key) {
    filters.sortOrder = filters.sortOrder === 'asc' ? 'desc' : 'asc';
  } else {
    filters.sortBy = key;
    filters.sortOrder = 'asc';
  }
  fetchRequests();
};

const handleSearch = () => {
  filters.page = 1;
  fetchRequests();
};

const resetFilters = () => {
  filters.categoryCode = '';
  filters.statusCode = '';
  filters.dateStart = '';
  filters.dateEnd = '';
  filters.page = 1;
  fetchRequests();
};

const getStatusBadgeClass = (status: string | undefined) => {
  if (!status) return '';
  const s = status.toLowerCase();
  if (s === 'pending') return 'badge-pending';
  if (s === 'in progress') return 'badge-in-progress';
  if (s === 'approved') return 'badge-approved';
  if (s === 'rejected') return 'badge-rejected';
  return '';
};

// Watchers to auto-fill contact details when selected contact changes
watch(() => inlineEditForm.cId, (newCId) => {
  const contact = refData.value.contacts.find(c => c.cId === newCId);
  if (contact) {
    inlineEditForm.emailId = contact.emailId;
    inlineEditForm.orgCode = contact.orgCode || '';
    inlineEditForm.orgName = contact.orgName || '';
    inlineEditForm.countryName = contact.countryName || '';
  }
});

watch(() => requestForm.value.cId, (newCId) => {
  const contact = refData.value.contacts.find(c => c.cId === newCId);
  if (contact) {
    requestForm.value.emailId = contact.emailId;
    requestForm.value.orgCode = contact.orgCode || '';
    requestForm.value.orgName = contact.orgName || '';
    requestForm.value.countryName = contact.countryName || '';
  } else {
    requestForm.value.emailId = '';
    requestForm.value.orgCode = '';
    requestForm.value.orgName = '';
    requestForm.value.countryName = '';
  }
});

watch(() => inlineEditForm.orgCode, (newOrgCode) => {
  const org = refData.value.organizations.find(o => o.orgCode === newOrgCode);
  if (org) {
    inlineEditForm.orgName = org.orgName;
    inlineEditForm.countryName = org.countryName || '';
  }
});

watch(() => requestForm.value.orgCode, (newOrgCode) => {
  const org = refData.value.organizations.find(o => o.orgCode === newOrgCode);
  if (org) {
    requestForm.value.orgName = org.orgName;
    requestForm.value.countryName = org.countryName || '';
  }
});

const saveContactUpdates = async (cId: number, email: string, orgCode: string) => {
  await referenceService.updateContact({
    cId: cId,
    emailId: email,
    orgCode: orgCode
  });
  await fetchReferenceData(); // Refresh list to get updated contact attributes
};

// CRUD Triggers
const handleQuickContactCreate = (query: string) => {
  contactForm.cName = query;
  showContactModal.value = true;
};

const openAddModal = () => {
  isEditMode.value = false;
  selectedRequest.value = null;
  requestForm.value = {
    cId: '' as any,
    emailId: '',
    orgCode: '',
    orgName: '',
    countryName: '',
    dateReceived: new Date().toISOString().split('T')[0], // prefill with today
    dateProvided: null,
    size: null,
    cost: null,
    categoryCode: '' as any,
    details: '',
    statusCode: 'P' as string,
    remarks: '',
    programCodes: [],
    programs: []
  };
  Object.keys(formErrors).forEach(key => delete formErrors[key]);
  showFormModal.value = true;
};

// Inline editing functions
const startInlineEdit = (item: DataRequest) => {
  editingRowId.value = item.requestId ?? null;
  inlineEditForm.cId = item.cId;
  inlineEditForm.emailId = item.emailId || '';
  const contact = refData.value.contacts.find(c => c.cId === item.cId);
  inlineEditForm.orgCode = contact ? contact.orgCode : '';
  inlineEditForm.orgName = item.orgName || '';
  inlineEditForm.countryName = item.countryName || '';
  inlineEditForm.dateReceived = item.dateReceived;
  inlineEditForm.dateProvided = item.dateProvided;
  inlineEditForm.size = item.size !== undefined ? item.size : null;
  inlineEditForm.cost = item.cost !== undefined ? item.cost : null;
  inlineEditForm.categoryCode = item.categoryCode;
  inlineEditForm.statusCode = item.statusCode;
  inlineEditForm.remarks = item.remarks;
  inlineEditForm.programCodes = item.programCodes || [];
};

const cancelInlineEdit = () => {
  editingRowId.value = null;
};

const saveInlineEdit = async () => {
  if (!inlineEditForm.cId) { emit('show-toast', 'error', 'Contact is required.'); return; }
  if (!inlineEditForm.dateReceived) { emit('show-toast', 'error', 'Date Received is required.'); return; }
  if (!inlineEditForm.categoryCode) { emit('show-toast', 'error', 'Category is required.'); return; }
  if (!inlineEditForm.statusCode) { emit('show-toast', 'error', 'Status is required.'); return; }
  if (!inlineEditForm.remarks.trim()) { emit('show-toast', 'error', 'Remarks are required.'); return; }
  if (!inlineEditForm.emailId.trim()) { emit('show-toast', 'error', 'Email is required.'); return; }
  if (!/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.(com|in|org|net)$/.test(inlineEditForm.emailId)) {
    emit('show-toast', 'error', 'Email must be valid and end with .com, .in, .org, or .net.');
    return;
  }
  if (!inlineEditForm.orgCode) { emit('show-toast', 'error', 'Organization is required.'); return; }

  try {
    // Save contact updates first
    await saveContactUpdates(
      inlineEditForm.cId,
      inlineEditForm.emailId,
      inlineEditForm.orgCode
    );

    await requestService.updateRequest({
      requestId: editingRowId.value!,
      cId: inlineEditForm.cId,
      dateReceived: inlineEditForm.dateReceived,
      dateProvided: inlineEditForm.dateProvided,
      size: inlineEditForm.size,
      cost: inlineEditForm.cost,
      categoryCode: inlineEditForm.categoryCode,
      details: null,
      statusCode: inlineEditForm.statusCode,
      remarks: inlineEditForm.remarks,
      programs: inlineEditForm.programCodes
    });
    emit('show-toast', 'success', `Request #${editingRowId.value} updated successfully.`);
    editingRowId.value = null;
    fetchRequests();
  } catch (error: any) {
    emit('show-toast', 'error', 'Failed to save: ' + (error.response?.data?.error || error.message));
  }
};

const viewDetails = (item: DataRequest) => {
  selectedRequest.value = item;
  showViewModal.value = true;
};

// Validations
const validateRequest = (): boolean => {
  Object.keys(formErrors).forEach(key => delete formErrors[key]);
  
  if (!requestForm.value.cId) formErrors.cId = 'Contact is required.';
  if (!requestForm.value.categoryCode) formErrors.categoryCode = 'Category is required.';
  if (!requestForm.value.dateReceived) formErrors.dateReceived = 'Date Received is required.';
  if (!requestForm.value.statusCode) formErrors.statusCode = 'Status is required.';
  if (!requestForm.value.remarks) formErrors.remarks = 'Remarks are required.';
  
  if (requestForm.value.dateReceived && requestForm.value.dateProvided) {
    const received = new Date(requestForm.value.dateReceived);
    const provided = new Date(requestForm.value.dateProvided);
    if (provided < received) {
      formErrors.dateProvided = 'Date Provided cannot be earlier than Date Received.';
    }
  }

  return Object.keys(formErrors).length === 0;
};

// CRUD Save/Delete Actions
const saveRequest = async () => {
  if (!validateRequest()) return;
  
  try {
    // Validate contact edits if cId is present
    if (requestForm.value.cId) {
      const email = requestForm.value.emailId || '';
      const orgCode = requestForm.value.orgCode || '';

      if (!email.trim()) { emit('show-toast', 'error', 'Email is required.'); return; }
      if (!/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.(com|in|org|net)$/.test(email)) {
        emit('show-toast', 'error', 'Email must be valid and end with .com, .in, .org, or .net.');
        return;
      }
      if (!orgCode) { emit('show-toast', 'error', 'Organization is required.'); return; }

      await saveContactUpdates(
        requestForm.value.cId,
        email,
        orgCode
      );
    }

    const payload = {
      ...requestForm.value,
      programs: requestForm.value.programCodes
    };

    if (isEditMode.value && selectedRequest.value) {
      await requestService.updateRequest({
        ...payload,
        requestId: selectedRequest.value.requestId
      });
      emit('show-toast', 'success', `Data request #${selectedRequest.value.requestId} updated successfully.`);
    } else {
      await requestService.createRequest(payload);
      emit('show-toast', 'success', 'New data request created successfully.');
    }
    showFormModal.value = false;
    fetchRequests();
  } catch (error: any) {
    emit('show-toast', 'error', 'Failed to save request: ' + (error.response?.data?.error || error.message));
  }
};


const deleteRequest = async () => {
  if (!selectedRequest.value?.requestId) return;
  
  try {
    await requestService.deleteRequest(selectedRequest.value.requestId);
    emit('show-toast', 'success', `Data request #${selectedRequest.value.requestId} deleted successfully.`);
    showDeleteConfirm.value = false;
    fetchRequests();
  } catch (error: any) {
    emit('show-toast', 'error', 'Failed to delete request: ' + (error.response?.data?.error || error.message));
  }
};

// Cascading Level 1: Contact
const validateContact = (): boolean => {
  Object.keys(contactErrors).forEach(key => delete contactErrors[key]);
  
  if (!contactForm.cName) {
    contactErrors.cName = 'Contact Name is required.';
  } else if (!/^[A-Za-z. ]+$/.test(contactForm.cName)) {
    contactErrors.cName = 'Name can only contain letters, spaces, and dots.';
  }
  
  if (!contactForm.emailId) {
    contactErrors.emailId = 'Email is required.';
  } else if (!/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.(com|in|org|net)$/.test(contactForm.emailId)) {
    contactErrors.emailId = 'Email must be valid and end with .com, .in, .org, or .net.';
  }
  
  if (!contactForm.orgCode) contactErrors.orgCode = 'Organization is required.';
  
  return Object.keys(contactErrors).length === 0;
};

const saveContact = async () => {
  if (!validateContact()) return;
  try {
    const res = await referenceService.createContact(contactForm);
    emit('show-toast', 'success', `Contact "${res.data.cName}" added successfully.`);
    await fetchReferenceData(); // refresh dropdown values
    
    // Auto-select the newly created contact
    requestForm.value.cId = res.data.cId;
    
    // Clear form & close
    contactForm.cName = '';
    contactForm.emailId = '';
    contactForm.orgCode = '' as any;
    showContactModal.value = false;
  } catch (error: any) {
    emit('show-toast', 'error', 'Failed to add contact: ' + (error.response?.data?.error || error.message));
  }
};

// Cascading Level 2: Organization
const validateOrg = (): boolean => {
  Object.keys(orgErrors).forEach(key => delete orgErrors[key]);
  
  if (!orgForm.orgCode) {
    orgErrors.orgCode = 'Organization Code is required.';
  } else if (orgForm.orgCode <= 0) {
    orgErrors.orgCode = 'Code must be a positive number.';
  }
  
  if (!orgForm.orgName) {
    orgErrors.orgName = 'Organization Name is required.';
  } else if (!/^[A-Za-z0-9 _-]+$/.test(orgForm.orgName)) {
    orgErrors.orgName = 'Name can only contain alphanumeric characters, spaces, underscores, and hyphens.';
  }
  
  if (!orgForm.address) orgErrors.address = 'Address is required.';
  if (!orgForm.countryCode) orgErrors.countryCode = 'Country is required.';
  
  return Object.keys(orgErrors).length === 0;
};

const saveOrganization = async () => {
  if (!validateOrg()) return;
  try {
    const res = await referenceService.createOrganization(orgForm);
    emit('show-toast', 'success', `Organization "${res.data.orgName}" added successfully.`);
    await fetchReferenceData();
    
    // Auto-select organization in Contact Form
    contactForm.orgCode = res.data.orgCode;
    
    // Clear and close
    orgForm.orgCode = '' as any;
    orgForm.orgName = '';
    orgForm.address = '';
    orgForm.countryCode = '' as any;
    showOrgModal.value = false;
  } catch (error: any) {
    emit('show-toast', 'error', 'Failed to add organization: ' + (error.response?.data?.error || error.message));
  }
};


</script>

<style scoped>
/* Scoped styles for detail viewing modal */
.details-view {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  font-size: 0.9rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 0.5rem;
  align-items: center;
}

.detail-label {
  font-weight: 600;
  color: var(--text-secondary);
}

.detail-val {
  color: var(--text-primary);
  text-align: right;
}

.program-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  justify-content: flex-end;
}

.badge-program {
  background-color: #f1f5f9;
  border: 1px solid var(--border-color);
  color: var(--text-primary);
  padding: 0.125rem 0.5rem;
  border-radius: var(--radius-sm);
  font-size: 0.75rem;
  font-weight: 500;
}

.detail-section {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  margin-top: 0.5rem;
}

.detail-block {
  background-color: #f8fafc;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: 0.75rem;
  font-size: 0.85rem;
  line-height: 1.4;
  white-space: pre-wrap;
  color: var(--text-primary);
}

.table-program-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  justify-content: flex-start;
}

.inline-multiselect {
  margin: 0;
  min-width: 140px;
  max-width: 200px;
}

:deep(.inline-multiselect .multiselect-trigger) {
  min-height: 30px;
  padding: 0.15rem 0.5rem;
  font-size: 0.8rem;
}

:deep(.inline-multiselect .multiselect-badge) {
  padding: 0.1rem 0.3rem;
  font-size: 0.7rem;
}

:deep(.inline-multiselect .dropdown-checklist) {
  max-height: 150px;
}

.inline-select-searchable {
  margin: 0;
  min-width: 140px;
  max-width: 220px;
}

:deep(.inline-select-searchable .select-trigger) {
  height: 30px;
  padding: 0.15rem 0.5rem;
  font-size: 0.8rem;
  border: 1px solid var(--color-primary);
  border-radius: var(--radius-sm);
  background-color: #fff;
}

:deep(.inline-select-searchable .selected-text) {
  font-size: 0.8rem;
}

:deep(.inline-select-searchable .dropdown-panel) {
  max-height: 180px;
  min-width: 240px;
}
</style>
