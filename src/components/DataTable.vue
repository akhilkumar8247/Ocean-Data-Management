<template>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th 
            v-for="header in headers" 
            :key="header.key"
            :class="{ sortable: header.sortable }"
            @click="header.sortable && handleSort(header.key)"
            :style="{ width: header.width }"
          >
            <div style="display: flex; align-items: center; gap: 0.25rem;">
              <span>{{ header.label }}</span>
              <span v-if="header.sortable && sortBy === header.key" class="sort-icon">
                <svg v-if="sortOrder === 'asc'" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
              </span>
            </div>
          </th>
        </tr>
      </thead>
      
      <tbody>
        <!-- Loading State Skeletons -->
        <template v-if="loading">
          <tr v-for="i in skeletonCount" :key="'skeleton-' + i">
            <td v-for="header in headers" :key="'skeleton-col-' + header.key">
              <div class="skeleton skeleton-td"></div>
            </td>
          </tr>
        </template>
        
        <!-- Empty State -->
        <tr v-else-if="items.length === 0">
          <td :colspan="headers.length" style="padding: 0;">
            <slot name="empty">
              <div style="padding: 3rem; text-align: center; color: var(--text-muted);">
                No records found.
              </div>
            </slot>
          </td>
        </tr>
        
        <!-- Data State -->
        <template v-else>
          <tr v-for="(item, idx) in items" :key="item[idKey] || idx">
            <td v-for="header in headers" :key="'col-' + header.key">
              <slot :name="'cell(' + header.key + ')'" :item="item" :index="idx">
                {{ item[header.key] }}
              </slot>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    headers: Array<{ key: string; label: string; sortable?: boolean; width?: string }>;
    items: any[];
    loading?: boolean;
    sortBy?: string;
    sortOrder?: 'asc' | 'desc';
    idKey?: string;
    skeletonCount?: number;
  }>(),
  {
    loading: false,
    idKey: 'id',
    skeletonCount: 5,
  }
);

const emit = defineEmits<{
  (e: 'sort', key: string): void;
}>();

const handleSort = (key: string) => {
  emit('sort', key);
};
</script>

<style scoped>
.sort-icon {
  display: inline-flex;
  align-items: center;
  color: var(--text-secondary);
}
</style>
