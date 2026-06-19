<template>
  <div class="pagination">
    <div class="pagination-info">
      Showing 
      <span class="font-semibold">{{ startItem }}</span> 
      to 
      <span class="font-semibold">{{ endItem }}</span> 
      of 
      <span class="font-semibold">{{ total }}</span> 
      records
    </div>
    
    <div class="pagination-buttons" v-if="totalPages > 1">
      <button 
        class="pagination-btn" 
        :disabled="page <= 1" 
        @click="changePage(page - 1)"
        aria-label="Previous page"
      >
        &lsaquo;
      </button>
      
      <button 
        v-for="p in visiblePages" 
        :key="p" 
        class="pagination-btn" 
        :class="{ active: p === page }" 
        @click="changePage(p)"
      >
        {{ p }}
      </button>
      
      <button 
        class="pagination-btn" 
        :disabled="page >= totalPages" 
        @click="changePage(page + 1)"
        aria-label="Next page"
      >
        &rsaquo;
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  page: number;
  limit: number;
  total: number;
  totalPages: number;
}>();

const emit = defineEmits<{
  (e: 'update:page', value: number): void;
}>();

const startItem = computed(() => {
  if (props.total === 0) return 0;
  return (props.page - 1) * props.limit + 1;
});

const endItem = computed(() => {
  return Math.min(props.page * props.limit, props.total);
});

const visiblePages = computed(() => {
  const range = [];
  const maxVisible = 5;
  let start = Math.max(1, props.page - 2);
  let end = Math.min(props.totalPages, start + maxVisible - 1);
  
  if (end - start < maxVisible - 1) {
    start = Math.max(1, end - maxVisible + 1);
  }
  
  for (let i = start; i <= end; i++) {
    range.push(i);
  }
  return range;
});

const changePage = (p: number) => {
  if (p < 1 || p > props.totalPages || p === props.page) return;
  emit('update:page', p);
};
</script>

<style scoped>
.font-semibold {
  font-weight: 600;
  color: var(--text-primary);
}
.pagination-info {
  font-size: 0.875rem;
  color: var(--text-secondary);
}
</style>
