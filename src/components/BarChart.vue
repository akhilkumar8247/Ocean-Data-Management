<template>
  <div class="bar-chart-wrapper" ref="chartWrapper">
    <div class="canvas-container">
      <canvas 
        ref="chartCanvas" 
        class="bar-chart-canvas"
        @mousemove="handleMouseMove"
        @mouseleave="handleMouseLeave"
      ></canvas>

      <!-- Custom Tooltip -->
      <div 
        v-if="tooltip.visible" 
        class="chart-tooltip" 
        :style="{ 
          left: `${tooltip.x}px`, 
          top: `${tooltip.y}px`,
          borderColor: tooltip.color 
        }"
      >
        <div class="tooltip-header">{{ tooltip.label }}</div>
        <div class="tooltip-body">
          <span class="tooltip-value">{{ tooltip.value }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch, onUnmounted } from 'vue';

interface ChartDataItem {
  label: string;
  value: number;
}

interface Props {
  data: ChartDataItem[];
  title?: string;
  maxValue?: number;
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Chart',
  maxValue: 0
});

const chartCanvas = ref<HTMLCanvasElement | null>(null);
const hoveredIndex = ref<number | null>(null);
const animationProgress = ref(0);
let animationFrameId: number | null = null;

// Store CSS-pixel dimensions for consistent draw/hover logic
const cssWidth = ref(300);
const cssHeight = ref(350);

const tooltip = reactive({
  visible: false,
  x: 0,
  y: 0,
  label: '',
  value: 0,
  color: ''
});

// Vibrant and modern colors (HSL gradients)
const colors = [
  { start: '#3b82f6', end: '#1d4ed8' }, // Blue
  { start: '#10b981', end: '#047857' }, // Emerald
  { start: '#f59e0b', end: '#b45309' }, // Amber
  { start: '#ef4444', end: '#b91c1c' }, // Red
  { start: '#8b5cf6', end: '#6d28d9' }, // Violet
  { start: '#ec4899', end: '#be185d' }, // Pink
  { start: '#06b6d4', end: '#0891b2' }, // Cyan
  { start: '#f97316', end: '#c2410c' }  // Orange
];

