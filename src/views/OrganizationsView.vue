<template>
  <div class="content-card">
    <!-- Page Header & Actions -->
    <div class="toolbar">
      <SearchBar 
        v-model="filters.search" 
        placeholder="Search organizations by code, name, address, country..."
        @search="handleSearch" 
      />
      
      <button class="btn btn-primary" @click="openAddModal">
        <PlusIcon :size="16" />
        Add Organization
      </button>
    </div>

    <!-- Data Table -->
    <DataTable
      :headers="tableHeaders"
      :items="organizations"
      :loading="loading"
      :sort-by="filters.sortBy"
      :sort-order="filters.sortOrder"
      id-key="orgCode"
      @sort="handleSort"
    >
      <!-- Org Code: input when editing -->
      <template #cell(orgCode)="{ item }">
        <template v-if="editingRowId === item.orgCode">
          <input type="text" v-model="inlineEditForm.orgCode" class="form-control inline-input" style="width: 100px;" />
        </template>
        <template v-else>
          <span style="font-family: monospace; font-weight: 600;">{{ item.orgCode }}</span>
        </template>
      </template>

      <!-- Org Name: input when editing -->
      <template #cell(orgName)="{ item }">
        <template v-if="editingRowId === item.orgCode">
          <input type="text" v-model="inlineEditForm.orgName" class="form-control inline-input" />
        </template>
        <template v-else>
          <div style="font-weight: 500;">{{ item.orgName }}</div>
        </template>
      </template>

      <!-- Address: input when editing -->
      <template #cell(address)="{ item }">
        <template v-if="editingRowId === item.orgCode">
          <input type="text" v-model="inlineEditForm.address" class="form-control inline-input" />
        </template>
        <template v-else>{{ item.address }}</template>
      </template>

      <!-- Country Name: dropdown when editing -->
      <template #cell(countryName)="{ item }">
        <template v-if="editingRowId === item.orgCode">
          <select v-model="inlineEditForm.countryCode" class="form-control inline-select">
            <option v-for="opt in countryOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </template>
        <template v-else>{{ item.countryName }}</template>
      </template>

      <!-- Actions: Save/Cancel when editing, View/Edit/Delete otherwise -->
      <template #cell(actions)="{ item }">
        <div class="btn-group">
          <template v-if="editingRowId === item.orgCode">
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
            <button class="btn btn-danger inline-action-btn" @click="confirmDelete(item)" title="Delete Organization">
              <TrashIcon :size="13" /> Delete
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
      @update:page="fetchOrganizations"
    />

    <!-- ============================================== -->
    <!-- 1. ADD / EDIT ORGANIZATION MODAL -->
    <!-- ============================================== -->
    <Modal 
      :is-open="showFormModal" 
      :title="isEditMode ? 'Edit Organization' : 'Add Organization'" 
      size="md"
      @close="showFormModal = false"
    >
      <div style="display: flex; flex-direction: column; gap: 1rem;">
        <!-- Organization Code -->
        <FormInput
          v-model="orgForm.orgCode"
          id="org-code"
          label="Organization Code"
          type="text"
          :error="formErrors.orgCode"
          required
          placeholder="Unique alphanumeric code (e.g. ORG001)"
        />

        <!-- Organization Name -->
        <FormInput
          v-model="orgForm.orgName"
          id="org-name"
          label="Organization Name"
          type="text"
          :error="formErrors.orgName"
          required
          placeholder="Letters, numbers, spaces, _ or - only"
        />

        <!-- Address -->
        <FormInput
          v-model="orgForm.address"
          id="org-address"
          label="Address"
          type="text"
          :error="formErrors.address"
          required
          placeholder="Street, City, Postal Code"
        />

        <!-- Country -->
        <FormSelect
          v-model="orgForm.countryCode"
          id="org-country"
          label="Country"
          :options="countryOptions"
          :error="formErrors.countryCode"
          required
        />
      </div>

      <template #footer>
        <button class="btn btn-secondary" @click="showFormModal = false">
          Cancel
        </button>
        <button class="btn btn-primary" @click="saveOrganization">
          {{ isEditMode ? 'Update Organization' : 'Create Organization' }}
        </button>
      </template>
    </Modal>

    <!-- ============================================== -->
    <!-- 2. VIEW DETAILS MODAL -->
    <!-- ============================================== -->
    <Modal 
      :is-open="showViewModal" 
      title="Organization Details" 
      size="md"
      @close="showViewModal = false"
    >
      <div v-if="selectedOrg" class="details-view">
        <div class="detail-row">
          <span class="detail-label">Organization Code:</span>
          <span class="detail-val" style="font-family: monospace;">{{ selectedOrg.orgCode }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Organization Name:</span>
          <span class="detail-val">{{ selectedOrg.orgName }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Address:</span>
          <span class="detail-val">{{ selectedOrg.address }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Country:</span>
          <span class="detail-val">{{ selectedOrg.countryName }}</span>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-primary" @click="showViewModal = false">
          Close
        </button>
      </template>
    </Modal>

    <!-- ============================================== -->
    <!-- 3. DELETE CONFIRMATION DIALOG -->
    <!-- ============================================== -->
    <ConfirmDialog
      :is-open="showDeleteConfirm"
      title="Delete Organization"
      :message="`Are you sure you want to delete organization '${selectedOrg?.orgName}' (${selectedOrg?.orgCode})? This action cannot be undone.`"
      confirm-text="Delete"
      cancel-text="Cancel"
      @confirm="deleteOrg"
      @cancel="showDeleteConfirm = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue';
import { 
  Plus as PlusIcon, 
  Eye as EyeIcon, 
  Edit as EditIcon, 
  Check as CheckIcon,
  X as XIcon,
  Trash as TrashIcon
} from '@lucide/vue';

// Services & Types
import organizationService from '../services/organizationService';
import referenceService from '../services/referenceService';
import type { Organization, ReferenceData } from '../types';

// Reusable components
import SearchBar from '../components/SearchBar.vue';
import DataTable from '../components/DataTable.vue';
import Pagination from '../components/Pagination.vue';
import Modal from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import FormInput from '../components/FormInput.vue';
import FormSelect from '../components/FormSelect.vue';

const emit = defineEmits<{
  (e: 'show-toast', type: 'success' | 'error', text: string): void;
}>();

// List States
const organizations = ref<Organization[]>([]);
const loading = ref(false);
const totalRecords = ref(0);
const totalPages = ref(1);

const filters = reactive({
  search: '',
  sortBy: 'orgName',
  sortOrder: 'asc' as 'asc' | 'desc',
  page: 1,
  limit: 20
});

// Reference Data
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
const showDeleteConfirm = ref(false);
const showViewModal = ref(false);
const isEditMode = ref(false);
const selectedOrg = ref<Organization | null>(null);

// Inline editing state
const editingRowId = ref<string | null>(null);
const inlineEditForm = reactive({
  oldOrgCode: '',
  orgCode: '',
  orgName: '',
  address: '',
  countryCode: 0,
  countryName: ''
});

// Form States
const orgForm = ref({
  orgCode: '',
  orgName: '',
  address: '',
  countryCode: '' as any
});

// Validation Errors
const formErrors = reactive<Record<string, string>>({});

// Headers config
const tableHeaders = [
  { key: 'orgCode', label: 'Org Code', sortable: true, width: '150px' },
  { key: 'orgName', label: 'Organization Name', sortable: true },
  { key: 'address', label: 'Address', sortable: true },
  { key: 'countryName', label: 'Country', sortable: true, width: '180px' },
  { key: 'actions', label: 'Actions', sortable: false, width: '220px' }
];

// Computed Options
const countryOptions = computed(() => 
  refData.value.countries.map(c => ({
    value: c.countryCode,
    label: c.countryName
  }))
);

// Fetch Operations
const fetchOrganizations = async () => {
  loading.value = true;
  try {
    const res = await organizationService.getOrganizations({
      search: filters.search,
      sortBy: filters.sortBy,
      sortOrder: filters.sortOrder,
      page: filters.page,
      limit: filters.limit
    });
    organizations.value = res.data.data;
    totalRecords.value = res.data.total;
    totalPages.value = res.data.totalPages;
  } catch (error: any) {
    emit('show-toast', 'error', 'Failed to fetch organizations: ' + (error.response?.data?.error || error.message));
  } finally {
    loading.value = false;
  }
};

const fetchReferenceData = async () => {
  try {
    const res = await referenceService.getAllReferences();
    refData.value = res.data;
  } catch (error) {
    console.error('Failed to load countries:', error);
  }
};

onMounted(() => {
  fetchOrganizations();
  fetchReferenceData();
});

// Search & Sort Handlers
const handleSearch = () => {
  filters.page = 1;
  fetchOrganizations();
};

const handleSort = (key: string) => {
  if (filters.sortBy === key) {
    filters.sortOrder = filters.sortOrder === 'asc' ? 'desc' : 'asc';
  } else {
    filters.sortBy = key;
    filters.sortOrder = 'asc';
  }
  fetchOrganizations();
};

// Add / Edit Handlers
const openAddModal = () => {
  isEditMode.value = false;
  orgForm.value = {
    orgCode: '',
    orgName: '',
    address: '',
    countryCode: ''
  };
  Object.keys(formErrors).forEach(key => delete formErrors[key]);
  showFormModal.value = true;
};



const startInlineEdit = (item: Organization) => {
  cancelInlineEdit();
  editingRowId.value = item.orgCode;
  inlineEditForm.oldOrgCode = item.orgCode;
  inlineEditForm.orgCode = item.orgCode;
  inlineEditForm.orgName = item.orgName;
  inlineEditForm.address = item.address;
  inlineEditForm.countryCode = item.countryCode;
  inlineEditForm.countryName = item.countryName || '';
};

const cancelInlineEdit = () => {
  editingRowId.value = null;
};

const validateForm = (data: { orgCode: string; orgName: string; address: string; countryCode: any }): boolean => {
  Object.keys(formErrors).forEach(key => delete formErrors[key]);
  let isValid = true;

  if (!data.orgCode.trim()) {
    formErrors.orgCode = 'Organization Code is required.';
    isValid = false;
  } else if (!/^[A-Za-z0-9_-]+$/.test(data.orgCode.trim())) {
    formErrors.orgCode = 'Code must be alphanumeric (underscores/hyphens allowed).';
    isValid = false;
  }

  if (!data.orgName.trim()) {
    formErrors.orgName = 'Organization Name is required.';
    isValid = false;
  } else if (!/^[A-Za-z0-9 _-]+$/.test(data.orgName.trim())) {
    formErrors.orgName = 'Name must be alphanumeric (underscores/hyphens/spaces allowed, no dots).';
    isValid = false;
  }

  if (!data.address.trim()) {
    formErrors.address = 'Address is required.';
    isValid = false;
  }

  if (!data.countryCode) {
    formErrors.countryCode = 'Country is required.';
    isValid = false;
  }

  return isValid;
};

const saveOrganization = async () => {
  if (!validateForm(orgForm.value)) return;

  try {
    const payload = {
      oldOrgCode: isEditMode.value && selectedOrg.value ? selectedOrg.value.orgCode : orgForm.value.orgCode.trim(),
      orgCode: orgForm.value.orgCode.trim(),
      orgName: orgForm.value.orgName.trim(),
      address: orgForm.value.address.trim(),
      countryCode: Number(orgForm.value.countryCode)
    };

    if (isEditMode.value) {
      await organizationService.updateOrganization(payload);
      emit('show-toast', 'success', `Organization '${payload.orgName}' updated successfully.`);
    } else {
      await organizationService.createOrganization(payload);
      emit('show-toast', 'success', `Organization '${payload.orgName}' created successfully.`);
    }
    showFormModal.value = false;
    fetchOrganizations();
  } catch (error: any) {
    emit('show-toast', 'error', 'Failed to save organization: ' + (error.response?.data?.error || error.message));
  }
};

const saveInlineEdit = async () => {
  const mockForm = {
    orgCode: inlineEditForm.orgCode,
    orgName: inlineEditForm.orgName,
    address: inlineEditForm.address,
    countryCode: inlineEditForm.countryCode
  };

  if (!validateForm(mockForm)) {
    const firstError = Object.values(formErrors)[0];
    emit('show-toast', 'error', firstError);
    return;
  }

  try {
    const payload = {
      oldOrgCode: inlineEditForm.oldOrgCode,
      orgCode: inlineEditForm.orgCode.trim(),
      orgName: inlineEditForm.orgName.trim(),
      address: inlineEditForm.address.trim(),
      countryCode: inlineEditForm.countryCode
    };

    await organizationService.updateOrganization(payload);
    emit('show-toast', 'success', `Organization '${payload.orgName}' updated successfully inline.`);
    editingRowId.value = null;
    fetchOrganizations();
  } catch (error: any) {
    emit('show-toast', 'error', 'Failed to update organization: ' + (error.response?.data?.error || error.message));
  }
};

// View Details
const viewDetails = (item: Organization) => {
  selectedOrg.value = item;
  showViewModal.value = true;
};

// Delete Organization
const confirmDelete = (item: Organization) => {
  selectedOrg.value = item;
  showDeleteConfirm.value = true;
};

const deleteOrg = async () => {
  if (!selectedOrg.value) return;
  try {
    await organizationService.deleteOrganization(selectedOrg.value.orgCode);
    emit('show-toast', 'success', `Organization '${selectedOrg.value.orgName}' deleted successfully.`);
    showDeleteConfirm.value = false;
    fetchOrganizations();
  } catch (error: any) {
    emit('show-toast', 'error', 'Failed to delete organization: ' + (error.response?.data?.error || error.message));
  }
};
</script>

<style scoped>
.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  gap: 1.5rem;
}
.inline-input, .inline-select {
  height: 32px;
  padding: 2px 8px;
  font-size: 0.875rem;
}
.btn-group {
  display: flex;
  gap: 0.35rem;
}
.inline-action-btn, .inline-save-btn, .inline-cancel-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  height: 28px;
}
</style>
