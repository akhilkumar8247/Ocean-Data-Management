<template>
  <div class="filter-panel-wrapper">
    <div class="filter-grid">
      <slot></slot>
      
      <!-- Reset Button Column -->
      <div class="filter-actions-col">
        <button 
          v-if="showReset" 
          class="btn btn-secondary reset-btn" 
          @click="handleReset"
          type="button"
        >
          <!-- Reset Icon -->
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
          Clear Filters
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    showReset?: boolean;
  }>(),
  {
    showReset: true
  }
);

const emit = defineEmits<{
  (e: 'reset'): void;
}>();

const handleReset = () => {
  emit('reset');
};
</script>

<style scoped>
.filter-panel-wrapper {
  width: 100%;
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  align-items: end;
}

.filter-actions-col {
  display: flex;
  justify-content: flex-start;
}

.reset-btn {
  width: 100%;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

@media (max-width: 768px) {
  .filter-grid {
    grid-template-columns: 1fr;
  }
  
  .reset-btn {
    width: 100%;
  }
}
</style>
