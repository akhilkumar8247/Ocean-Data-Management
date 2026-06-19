<template>
  <div class="pie-chart-wrapper" ref="chartWrapper">
    <div class="canvas-container">
      <canvas 
        ref="chartCanvas" 
        class="pie-chart-canvas"
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
          <span class="tooltip-percentage">({{ tooltip.percentage }}%)</span>
        </div>
      </div>
    </div>
    
    <div class="pie-legend">
      <div 
        v-for="(item, idx) in data" 
        :key="`legend-${idx}`" 
        class="legend-item"
        :class="{ 'dimmed': hoveredIndex !== null && hoveredIndex !== idx }"
        @mouseenter="hoveredIndex = idx; drawPieChart()"
        @mouseleave="hoveredIndex = null; drawPieChart()"
      >
        <span class="legend-color" :style="{ background: getGradientStyle(idx) }"></span>
        <span class="legend-text">{{ item.label }} ({{ item.value }})</span>
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
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Chart'
});

const chartCanvas = ref<HTMLCanvasElement | null>(null);
const hoveredIndex = ref<number | null>(null);
const animationProgress = ref(0);
let animationFrameId: number | null = null;

// Store CSS-pixel dimensions for consistent draw/hover logic
const cssWidth = ref(300);
const cssHeight = ref(300);

const tooltip = reactive({
  visible: false,
  x: 0,
  y: 0,
  label: '',
  value: 0,
  percentage: '',
  color: ''
});

// Vibrant and modern colors (HSL)
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

const getGradientStyle = (idx: number) => {
  const c = colors[idx % colors.length];
  return `linear-gradient(135deg, ${c.start}, ${c.end})`;
};

const drawPieChart = () => {
  const canvas = chartCanvas.value;
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  // Use CSS-pixel dimensions (ctx is already scaled by DPR)
  const width = cssWidth.value;
  const height = cssHeight.value;
  const centerX = width / 2;
  const centerY = height / 2;
  const radius = Math.min(width, height) / 2 - 30;

  // Clear canvas
  ctx.clearRect(0, 0, width, height);

  // Calculate total
  const total = props.data.reduce((sum, item) => sum + item.value, 0) || 1;
  let currentAngle = -Math.PI / 2;

  // Draw slices
  props.data.forEach((item, idx) => {
    const sliceAngle = (item.value / total) * 2 * Math.PI * animationProgress.value;
    const isHovered = hoveredIndex.value === idx;
    
    // Explode effect for hovered slice
    let offsetColX = 0;
    let offsetColY = 0;
    if (isHovered && animationProgress.value === 1) {
      const midAngle = currentAngle + sliceAngle / 2;
      offsetColX = Math.cos(midAngle) * 10;
      offsetColY = Math.sin(midAngle) * 10;
    }

    ctx.save();
    ctx.translate(offsetColX, offsetColY);

    // Draw slice shadow
    if (isHovered) {
      ctx.shadowBlur = 15;
      ctx.shadowColor = 'rgba(0, 0, 0, 0.2)';
    }

    // Create radial gradient for 3D/glass glow effect
    const grad = ctx.createRadialGradient(
      centerX, centerY, radius * 0.2,
      centerX, centerY, radius
    );
    grad.addColorStop(0, colors[idx % colors.length].start);
    grad.addColorStop(1, colors[idx % colors.length].end);

    ctx.beginPath();
    ctx.moveTo(centerX, centerY);
    ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + sliceAngle);
    ctx.closePath();
    ctx.fillStyle = grad;
    ctx.fill();

    // Draw border
    ctx.strokeStyle = '#ffffff';
    ctx.lineWidth = isHovered ? 3 : 2;
    ctx.stroke();

    ctx.restore();

    currentAngle += sliceAngle;
  });

  // Draw center cutout for sleek donut chart aesthetic
  ctx.save();
  ctx.beginPath();
  ctx.arc(centerX, centerY, radius * 0.55, 0, 2 * Math.PI);
  ctx.fillStyle = '#ffffff';
  ctx.shadowBlur = 8;
  ctx.shadowColor = 'rgba(15, 23, 42, 0.08)';
  ctx.fill();
  ctx.restore();

  // Draw total count inside center of donut
  if (animationProgress.value === 1) {
    const totalCount = props.data.reduce((sum, item) => sum + item.value, 0);
    ctx.fillStyle = '#0f172a';
    ctx.font = 'bold 20px Inter, system-ui, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(totalCount.toString(), centerX, centerY - 8);
    
    ctx.fillStyle = '#64748b';
    ctx.font = '500 10px Inter, system-ui, sans-serif';
    ctx.fillText('TOTAL', centerX, centerY + 12);
  }
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
  const centerX = width / 2;
  const centerY = height / 2;
  const radius = Math.min(width, height) / 2 - 30;

  // Calculate distance from center
  const dx = x - centerX;
  const dy = y - centerY;
  const distance = Math.sqrt(dx * dx + dy * dy);

  // Verify click/hover is within pie slice bounds
  if (distance > radius || distance < radius * 0.55) {
    handleMouseLeave();
    return;
  }

  // Calculate angle relative to starting point (-Math.PI/2)
  let angle = Math.atan2(dy, dx);
  if (angle < -Math.PI / 2) {
    angle += 2 * Math.PI;
  }
  const relativeAngle = angle - (-Math.PI / 2);

  const total = props.data.reduce((sum, item) => sum + item.value, 0) || 1;
  let currentAngle = 0;
  let matchedIndex = -1;

  for (let i = 0; i < props.data.length; i++) {
    const sliceAngle = (props.data[i].value / total) * 2 * Math.PI;
    if (relativeAngle >= currentAngle && relativeAngle <= currentAngle + sliceAngle) {
      matchedIndex = i;
      break;
    }
    currentAngle += sliceAngle;
  }

  if (matchedIndex !== -1) {
    const lastHovered = hoveredIndex.value;
    hoveredIndex.value = matchedIndex;

    if (lastHovered !== matchedIndex) {
      drawPieChart();
    }

    // Update Tooltip
    const item = props.data[matchedIndex];
    tooltip.visible = true;
    tooltip.x = x + 15;
    tooltip.y = y - 30;
    tooltip.label = item.label;
    tooltip.value = item.value;
    tooltip.percentage = ((item.value / total) * 100).toFixed(1);
    tooltip.color = colors[matchedIndex % colors.length].start;
  } else {
    handleMouseLeave();
  }
};

