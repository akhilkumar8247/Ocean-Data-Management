<template>
  <div class="line-chart-wrapper" ref="chartWrapper">
    <div class="line-chart-controls">
      <div class="toggle-group">
        <button 
          class="toggle-btn" 
          :class="{ active: viewMode === 'monthly' }" 
          @click="setViewMode('monthly')"
        >Monthly</button>
        <button 
          class="toggle-btn" 
          :class="{ active: viewMode === 'yearly' }" 
          @click="setViewMode('yearly')"
        >Yearly</button>
      </div>
    </div>
    <div class="canvas-container">
      <canvas 
        ref="chartCanvas" 
        class="line-chart-canvas"
        @mousemove="handleMouseMove"
        @mouseleave="handleMouseLeave"
      ></canvas>

      <!-- Unified Tooltip -->
      <div 
        v-if="tooltip.visible" 
        class="chart-tooltip" 
        :style="{ 
          left: `${tooltip.x}px`, 
          top: `${tooltip.y}px`,
          transform: tooltip.alignLeft ? 'translateX(-100%)' : 'none'
        }"
      >
        <div class="tooltip-header">{{ tooltip.month }}</div>
        <div class="tooltip-body">
          <div class="tooltip-row">
            <span class="tooltip-dot requests-dot"></span>
            <span class="tooltip-label">Requests:</span>
            <span class="tooltip-val">{{ tooltip.requests }}</span>
          </div>
          <div class="tooltip-row">
            <span class="tooltip-dot receptions-dot"></span>
            <span class="tooltip-label">Receptions:</span>
            <span class="tooltip-val">{{ tooltip.receptions }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch, onUnmounted, computed } from 'vue';

interface MonthlyDataItem {
  month: string;
  count: number;
}

interface Props {
  requests: MonthlyDataItem[];
  receptions: MonthlyDataItem[];
}

const props = defineProps<Props>();

const chartCanvas = ref<HTMLCanvasElement | null>(null);
const hoveredMonthIndex = ref<number | null>(null);
const animationProgress = ref(0);
let animationFrameId: number | null = null;

// Store CSS-pixel dimensions for consistent draw/hover logic
const cssWidth = ref(300);
const cssHeight = ref(320);

const tooltip = reactive({
  visible: false,
  x: 0,
  y: 0,
  alignLeft: false,
  month: '',
  requests: 0,
  receptions: 0
});

// View mode toggle
const viewMode = ref<'monthly' | 'yearly'>('monthly');

const setViewMode = (mode: 'monthly' | 'yearly') => {
  viewMode.value = mode;
};

// Format month label "YYYY-MM" to readable "MMM YY" or year "YYYY"
const formatMonth = (monthStr: string): string => {
  if (!monthStr) return '';
  if (monthStr.length === 4) {
    return monthStr; // Year only (e.g., "2026")
  }
  const [year, month] = monthStr.split('-');
  const date = new Date(parseInt(year), parseInt(month) - 1, 1);
  return date.toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
};

// Aggregate data by year from monthly data
const yearlyData = computed(() => {
  const reqByYear = new Map<string, number>();
  const recByYear = new Map<string, number>();
  
  props.requests.forEach(d => {
    const year = d.month.substring(0, 4);
    reqByYear.set(year, (reqByYear.get(year) || 0) + d.count);
  });
  props.receptions.forEach(d => {
    const year = d.month.substring(0, 4);
    recByYear.set(year, (recByYear.get(year) || 0) + d.count);
  });
  
  const allYears = new Set([...reqByYear.keys(), ...recByYear.keys()]);
  const sortedYears = Array.from(allYears).sort();
  
  return sortedYears.map(year => ({
    month: year,
    formattedMonth: year,
    requests: reqByYear.get(year) || 0,
    receptions: recByYear.get(year) || 0
  }));
});

// Merge and align dates from both datasets
const chartData = computed(() => {
  if (viewMode.value === 'yearly') {
    return yearlyData.value;
  }
  
  const allMonthsSet = new Set<string>();
  props.requests.forEach(d => allMonthsSet.add(d.month));
  props.receptions.forEach(d => allMonthsSet.add(d.month));
  
  const sortedMonths = Array.from(allMonthsSet).sort();
  
  // limit to last 12 months for better display if list is huge
  const displayMonths = sortedMonths.slice(-12);

  const reqMap = new Map(props.requests.map(d => [d.month, d.count]));
  const recMap = new Map(props.receptions.map(d => [d.month, d.count]));

  return displayMonths.map(month => ({
    month,
    formattedMonth: formatMonth(month),
    requests: reqMap.get(month) || 0,
    receptions: recMap.get(month) || 0
  }));
});

