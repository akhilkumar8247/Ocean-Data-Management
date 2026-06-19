<template>
  <div class="content-card">
    <!-- Page Header & Actions -->
    <div class="toolbar">
      <SearchBar 
        v-model="filters.search" 
        placeholder="Search contacts by name, email, organization, country..."
        @search="handleSearch" 
      />
      
      <button class="btn btn-primary" @click="openAddModal">
        <PlusIcon :size="16" />
        Add Contact
      </button>
    </div>

    <!-- Data Table -->
    <DataTable
      :headers="tableHeaders"
      :items="contacts"
      :loading="loading"
      :sort-by="filters.sortBy"
      :sort-order="filters.sortOrder"
      id-key="cId"
      @sort="handleSort"
    >
      <!-- ID -->
      <template #cell(cId)="{ item }">
        <span style="font-weight: 600; color: #64748b;">#{{ item.cId }}</span>
      </template>

      <!-- Contact Name: input when editing -->
      <template #cell(cName)="{ item }">
        <template v-if="editingRowId === item.cId">
          <input type="text" v-model="inlineEditForm.cName" class="form-control inline-input" />
        </template>
        <template v-else>
          <div style="font-weight: 500;">{{ item.cName }}</div>
        </template>
      </template>

      <!-- Email: input when editing -->
      <template #cell(emailId)="{ item }">
        <template v-if="editingRowId === item.cId">
          <input type="email" v-model="inlineEditForm.emailId" class="form-control inline-input" />
        </template>
        <template v-else>{{ item.emailId }}</template>
      </template>

      <!-- Organization & Country: dropdown when editing -->
      <template #cell(orgName)="{ item }">
        <template v-if="editingRowId === item.cId">
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

      <!-- Actions: Save/Cancel when editing, View/Edit/Delete otherwise -->
      <template #cell(actions)="{ item }">
        <div class="btn-group">
          <template v-if="editingRowId === item.cId">
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
            <button class="btn btn-danger inline-action-btn" @click="confirmDelete(item)" title="Delete Contact">
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
      @update:page="fetchContacts"
    />

    <!-- ============================================== -->
    <!-- 1. ADD / EDIT CONTACT MODAL -->
    <!-- ============================================== -->
    <Modal 
      :is-open="showFormModal" 
      :title="isEditMode ? 'Edit Contact' : 'Add Contact'" 
      size="md"
      @close="showFormModal = false"
    >
      <div style="display: flex; flex-direction: column; gap: 1rem;">
        <!-- Contact Name -->
        <FormInput
          v-model="contactForm.cName"
          id="c-name"
          label="Contact Name"
          type="text"
          :error="formErrors.cName"
          required
          placeholder="Letters, dots, and spaces allowed (e.g. b. Akhil)"
        />

        <!-- Email Address -->
        <FormInput
          v-model="contactForm.emailId"
          id="c-email"
          label="Email Address"
          type="email"
          :error="formErrors.emailId"
          required
          placeholder="e.g. akhil@domain.com (.com, .org, .net, .in)"
        />

        <!-- Organization Dropdown -->
        <FormSelect
          v-model="contactForm.orgCode"
          id="c-org"
          label="Organization"
          :options="organizationOptions"
          :error="formErrors.orgCode"
          required
          searchable
        />

        <!-- Country (Static Display) -->
        <FormInput
          v-model="selectedOrgCountry"
          id="c-country"
          label="Country"
          disabled
          placeholder="Select an organization to show country"
        />
      </div>

      <template #footer>
        <button class="btn btn-secondary" @click="showFormModal = false">
          Cancel
        </button>
        <button class="btn btn-primary" @click="saveContact">
          {{ isEditMode ? 'Update Contact' : 'Create Contact' }}
        </button>
      </template>
    </Modal>

    <!-- ============================================== -->
    <!-- 2. VIEW DETAILS MODAL -->
    <!-- ============================================== -->
    <Modal 
      :is-open="showViewModal" 
      title="Contact Details" 
      size="md"
      @close="showViewModal = false"
    >
      <div v-if="selectedContact" class="details-view">
        <div class="detail-row">
          <span class="detail-label">Contact ID:</span>
          <span class="detail-val">#{{ selectedContact.cId }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Contact Name:</span>
          <span class="detail-val">{{ selectedContact.cName }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Email Address:</span>
          <span class="detail-val">{{ selectedContact.emailId }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Organization:</span>
          <span class="detail-val">{{ selectedContact.orgName }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Country:</span>
          <span class="detail-val">{{ selectedContact.countryName }}</span>
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
      title="Delete Contact"
      :message="`Are you sure you want to delete contact '${selectedContact?.cName}' (#${selectedContact?.cId})? This action cannot be undone.`"
      confirm-text="Delete"
      cancel-text="Cancel"
      @confirm="deleteContact"
      @cancel="showDeleteConfirm = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { 
  Plus as PlusIcon, 
  Eye as EyeIcon, 
  Edit as EditIcon, 
  Check as CheckIcon,
  X as XIcon,
  Trash as TrashIcon
} from '@lucide/vue';

// Services & Types
import contactService from '../services/contactService';
import referenceService from '../services/referenceService';
import type { Contact, ReferenceData } from '../types';

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
const contacts = ref<Contact[]>([]);
const loading = ref(false);
const totalRecords = ref(0);
const totalPages = ref(1);

const filters = reactive({
  search: '',
  sortBy: 'cName',
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
const selectedContact = ref<Contact | null>(null);

// Inline editing state
const editingRowId = ref<number | null>(null);
const inlineEditForm = reactive({
  cId: 0,
  cName: '',
  emailId: '',
  orgCode: '',
  countryName: ''
});

// Form States
const contactForm = reactive({
  cId: undefined as number | undefined,
  cName: '',
  emailId: '',
  orgCode: '' as any
});

const selectedOrgCountry = ref('');

// Validation Errors
const formErrors = reactive<Record<string, string>>({});

// Headers config
const tableHeaders = [
  { key: 'cId', label: 'ID', sortable: true, width: '90px' },
  { key: 'cName', label: 'Contact Name', sortable: true },
  { key: 'emailId', label: 'Email Address', sortable: true },
  { key: 'orgName', label: 'Organization', sortable: true },
  { key: 'actions', label: 'Actions', sortable: false, width: '220px' }
];

// Computed Options
const organizationOptions = computed(() => 
  refData.value.organizations.map(o => ({
    value: o.orgCode,
    label: `${o.orgName} (${o.countryName})`
  }))
);

// Track country helper in modal
watch(() => contactForm.orgCode, (newOrgCode) => {
  const org = refData.value.organizations.find(o => o.orgCode === newOrgCode);
  selectedOrgCountry.value = org ? org.countryName || '' : '';
});

// Track country helper in inline editing
watch(() => inlineEditForm.orgCode, (newOrgCode) => {
  const org = refData.value.organizations.find(o => o.orgCode === newOrgCode);
  inlineEditForm.countryName = org ? org.countryName || '' : '';
});

// Fetch Operations
const fetchContacts = async () => {
  loading.value = true;
  try {
    const res = await contactService.getContacts({
      search: filters.search,
      sortBy: filters.sortBy,
      sortOrder: filters.sortOrder,
      page: filters.page,
      limit: filters.limit
    });
    contacts.value = res.data.data;
    totalRecords.value = res.data.total;
    totalPages.value = res.data.totalPages;
  } catch (error: any) {
    emit('show-toast', 'error', 'Failed to fetch contacts: ' + (error.response?.data?.error || error.message));
  } finally {
    loading.value = false;
  }
};

const fetchReferenceData = async () => {
  try {
    const res = await referenceService.getAllReferences();
    refData.value = res.data;
  } catch (error) {
    console.error('Failed to load reference organizations:', error);
  }
};

onMounted(() => {
  fetchContacts();
  fetchReferenceData();
});

// Search & Sort Handlers
const handleSearch = () => {
  filters.page = 1;
  fetchContacts();
};

const handleSort = (key: string) => {
  if (filters.sortBy === key) {
    filters.sortOrder = filters.sortOrder === 'asc' ? 'desc' : 'asc';
  } else {
    filters.sortBy = key;
    filters.sortOrder = 'asc';
  }
  fetchContacts();
};

// Add / Edit Handlers
const openAddModal = () => {
  isEditMode.value = false;
  contactForm.cId = undefined;
  contactForm.cName = '';
  contactForm.emailId = '';
  contactForm.orgCode = '';
  selectedOrgCountry.value = '';
  Object.keys(formErrors).forEach(key => delete formErrors[key]);
  showFormModal.value = true;
};

const startInlineEdit = (item: Contact) => {
  cancelInlineEdit();
  editingRowId.value = item.cId;
  inlineEditForm.cId = item.cId;
  inlineEditForm.cName = item.cName;
  inlineEditForm.emailId = item.emailId;
  inlineEditForm.orgCode = item.orgCode;
  inlineEditForm.countryName = item.countryName || '';
};

const cancelInlineEdit = () => {
  editingRowId.value = null;
};

const validateForm = (data: { cName: string; emailId: string; orgCode: string }): boolean => {
  Object.keys(formErrors).forEach(key => delete formErrors[key]);
  let isValid = true;

  if (!data.cName.trim()) {
    formErrors.cName = 'Contact Name is required.';
    isValid = false;
  } else if (!/^[A-Za-z. ]+$/.test(data.cName.trim())) {
    formErrors.cName = 'Contact Name can only contain letters, spaces, and dots.';
    isValid = false;
  }

  if (!data.emailId.trim()) {
    formErrors.emailId = 'Email Address is required.';
    isValid = false;
  } else if (!/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.(com|in|org|net)$/.test(data.emailId.trim())) {
    formErrors.emailId = 'Email must be valid and end with .com, .in, .org, or .net.';
    isValid = false;
  }

  if (!data.orgCode) {
    formErrors.orgCode = 'Organization is required.';
    isValid = false;
  }

  return isValid;
};

const saveContact = async () => {
  if (!validateForm(contactForm)) return;

  try {
    const payload = {
      cName: contactForm.cName.trim(),
      emailId: contactForm.emailId.trim(),
      orgCode: contactForm.orgCode
    };

    if (isEditMode.value && contactForm.cId !== undefined) {
      await contactService.updateContact({
        ...payload,
        cId: contactForm.cId
      });
      emit('show-toast', 'success', `Contact '${payload.cName}' updated successfully.`);
    } else {
      await contactService.createContact(payload);
      emit('show-toast', 'success', `Contact '${payload.cName}' created successfully.`);
    }
    showFormModal.value = false;
    fetchContacts();
    fetchReferenceData(); // Refresh dropdown cache
  } catch (error: any) {
    emit('show-toast', 'error', 'Failed to save contact: ' + (error.response?.data?.error || error.message));
  }
};

const saveInlineEdit = async () => {
  const mockForm = {
    cName: inlineEditForm.cName,
    emailId: inlineEditForm.emailId,
    orgCode: inlineEditForm.orgCode
  };

  if (!validateForm(mockForm)) {
    const firstError = Object.values(formErrors)[0];
    emit('show-toast', 'error', firstError);
    return;
  }

  try {
    const payload = {
      cId: inlineEditForm.cId,
      cName: inlineEditForm.cName.trim(),
      emailId: inlineEditForm.emailId.trim(),
      orgCode: inlineEditForm.orgCode
    };

    await contactService.updateContact(payload);
    emit('show-toast', 'success', `Contact '${payload.cName}' updated successfully inline.`);
    editingRowId.value = null;
    fetchContacts();
    fetchReferenceData();
  } catch (error: any) {
    emit('show-toast', 'error', 'Failed to update contact: ' + (error.response?.data?.error || error.message));
  }
};

// View Details
const viewDetails = (item: Contact) => {
  selectedContact.value = item;
  showViewModal.value = true;
};

// Delete Contact
const confirmDelete = (item: Contact) => {
  selectedContact.value = item;
  showDeleteConfirm.value = true;
};

const deleteContact = async () => {
  if (!selectedContact.value) return;
  try {
    await contactService.deleteContact(selectedContact.value.cId);
    emit('show-toast', 'success', `Contact '${selectedContact.value.cName}' deleted successfully.`);
    showDeleteConfirm.value = false;
    fetchContacts();
    fetchReferenceData();
  } catch (error: any) {
    emit('show-toast', 'error', 'Failed to delete contact: ' + (error.response?.data?.error || error.message));
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