const handleMouseLeave = () => {
  if (hoveredIndex.value !== null) {
    hoveredIndex.value = null;
    drawPieChart();
  }
  tooltip.visible = false;
};

const runEntryAnimation = () => {
  const duration = 750; // ms
  const startTime = performance.now();

  const animate = (time: number) => {
    const elapsed = time - startTime;
    const progress = Math.min(elapsed / duration, 1);
    
    // Ease-out cubic formula
    animationProgress.value = 1 - Math.pow(1 - progress, 3);
    drawPieChart();

    if (progress < 1) {
      animationFrameId = requestAnimationFrame(animate);
    } else {
      animationProgress.value = 1;
      drawPieChart();
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
  const containerW = container.clientWidth || 300;
  const size = Math.max(180, Math.min(containerW, 420));
  const h = Math.min(size, 320);
  
  // Store CSS-pixel dimensions
  cssWidth.value = size;
  cssHeight.value = h;
  
  // Set canvas buffer at DPR scale for sharpness
  canvas.width = size * dpr;
  canvas.height = h * dpr;
  canvas.style.width = `${size}px`;
  canvas.style.height = `${h}px`;

  const ctx = canvas.getContext('2d');
  if (ctx) {
    ctx.scale(dpr, dpr);
  }
  
  drawPieChart();
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
.pie-chart-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.5rem;
  width: 100%;
}

.canvas-container {
  position: relative;
  width: 100%;
  display: flex;
  justify-content: center;
}

.pie-chart-canvas {
  cursor: pointer;
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
  transition: left 0.1s ease, top 0.1s ease;
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
  font-size: 0.9rem;
  font-weight: 700;
}

.tooltip-percentage {
  color: rgba(255, 255, 255, 0.6);
}

.pie-legend {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem 1.25rem;
  justify-content: center;
  width: 100%;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.825rem;
  font-weight: 500;
  cursor: pointer;
  transition: opacity 0.2s ease;
}

.legend-item.dimmed {
  opacity: 0.35;
}

.legend-color {
  width: 14px;
  height: 14px;
  border-radius: 4px;
  flex-shrink: 0;
}

.legend-text {
  color: var(--text-secondary);
}
</style>