const drawLineChart = () => {
  const canvas = chartCanvas.value;
  if (!canvas || chartData.value.length === 0) return;

  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  // Use CSS-pixel dimensions (ctx is already scaled by DPR)
  const width = cssWidth.value;
  const height = cssHeight.value;
  const paddingLeft = 50;
  const paddingRight = 30;
  const paddingTop = 45;
  const paddingBottom = 50;

  const chartWidth = width - paddingLeft - paddingRight;
  const chartHeight = height - paddingTop - paddingBottom;

  // Clear canvas
  ctx.clearRect(0, 0, width, height);

  // Find max value
  const maxRequests = Math.max(...chartData.value.map(d => d.requests), 0);
  const maxReceptions = Math.max(...chartData.value.map(d => d.receptions), 0);
  const maxVal = Math.max(maxRequests, maxReceptions, 1);

  // Draw grid lines
  ctx.strokeStyle = '#e2e8f0';
  ctx.lineWidth = 1;
  ctx.font = '500 10px Inter, system-ui, sans-serif';
  ctx.fillStyle = '#64748b';
  ctx.textAlign = 'right';
  ctx.textBaseline = 'middle';

  const gridTicks = 4;
  for (let i = 0; i <= gridTicks; i++) {
    const val = Math.round((i / gridTicks) * maxVal);
    const y = height - paddingBottom - (i / gridTicks) * chartHeight;

    ctx.beginPath();
    ctx.moveTo(paddingLeft, y);
    ctx.lineTo(width - paddingRight, y);
    ctx.stroke();

    ctx.fillText(val.toString(), paddingLeft - 10, y);
  }

  const pointsCount = chartData.value.length;
  const xSpacing = pointsCount > 1 ? chartWidth / (pointsCount - 1) : chartWidth;

  // Draw vertical indicator line if hovered
  if (hoveredMonthIndex.value !== null && hoveredMonthIndex.value >= 0 && hoveredMonthIndex.value < pointsCount) {
    const x = paddingLeft + hoveredMonthIndex.value * xSpacing;
    ctx.strokeStyle = '#94a3b8';
    ctx.lineWidth = 1;
    ctx.setLineDash([4, 4]);
    ctx.beginPath();
    ctx.moveTo(x, paddingTop);
    ctx.lineTo(x, height - paddingBottom);
    ctx.stroke();
    ctx.setLineDash([]); // Reset line dash
  }

  // Draw lines function
  const drawLine = (
    key: 'requests' | 'receptions', 
    lineColor: string, 
    gradStart: string, 
    gradEnd: string
  ) => {
    const points: { x: number; y: number }[] = [];
    
    chartData.value.forEach((item, idx) => {
      const x = paddingLeft + idx * xSpacing;
      const y = height - paddingBottom - (item[key] / maxVal) * chartHeight * animationProgress.value;
      points.push({ x, y });
    });

    if (points.length === 0) return;

    // Draw shaded area under line first
    const areaGrad = ctx.createLinearGradient(0, paddingTop, 0, height - paddingBottom);
    areaGrad.addColorStop(0, gradStart);
    areaGrad.addColorStop(1, gradEnd);

    ctx.beginPath();
    ctx.moveTo(points[0].x, height - paddingBottom);
    points.forEach(p => ctx.lineTo(p.x, p.y));
    ctx.lineTo(points[points.length - 1].x, height - paddingBottom);
    ctx.closePath();
    ctx.fillStyle = areaGrad;
    ctx.fill();

    // Draw line
    ctx.beginPath();
    ctx.moveTo(points[0].x, points[0].y);
    for (let i = 0; i < points.length - 1; i++) {
      const xc = (points[i].x + points[i + 1].x) / 2;
      const yc = (points[i].y + points[i + 1].y) / 2;
      ctx.quadraticCurveTo(points[i].x, points[i].y, xc, yc);
    }
    ctx.lineTo(points[points.length - 1].x, points[points.length - 1].y);
    
    ctx.strokeStyle = lineColor;
    ctx.lineWidth = 3;
    ctx.stroke();

    // Draw dots at points
    if (animationProgress.value === 1) {
      points.forEach((p, idx) => {
        const isHovered = hoveredMonthIndex.value === idx;
        ctx.beginPath();
        ctx.arc(p.x, p.y, isHovered ? 6 : 4, 0, 2 * Math.PI);
        ctx.fillStyle = lineColor;
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 2;
        ctx.fill();
        ctx.stroke();
      });
    }
  };

  // Draw Requests Line (Blue theme)
  drawLine('requests', '#2563eb', 'rgba(37, 99, 235, 0.12)', 'rgba(37, 99, 235, 0)');

  // Draw Receptions Line (Emerald theme)
  drawLine('receptions', '#10b981', 'rgba(16, 185, 129, 0.12)', 'rgba(16, 185, 129, 0)');

  // Draw X-axis labels
  ctx.textAlign = 'center';
  ctx.fillStyle = '#64748b';
  ctx.font = '500 10px Inter, system-ui, sans-serif';
  chartData.value.forEach((item, idx) => {
    const x = paddingLeft + idx * xSpacing;
    ctx.fillText(item.formattedMonth, x, height - paddingBottom + 20);
  });
};

