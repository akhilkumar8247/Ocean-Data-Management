<template>
  <AppLayout>
    <router-view @show-toast="triggerToast"></router-view>
  </AppLayout>

  <!-- Global Toast Notification Container -->
  <div class="toast-container" aria-live="polite">
    <div 
      v-for="t in toasts" 
      :key="t.id" 
      class="toast" 
      :class="'toast-' + t.type"
      @click="removeToast(t.id)"
      role="alert"
    >
      <!-- Success Icon -->
      <svg v-if="t.type === 'success'" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="toast-icon"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
      
      <!-- Error Icon -->
      <svg v-else-if="t.type === 'error'" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="toast-icon"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      
      <!-- Info Icon -->
      <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="toast-icon"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>

      <span class="toast-text">{{ t.text }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from './components/AppLayout.vue';
import type { ToastMessage } from './types';

const toasts = ref<ToastMessage[]>([]);

const triggerToast = (type: 'success' | 'error' | 'info', text: string) => {
  const id = Date.now();
  toasts.value.push({ id, type, text });
  
  // Auto dismiss after 4 seconds
  setTimeout(() => {
    removeToast(id);
  }, 4000);
};

const removeToast = (id: number) => {
  toasts.value = toasts.value.filter(t => t.id !== id);
};
</script>

<style scoped>
.toast-icon {
  flex-shrink: 0;
  margin-top: 0.125rem;
}
.toast-text {
  flex-grow: 1;
  word-break: break-word;
}
</style>
