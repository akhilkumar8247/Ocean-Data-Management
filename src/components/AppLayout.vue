<template>
  <div class="app-container">
    <!-- Main Content Frame -->
    <div class="main-content">
      <TopHeader :title="currentTitle" />
      
      <main class="page-wrapper">
        <slot></slot>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import TopHeader from './TopHeader.vue';

const route = useRoute();

const currentTitle = computed(() => {
  if (route.meta && typeof route.meta.title === 'string') {
    return route.meta.title;
  }
  return 'Data Management System';
});
</script>

<style scoped>
/* AppLayout is now a simpler top header content frame wrapper */
</style>