const handleMouseMove = (event: MouseEvent) => {
  const canvas = chartCanvas.value;
  if (!canvas || chartData.value.length === 0 || animationProgress.value < 1) return;

  const rect = canvas.getBoundingClientRect();
  const x = event.clientX - rect.left;
  const y = event.clientY - rect.top;

  // Use CSS-pixel dimensions to match the drawn coordinates
  const width = cssWidth.value;
  const paddingLeft = 50;
  const paddingRight = 30;
  const chartWidth = width - paddingLeft - paddingRight;

  const pointsCount = chartData.value.length;
  const xSpacing = pointsCount > 1 ? chartWidth / (pointsCount - 1) : chartWidth;

  // Find nearest index
  const relativeX = x - paddingLeft;
  let nearestIdx = Math.round(relativeX / xSpacing);
  nearestIdx = Math.max(0, Math.min(pointsCount - 1, nearestIdx));

  const nearestX = paddingLeft + nearestIdx * xSpacing;

  // Only trigger tooltip if within acceptable mouse distance to point column
  if (Math.abs(x - nearestX) < xSpacing / 2) {
    const lastHovered = hoveredMonthIndex.value;
    hoveredMonthIndex.value = nearestIdx;

    if (lastHovered !== nearestIdx) {
      drawLineChart();
    }

    const item = chartData.value[nearestIdx];
    tooltip.visible = true;
    const alignLeft = nearestX > width / 2;
    tooltip.alignLeft = alignLeft;
    tooltip.x = alignLeft ? nearestX - 15 : nearestX + 15;
    tooltip.y = Math.max(5, y - 40);
    tooltip.month = formatFullMonth(item.month);
    tooltip.requests = item.requests;
    tooltip.receptions = item.receptions;
  } else {
    handleMouseLeave();
  }
};

const handleMouseLeave = () => {
  if (hoveredMonthIndex.value !== null) {
    hoveredMonthIndex.value = null;
    drawLineChart();
  }
  tooltip.visible = false;
};

const formatFullMonth = (monthStr: string): string => {
  if (!monthStr) return '';
  if (monthStr.length === 4) {
    return `Year ${monthStr}`;
  }
  const [year, month] = monthStr.split('-');
  const date = new Date(parseInt(year), parseInt(month) - 1, 1);
  return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
};

const runEntryAnimation = () => {
  const duration = 900; // ms
  const startTime = performance.now();

  const animate = (time: number) => {
    const elapsed = time - startTime;
    const progress = Math.min(elapsed / duration, 1);
    
    // Ease-out cubic formula
    animationProgress.value = 1 - Math.pow(1 - progress, 3);
    drawLineChart();

    if (progress < 1) {
      animationFrameId = requestAnimationFrame(animate);
    } else {
      animationProgress.value = 1;
      drawLineChart();
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
  const cHeight = 320;
  
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
  
  drawLineChart();
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

watch([() => props.requests, () => props.receptions], () => {
  runEntryAnimation();
}, { deep: true });

watch(viewMode, () => {
  runEntryAnimation();
});
</script>

<style scoped>
.line-chart-wrapper {
  width: 100%;
}

.canvas-container {
  position: relative;
  width: 100%;
}

.line-chart-canvas {
  cursor: crosshair;
  width: 100%;
}

.chart-tooltip {
  position: absolute;
  background: rgba(15, 23, 42, 0.95);
  backdrop-filter: blur(4px);
  color: #ffffff;
  padding: 0.6rem 0.8rem;
  border-radius: 8px;
  border: 1px solid #334155;
  pointer-events: none;
  font-size: 0.75rem;
  font-family: 'Inter', system-ui, sans-serif;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 10;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  transition: left 0.08s ease, top 0.08s ease;
}

.tooltip-header {
  font-weight: 600;
  color: rgba(255, 255, 255, 0.9);
  border-bottom: 1px solid #334155;
  padding-bottom: 0.25rem;
  margin-bottom: 0.15rem;
}

.tooltip-body {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.tooltip-row {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.tooltip-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}

.requests-dot {
  background-color: #2563eb;
}

.receptions-dot {
  background-color: #10b981;
}

.tooltip-label {
  color: rgba(255, 255, 255, 0.7);
  min-width: 70px;
}

.tooltip-val {
  font-weight: 700;
}

.line-chart-controls {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 0.75rem;
}

.toggle-group {
  display: inline-flex;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
}

.toggle-btn {
  padding: 0.35rem 0.85rem;
  font-size: 0.75rem;
  font-weight: 600;
  font-family: 'Inter', system-ui, sans-serif;
  border: none;
  background: transparent;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s ease;
  letter-spacing: 0.01em;
}

.toggle-btn:hover {
  color: #334155;
  background: #f1f5f9;
}

.toggle-btn.active {
  background: #2563eb;
  color: #ffffff;
  box-shadow: 0 1px 4px rgba(37, 99, 235, 0.3);
}
</style>
