<template>
  <div class="form-group">
    <label v-if="label" class="form-label">
      {{ label }}
      <span v-if="required" class="required-indicator">*</span>
    </label>
    
    <div class="multiselect-container" ref="containerRef">
      <!-- Toggle Box -->
      <div 
        class="form-control multiselect-trigger" 
        :class="{ 'is-invalid': !!error, 'disabled': disabled }" 
        @click="toggleDropdown"
      >
        <div class="selected-pills">
          <span v-if="selectedOptions.length === 0" class="placeholder-text">
            {{ placeholder || 'Select programs' }}
          </span>
          <div 
            v-else 
            v-for="opt in selectedOptions" 
            :key="opt.value" 
            class="multiselect-badge"
          >
            <span class="badge-text">{{ opt.label }}</span>
            <span 
              v-if="!disabled"
              class="multiselect-badge-remove" 
              @click.stop="removeOption(opt.value)"
              role="button"
              aria-label="Remove program"
            >
              &times;
            </span>
          </div>
        </div>
        
        <!-- Chevron -->
        <span class="chevron-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </span>
      </div>

      <!-- Dropdown Checklist -->
      <div v-if="isOpen && !disabled" class="dropdown-checklist">
        <div 
          v-for="opt in options" 
          :key="opt.value" 
          class="checklist-item" 
          @click.stop="toggleOption(opt.value)"
        >
          <input 
            type="checkbox" 
            :id="'prog-' + opt.value"
            :checked="modelValue.includes(opt.value)" 
            class="checkbox-input"
            @click.stop
            @change="toggleOption(opt.value)"
          />
          <label :for="'prog-' + opt.value" class="checkbox-label" @click.stop>{{ opt.label }}</label>
        </div>
        <div v-if="options.length === 0" class="no-options">
          No programs available.
        </div>
      </div>
    </div>
    <span v-if="error" class="validation-error">{{ error }}</span>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps<{
  modelValue: (string | number)[];
  options: Array<{ value: string | number; label: string }>;
  label?: string;
  required?: boolean;
  error?: string;
  placeholder?: string;
  disabled?: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: (string | number)[]): void;
}>();

const isOpen = ref(false);
const containerRef = ref<HTMLElement | null>(null);

const selectedOptions = computed(() => {
  return props.options.filter(opt => props.modelValue.includes(opt.value));
});

const toggleDropdown = () => {
  if (props.disabled) return;
  isOpen.value = !isOpen.value;
};

const toggleOption = (value: string | number) => {
  if (props.disabled) return;
  const newValue = [...props.modelValue];
  const idx = newValue.indexOf(value);
  if (idx > -1) {
    newValue.splice(idx, 1);
  } else {
    newValue.push(value);
  }
  emit('update:modelValue', newValue);
};

const removeOption = (value: string | number) => {
  if (props.disabled) return;
  const newValue = props.modelValue.filter(v => v !== value);
  emit('update:modelValue', newValue);
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
.multiselect-container {
  position: relative;
  width: 100%;
}

.multiselect-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 38px;
  cursor: pointer;
  padding: 0.25rem 0.75rem;
  background-color: var(--bg-surface);
  flex-wrap: wrap;
}

.multiselect-trigger.disabled {
  cursor: not-allowed;
  background-color: #f1f5f9;
}

.selected-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  flex: 1;
  align-items: center;
  min-width: 0;
}

.placeholder-text {
  color: var(--text-muted);
  font-size: 0.875rem;
  user-select: none;
}

.chevron-icon {
  color: var(--text-muted);
  display: flex;
  align-items: center;
  margin-left: 0.5rem;
}

.dropdown-checklist {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background-color: var(--bg-surface);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  margin-top: 0.25rem;
  max-height: 200px;
  overflow-y: auto;
  z-index: 10;
  box-shadow: var(--shadow-lg);
  display: flex;
  flex-direction: column;
}

.checklist-item {
  display: flex;
  align-items: center;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  cursor: pointer;
  transition: background-color var(--transition-fast);
}

.checklist-item:hover {
  background-color: #f8fafc;
}

.checkbox-input {
  cursor: pointer;
  margin-right: 0.625rem;
  width: 15px;
  height: 15px;
  accent-color: var(--color-primary);
}

.checkbox-label {
  cursor: pointer;
  flex: 1;
  user-select: none;
  font-weight: 400;
  color: var(--text-primary);
}

.no-options {
  padding: 1rem;
  text-align: center;
  font-size: 0.875rem;
  color: var(--text-muted);
}
</style>
