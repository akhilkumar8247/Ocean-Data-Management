<template>
  <div class="form-group dropdown-with-action" ref="containerRef">
    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
      <label v-if="label" :for="id" class="form-label" style="margin: 0;">
        {{ label }}
        <span v-if="required" class="required-indicator">*</span>
      </label>
      <a 
        v-if="actionText" 
        href="#" 
        class="dropdown-action-link" 
        @click.prevent="handleAction"
      >
        {{ actionText }}
      </a>
    </div>
    
    <!-- Custom Searchable Dropdown -->
    <div v-if="searchable" class="searchable-select-container">
      <div 
        class="form-control select-trigger" 
        :class="{ 'is-invalid': !!error, 'disabled': disabled, 'is-open': isOpen }" 
        @click="toggleDropdown"
      >
        <span class="selected-text" :class="{ 'placeholder-text': !selectedLabel }">
          {{ selectedLabel || placeholder || 'Select an option' }}
        </span>
        <span class="chevron-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </span>
      </div>

      <div v-if="isOpen && !disabled" class="dropdown-panel">
        <div class="search-wrapper" @click.stop>
          <input 
            ref="searchInputRef"
            type="text" 
            v-model="searchQuery" 
            class="form-control search-input" 
            placeholder="Type to search..." 
            @keydown.down.prevent="navigateDown"
            @keydown.up.prevent="navigateUp"
            @keydown.enter.prevent="selectFocused"
            @keydown.esc="isOpen = false"
          />
        </div>
        <div class="options-list" ref="optionsListRef">
          <div 
            v-for="(opt, idx) in filteredOptions" 
            :key="opt.value" 
            class="option-item" 
            :class="{ 
              'selected': opt.value === modelValue,
              'focused': idx === focusedIndex
            }"
            @click.stop="selectOption(opt)"
          >
            {{ opt.label }}
          </div>
          <div 
            v-if="searchable && searchQuery && actionText" 
            class="option-item create-action-item"
            :class="{ 'focused': filteredOptions.length === focusedIndex }"
            @click.stop="triggerQuickCreate"
          >
            {{ actionText === '+ Add New Contact' ? `+ Add "${searchQuery}" as New Contact` : `+ Add "${searchQuery}"` }}
          </div>
          <div v-if="filteredOptions.length === 0 && (!searchQuery || !actionText)" class="no-options">
            No options found.
          </div>
        </div>
      </div>
    </div>

    <!-- Native Dropdown -->
    <select
      v-else
      :id="id"
      :value="modelValue === null ? '' : modelValue"
      @change="handleChange"
      class="form-control"
      :class="{ 'is-invalid': !!error }"
      :disabled="disabled"
    >
      <option value="" disabled>{{ placeholder || 'Select an option' }}</option>
      <option v-for="opt in options" :key="opt.value" :value="opt.value">
        {{ opt.label }}
      </option>
    </select>
    <span v-if="error" class="validation-error">{{ error }}</span>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, watch, onMounted, onUnmounted } from 'vue';

const props = withDefaults(
  defineProps<{
    modelValue: string | number | null;
    id: string;
    options: Array<{ value: string | number; label: string }>;
    label?: string;
    required?: boolean;
    error?: string;
    placeholder?: string;
    disabled?: boolean;
    actionText?: string;
    searchable?: boolean;
  }>(),
  {
    searchable: false,
    required: false,
    disabled: false,
  }
);

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number | null): void;
  (e: 'action'): void;
  (e: 'create-new', query: string): void;
}>();

const isOpen = ref(false);
const searchQuery = ref('');
const focusedIndex = ref(0);

const containerRef = ref<HTMLElement | null>(null);
const searchInputRef = ref<HTMLInputElement | null>(null);
const optionsListRef = ref<HTMLElement | null>(null);

const selectedLabel = computed(() => {
  const selectedOpt = props.options.find(opt => opt.value === props.modelValue);
  return selectedOpt ? selectedOpt.label : '';
});

const filteredOptions = computed(() => {
  if (!searchQuery.value) return props.options;
  const query = searchQuery.value.toLowerCase().trim();
  return props.options.filter(opt => 
    opt.label.toLowerCase().includes(query)
  );
});

const totalNavigableCount = computed(() => {
  let count = filteredOptions.value.length;
  if (props.searchable && searchQuery.value && props.actionText) {
    count += 1; // Extra item for "Quick Create"
  }
  return count;
});