const drawBarChart = () => {
  const canvas = chartCanvas.value;
  if (!canvas || props.data.length === 0) return;

  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  // Use CSS-pixel dimensions (ctx is already scaled by DPR)
  const width = cssWidth.value;
  const height = cssHeight.value;
  const paddingLeft = 50;
  const paddingRight = 30;
  const paddingTop = 40;
  const paddingBottom = 90;
  
  const chartWidth = width - paddingLeft - paddingRight;
  const chartHeight = height - paddingTop - paddingBottom;

  // Clear canvas
  ctx.clearRect(0, 0, width, height);

  // Calculate max value for scaling
  const maxVal = props.maxValue || Math.max(...props.data.map(d => d.value), 1);

  // Draw grid lines
  ctx.strokeStyle = '#e2e8f0';
  ctx.lineWidth = 1;
  ctx.font = '500 10px Inter, system-ui, sans-serif';
  ctx.fillStyle = '#64748b';
  ctx.textAlign = 'right';
  ctx.textBaseline = 'middle';

  const gridTicks = 5;
  for (let i = 0; i <= gridTicks; i++) {
    const val = Math.round((i / gridTicks) * maxVal);
    const y = height - paddingBottom - (i / gridTicks) * chartHeight;
    
    // Grid line
    ctx.beginPath();
    ctx.moveTo(paddingLeft, y);
    ctx.lineTo(width - paddingRight, y);
    ctx.stroke();

    // Label
    ctx.fillText(val.toString(), paddingLeft - 10, y);
  }

  // Draw bars
  const barSpacing = chartWidth / props.data.length;
  const barWidth = barSpacing * 0.6;

  props.data.forEach((item, idx) => {
    const isHovered = hoveredIndex.value === idx;
    const x = paddingLeft + idx * barSpacing + (barSpacing - barWidth) / 2;
    
    // Scale height based on animation progress
    const barHeight = (item.value / maxVal) * chartHeight * animationProgress.value;
    const y = height - paddingBottom - barHeight;

    ctx.save();

    // Hover shadow
    if (isHovered && animationProgress.value === 1) {
      ctx.shadowBlur = 10;
      ctx.shadowColor = 'rgba(0, 0, 0, 0.15)';
    }

    // Gradient bar fill
    const grad = ctx.createLinearGradient(x, y, x, height - paddingBottom);
    grad.addColorStop(0, colors[idx % colors.length].start);
    grad.addColorStop(1, colors[idx % colors.length].end);

    ctx.fillStyle = grad;
    
    // Draw rounded rect bar
    const radius = 6;
    ctx.beginPath();
    if (barHeight > radius) {
      ctx.moveTo(x, y + radius);
      ctx.arcTo(x, y, x + radius, y, radius);
      ctx.arcTo(x + barWidth, y, x + barWidth, y + radius, radius);
      ctx.lineTo(x + barWidth, height - paddingBottom);
      ctx.lineTo(x, height - paddingBottom);
    } else {
      ctx.rect(x, y, barWidth, barHeight);
    }
    ctx.closePath();
    ctx.fill();

    ctx.restore();

    // Draw label below axis (rotated slightly for readability)
    ctx.save();
    ctx.translate(x + barWidth / 2, height - paddingBottom + 12);
    ctx.rotate(-Math.PI / 4);
    ctx.textAlign = 'right';
    ctx.fillStyle = isHovered ? '#0f172a' : '#475569';
    ctx.font = isHovered ? 'bold 10px Inter, system-ui, sans-serif' : '500 10px Inter, system-ui, sans-serif';
    
    const labelLimit = 16;
    const displayLabel = item.label.length > labelLimit ? item.label.slice(0, labelLimit) + '...' : item.label;
    ctx.fillText(displayLabel, 0, 0);
    ctx.restore();

    // Draw value on top of bar (when animation is complete)
    if (animationProgress.value === 1 && item.value > 0) {
      ctx.fillStyle = isHovered ? '#0f172a' : '#334155';
      ctx.font = 'bold 11px Inter, system-ui, sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText(item.value.toString(), x + barWidth / 2, y - 8);
    }
  });

  // Draw base axis line
  ctx.strokeStyle = '#cbd5e1';
  ctx.lineWidth = 1.5;
  ctx.beginPath();
  ctx.moveTo(paddingLeft, height - paddingBottom);
  ctx.lineTo(width - paddingRight, height - paddingBottom);
  ctx.stroke();
};

const handleMouseMove = (event: MouseEvent) => {
  const canvas = chartCanvas.value;
  if (!canvas || props.data.length === 0 || animationProgress.value < 1) return;

  const rect = canvas.getBoundingClientRect();
  const x = event.clientX - rect.left;
  const y = event.clientY - rect.top;

  // Use CSS-pixel dimensions to match the drawn coordinates
  const width = cssWidth.value;
  const height = cssHeight.value;
  const paddingLeft = 50;
  const paddingRight = 30;
  const paddingTop = 40;
  const paddingBottom = 90;
  
  const chartWidth = width - paddingLeft - paddingRight;
  const chartHeight = height - paddingTop - paddingBottom;
  const maxVal = props.maxValue || Math.max(...props.data.map(d => d.value), 1);

  const barSpacing = chartWidth / props.data.length;
  const barWidth = barSpacing * 0.6;

  let matchedIndex = -1;

  for (let i = 0; i < props.data.length; i++) {
    const barX = paddingLeft + i * barSpacing + (barSpacing - barWidth) / 2;
    const barHeight = (props.data[i].value / maxVal) * chartHeight;
    const barY = height - paddingBottom - barHeight;

    if (x >= barX && x <= barX + barWidth && y >= barY && y <= height - paddingBottom) {
      matchedIndex = i;
      break;
    }
  }

  if (matchedIndex !== -1) {
    const lastHovered = hoveredIndex.value;
    hoveredIndex.value = matchedIndex;

    if (lastHovered !== matchedIndex) {
      drawBarChart();
    }

    // Update Tooltip
    const item = props.data[matchedIndex];
    tooltip.visible = true;
    tooltip.x = x + 15;
    tooltip.y = y - 30;
    tooltip.label = item.label;
    tooltip.value = item.value;
    tooltip.color = colors[matchedIndex % colors.length].start;
  } else {
    handleMouseLeave();
  }
};

