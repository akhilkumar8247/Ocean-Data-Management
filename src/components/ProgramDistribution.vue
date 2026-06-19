<template>
  <div class="program-distribution-wrapper">
    <div class="distribution-list">
      <div 
        v-for="(item, idx) in sortedPrograms" 
        :key="item.prgmCode" 
        class="distribution-item"
      >
        <div class="item-header">
          <span class="program-name">{{ item.prgmName }}</span>
          <span class="program-count">{{ item.count }}</span>
        </div>
        
        <div class="progress-bar-bg">
          <div 
            class="progress-bar-fill" 
            :style="{ 
              width: `${percentages[idx]}%`,
              background: getGradient(idx)
            }"
          ></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';

interface ProgramCountItem {
  prgmCode: string;
  prgmName: string;
  count: number;
}

interface Props {
  programs: ProgramCountItem[];
}

const props = defineProps<Props>();

const sortedPrograms = computed(() => {
  return [...props.programs].sort((a, b) => b.count - a.count);
});

const maxCount = computed(() => {
  const counts = props.programs.map(p => p.count);
  return Math.max(...counts, 1);
});

// Animation logic for width scaling on mount
const animationTriggered = ref(false);

const percentages = computed(() => {
  return sortedPrograms.value.map(item => {
    if (!animationTriggered.value) return 0;
    return (item.count / maxCount.value) * 100;
  });
});

const colors = [
  'linear-gradient(90deg, #3b82f6, #1d4ed8)', // Blue
  'linear-gradient(90deg, #10b981, #047857)', // Emerald
  'linear-gradient(90deg, #f97316, #c2410c)', // Orange
  'linear-gradient(90deg, #8b5cf6, #6d28d9)', // Violet
  'linear-gradient(90deg, #ec4899, #be185d)'  // Pink
];

const getGradient = (idx: number) => {
  return colors[idx % colors.length];
};

onMounted(() => {
  // Simple timeout to trigger progress bar animations
  setTimeout(() => {
    animationTriggered.value = true;
  }, 100);
});
</script>

<style scoped>
.program-distribution-wrapper {
  width: 100%;
}

.distribution-list {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.distribution-item {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.875rem;
  font-family: 'Inter', system-ui, sans-serif;
}

.program-name {
  font-weight: 600;
  color: var(--text-primary);
}

.program-count {
  font-weight: 700;
  color: var(--text-secondary);
  background: #f1f5f9;
  padding: 0.15rem 0.5rem;
  border-radius: 6px;
  font-size: 0.75rem;
}

.progress-bar-bg {
  height: 8px;
  background-color: #f1f5f9;
  border-radius: 9999px;
  overflow: hidden;
  width: 100%;
}

.progress-bar-fill {
  height: 100%;
  border-radius: 9999px;
  width: 0;
  transition: width 1.2s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