// Reset highlight on query change
watch([searchQuery, () => props.options], () => {
  focusedIndex.value = 0;
});

const toggleDropdown = () => {
  if (props.disabled) return;
  isOpen.value = !isOpen.value;
};

// Auto-focus search input when opening
watch(isOpen, (newVal) => {
  if (newVal) {
    searchQuery.value = '';
    focusedIndex.value = 0;
    nextTick(() => {
      if (searchInputRef.value) {
        searchInputRef.value.focus();
      }
    });
  }
});

const selectOption = (opt: { value: string | number; label: string }) => {
  emit('update:modelValue', opt.value);
  isOpen.value = false;
};

const triggerQuickCreate = () => {
  emit('create-new', searchQuery.value);
  isOpen.value = false;
};

// Keyboard Navigation Helpers
const navigateDown = () => {
  const count = totalNavigableCount.value;
  if (count > 0) {
    focusedIndex.value = (focusedIndex.value + 1) % count;
    scrollToFocused();
  }
};

const navigateUp = () => {
  const count = totalNavigableCount.value;
  if (count > 0) {
    focusedIndex.value = (focusedIndex.value - 1 + count) % count;
    scrollToFocused();
  }
};

const selectFocused = () => {
  const idx = focusedIndex.value;
  const filteredCount = filteredOptions.value.length;
  if (idx >= 0 && idx < filteredCount) {
    selectOption(filteredOptions.value[idx]);
  } else if (idx === filteredCount && props.actionText && searchQuery.value) {
    triggerQuickCreate();
  }
};

const scrollToFocused = () => {
  nextTick(() => {
    if (!optionsListRef.value) return;
    const list = optionsListRef.value;
    const items = list.querySelectorAll('.option-item');
    const focusedEl = items[focusedIndex.value] as HTMLElement;
    if (!focusedEl) return;

    const listScrollTop = list.scrollTop;
    const listHeight = list.clientHeight;
    const itemTop = focusedEl.offsetTop;
    const itemHeight = focusedEl.offsetHeight;

    if (itemTop < listScrollTop) {
      list.scrollTop = itemTop;
    } else if (itemTop + itemHeight > listScrollTop + listHeight) {
      list.scrollTop = itemTop + itemHeight - listHeight;
    }
  });
};

const handleChange = (e: Event) => {
  const target = e.target as HTMLSelectElement;
  const val = target.value;
  const numVal = Number(val);
  emit('update:modelValue', isNaN(numVal) || val === '' ? val : numVal);
};

const handleAction = () => {
  emit('action');
};

const handleClickOutside = (e: MouseEvent) => {
  if (containerRef.value && !containerRef.value.contains(e.target as Node)) {
    isOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.searchable-select-container {
  position: relative;
  width: 100%;
}

.select-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  background-color: var(--bg-surface);
  user-select: none;
  height: 38px;
}

.select-trigger.disabled {
  cursor: not-allowed;
  background-color: #f1f5f9;
  opacity: 0.7;
}

.select-trigger.is-open {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px rgba(30, 41, 59, 0.1);
}

.selected-text {
  font-size: 0.875rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: var(--text-primary);
}

.placeholder-text {
  color: var(--text-muted);
}

.chevron-icon {
  color: var(--text-muted);
  display: flex;
  align-items: center;
  margin-left: 0.5rem;
}

.dropdown-panel {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background-color: var(--bg-surface);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  margin-top: 0.25rem;
  z-index: 100;
  box-shadow: var(--shadow-lg);
  display: flex;
  flex-direction: column;
}

.search-wrapper {
  padding: 0.5rem;
  border-bottom: 1px solid var(--border-color);
}

.search-input {
  width: 100%;
  height: 32px;
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

.options-list {
  max-height: 200px;
  overflow-y: auto;
}

.option-item {
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  cursor: pointer;
  transition: background-color var(--transition-fast);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: var(--text-primary);
}

.option-item:hover, .option-item.focused {
  background-color: #f8fafc;
}

.option-item.selected {
  background-color: #e2e8f0;
  font-weight: 500;
}

.create-action-item {
  color: var(--color-info);
  font-weight: 500;
  border-top: 1px dashed var(--border-color);
  background-color: var(--color-info-bg);
}

.create-action-item:hover, .create-action-item.focused {
  background-color: var(--color-info-border);
  color: var(--color-info);
}

.no-options {
  padding: 1rem;
  text-align: center;
  font-size: 0.875rem;
  color: var(--text-muted);
}
</style>