const handleMouseLeave = () => {
  if (hoveredIndex.value !== null) {
    hoveredIndex.value = null;
    drawBarChart();
  }
  tooltip.visible = false;
};

const runEntryAnimation = () => {
  const duration = 800; // ms
  const startTime = performance.now();

  const animate = (time: number) => {
    const elapsed = time - startTime;
    const progress = Math.min(elapsed / duration, 1);
    
    // Ease-out cubic formula
    animationProgress.value = 1 - Math.pow(1 - progress, 3);
    drawBarChart();

    if (progress < 1) {
      animationFrameId = requestAnimationFrame(animate);
    } else {
      animationProgress.value = 1;
      drawBarChart();
    }
  };

  if (animationFrameId) {
    cancelAnimationFrame(animationFrameId);
  }
  animationFrameId = requestAnimationFrame(animate);
};
let resizeObserver: ResizeObserver | null = null;

const resizeCanvas = () => {
  const canvas = chartCanvas.value;
  if (!canvas) return;
  
  const container = canvas.parentElement;
  if (!container) return;
  
  const dpr = window.devicePixelRatio || 1;
  const cWidth = container.clientWidth || 300;
  const cHeight = 350;
  
  // Store CSS-pixel dimensions
  cssWidth.value = cWidth;
  cssHeight.value = cHeight;
  
  canvas.width = cWidth * dpr;
  canvas.height = cHeight * dpr;
  canvas.style.width = `${cWidth}px`;
  canvas.style.height = `${cHeight}px`;

  const ctx = canvas.getContext('2d');
  if (ctx) {
    ctx.scale(dpr, dpr);
  }
  
  drawBarChart();
};

onMounted(() => {
  resizeCanvas();
  
  const container = chartCanvas.value?.parentElement;
  if (container) {
    resizeObserver = new ResizeObserver(() => {
      resizeCanvas();
    });
    resizeObserver.observe(container);
  }
  
  runEntryAnimation();
});

onUnmounted(() => {
  if (resizeObserver) {
    resizeObserver.disconnect();
  }
  if (animationFrameId) {
    cancelAnimationFrame(animationFrameId);
  }
});

watch(() => props.data, () => {
  runEntryAnimation();
}, { deep: true });
</script>

<style scoped>
.bar-chart-wrapper {
  width: 100%;
}

.canvas-container {
  position: relative;
  width: 100%;
}

.bar-chart-canvas {
  cursor: pointer;
  width: 100%;
}

.chart-tooltip {
  position: absolute;
  background: rgba(15, 23, 42, 0.95);
  backdrop-filter: blur(4px);
  color: #ffffff;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  border: 1.5px solid transparent;
  pointer-events: none;
  font-size: 0.75rem;
  font-family: 'Inter', system-ui, sans-serif;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 10;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  transition: left 0.08s ease, top 0.08s ease;
}

.tooltip-header {
  font-weight: 600;
  color: rgba(255, 255, 255, 0.9);
}

.tooltip-body {
  display: flex;
  gap: 0.4rem;
  align-items: center;
}

.tooltip-value {
  font-size: 0.95rem;
  font-weight: 700;
}
</style>
