<template>
  <div class="search-input-wrapper">
    <span class="search-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
    </span>
    <input
      type="text"
      :value="modelValue"
      @input="handleInput"
      :placeholder="placeholder || 'Search records...'"
    />
  </div>
</template>

<script setup lang="ts">
import { onUnmounted } from 'vue';

const props = defineProps<{
  modelValue: string;
  placeholder?: string;
  debounceMs?: number;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', val: string): void;
  (e: 'search', val: string): void;
}>();

let debounceTimer: any = null;

const handleInput = (e: Event) => {
  const val = (e.target as HTMLInputElement).value;
  emit('update:modelValue', val);
  
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    emit('search', val);
  }, props.debounceMs || 300);
};

onUnmounted(() => {
  clearTimeout(debounceTimer);
});
</script>
