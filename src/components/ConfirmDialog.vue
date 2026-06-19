<template>
  <Modal :is-open="isOpen" :title="title" size="sm" @close="cancel">
    <div class="confirm-body">
      <p class="confirm-message">{{ message }}</p>
    </div>
    
    <template #footer>
      <button class="btn btn-secondary" @click="cancel">
        {{ cancelText }}
      </button>
      <button 
        class="btn" 
        :class="isDanger ? 'btn-danger' : 'btn-primary'" 
        @click="confirm"
      >
        {{ confirmText }}
      </button>
    </template>
  </Modal>
</template>

<script setup lang="ts">
import Modal from './Modal.vue';

const props = withDefaults(
  defineProps<{
    isOpen: boolean;
    title: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
    isDanger?: boolean;
  }>(),
  {
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    isDanger: true,
  }
);

const emit = defineEmits<{
  (e: 'confirm'): void;
  (e: 'cancel'): void;
}>();

const confirm = () => {
  emit('confirm');
};

const cancel = () => {
  emit('cancel');
};
</script>

<style scoped>
.confirm-body {
  padding: 0.25rem 0;
}
.confirm-message {
  font-size: 0.925rem;
  color: var(--text-secondary);
  line-height: 1.5;
}
</style>
