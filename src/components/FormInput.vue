<template>
  <div class="form-group">
    <label v-if="label" :for="id" class="form-label">
      {{ label }}
      <span v-if="required" class="required-indicator">*</span>
    </label>
    <input
      :id="id"
      :type="type"
      :value="modelValue"
      @input="handleInput"
      class="form-control"
      :class="{ 'is-invalid': !!error }"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      v-bind="$attrs"
    />
    <span v-if="error" class="validation-error">{{ error }}</span>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  modelValue: string | number | null;
  id: string;
  label?: string;
  type?: string;
  required?: boolean;
  error?: string;
  placeholder?: string;
  disabled?: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void;
}>();

const handleInput = (e: Event) => {
  emit('update:modelValue', (e.target as HTMLInputElement).value);
};
</script>
