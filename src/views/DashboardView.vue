<template>
  <div class="dashboard-container">
    <!-- Page Header -->
    <div class="dashboard-header">
      <h1>Dashboard Overview</h1>
      <p class="subtitle">System Analytics & Key Performance Indicators</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Loading analytics data...</p>
    </div>

    <!-- Main Dashboard Content -->
    <div v-else class="dashboard-content">
      <!-- Summary Cards Row -->
      <div class="cards-grid">
        <!-- Total Requests Card -->
        <div class="stat-card requests-theme">
          <div class="card-header">
            <FileTextIcon :size="20" />
            <h3>Total Requests</h3>
          </div>
          <div class="card-value">{{ stats.totalRequests }}</div>
          <p class="card-label">Data Requests Submitted</p>
        </div>

        <!-- Total Receptions Card -->
        <div class="stat-card receptions-theme">
          <div class="card-header">
            <FileSpreadsheetIcon :size="20" />
            <h3>Total Receptions</h3>
          </div>
          <div class="card-value">{{ stats.totalReceptions }}</div>
          <p class="card-label">Data Receptions Logged</p>
        </div>

        <!-- Total Contacts Card -->
        <div class="stat-card contacts-theme">
          <div class="card-header">
            <UsersIcon :size="20" />
            <h3>Total Contacts</h3>
          </div>
          <div class="card-value">{{ stats.totalContacts }}</div>
          <p class="card-label">Registered Contacts</p>
        </div>

        <!-- Total Organizations Card -->
        <div class="stat-card organizations-theme">
          <div class="card-header">
            <BuildingIcon :size="20" />
            <h3>Total Organizations</h3>
          </div>
          <div class="card-value">{{ stats.totalOrganizations }}</div>
          <p class="card-label">Registered Organizations</p>
        </div>
      </div>

      <!-- Trend Chart (Full Width) -->
      <div class="chart-card full-width">
        <div class="chart-header-row">
          <h3>Request & Reception Trends</h3>
          <div class="chart-legend-indicator">
            <span class="legend-indicator-item"><span class="dot blue"></span>Requests</span>
            <span class="legend-indicator-item"><span class="dot emerald"></span>Receptions</span>
          </div>
        </div>
        <LineChart :requests="requestsByMonth" :receptions="receptionsByMonth" />
      </div>

      <!-- Main Visualizations Grid -->
      <div class="analytics-row-grid">
        <!-- Request Status Pie Chart -->
        <div v-if="stats.statusBreakdown.requests.length > 0" class="chart-card">
          <h3>Request Status Distribution</h3>
          <PieChart :data="requestChartData" />
        </div>

        <!-- Reception Status Pie Chart -->
        <div v-if="stats.statusBreakdown.receptions.length > 0" class="chart-card">
          <h3>Reception Status Distribution</h3>
          <PieChart :data="receptionChartData" />
        </div>

        <!-- Program Distribution Progress Bars -->
        <div class="chart-card">
          <h3>Program Distribution</h3>
          <ProgramDistribution :programs="programCounts" />
        </div>
      </div>

      <!-- Category Distribution Bar Chart -->
      <div class="chart-card full-width">
        <h3>Data Distribution by Category</h3>
        <BarChart :data="categoryChartData" :maxValue="maxCategoryValue" />
      </div>

      <!-- Reference Data Summary Cards Row -->
      <div class="reference-cards-grid">
        <!-- Categories Card -->
        <div class="info-card">
          <div class="info-card-header">
            <TagsIcon :size="18" />
            <h3>Categories Summary</h3>
          </div>
          <div class="info-card-content">
            <p class="count">{{ stats.totalCategories }}</p>
            <ul v-if="refData.categories.length > 0" class="item-list">
              <li v-for="cat in refData.categories.slice(0, 4)" :key="cat.categoryCode">
                {{ cat.categoryName }}
              </li>
              <li v-if="refData.categories.length > 4" class="more-items-indicator">
                + {{ refData.categories.length - 4 }} more categories
              </li>
            </ul>
          </div>
        </div>

        <!-- Programs Card -->
        <div class="info-card">
          <div class="info-card-header">
            <CheckCircleIcon :size="18" />
            <h3>Active Programs</h3>
          </div>
          <div class="info-card-content">
            <p class="count">{{ stats.totalPrograms }}</p>
            <ul v-if="refData.programs.length > 0" class="item-list">
              <li v-for="prog in refData.programs.slice(0, 4)" :key="prog.prgmCode">
                {{ prog.prgmName }}
              </li>
              <li v-if="refData.programs.length > 4" class="more-items-indicator">
                + {{ refData.programs.length - 4 }} more programs
              </li>
            </ul>
          </div>
        </div>

        <!-- Countries Card -->
        <div class="info-card">
          <div class="info-card-header">
            <GlobeIcon :size="18" />
            <h3>Represented Countries</h3>
          </div>
          <div class="info-card-content">
            <p class="count">{{ stats.totalCountries }}</p>
            <p class="text-muted">International reference countries used for organization profiling and tracking.</p>
          </div>
        </div>
      </div>

      <!-- Last Updated Info -->
      <div class="dashboard-footer-info">
        <p>Last updated: {{ lastUpdated }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue';
import {
  FileText as FileTextIcon,
  FileSpreadsheet as FileSpreadsheetIcon,
  Users as UsersIcon,
  Building as BuildingIcon,
  Tags as TagsIcon,
  CheckCircle as CheckCircleIcon,
  Globe as GlobeIcon
} from '@lucide/vue';
import PieChart from '../components/PieChart.vue';
import BarChart from '../components/BarChart.vue';
import LineChart from '../components/LineChart.vue';
import ProgramDistribution from '../components/ProgramDistribution.vue';
import referenceService from '../services/referenceService';
import requestService from '../services/requestService';
import receptionService from '../services/receptionService';
import dashboardService from '../services/dashboardService';
import type { ReferenceData } from '../types';

const loading = ref(true);
const lastUpdated = ref('');

const refData = ref<ReferenceData>({
  countries: [],
  organizations: [],
  contacts: [],
  categories: [],
  programs: [],
  statusReq: [],
  statusRes: []
});

const stats = reactive({
  totalRequests: 0,
  totalReceptions: 0,
  totalContacts: 0,
  totalOrganizations: 0,
  totalCategories: 0,
  totalPrograms: 0,
  totalCountries: 0,
  statusBreakdown: {
    requests: [] as Array<{ code: string; name: string; count: number }>,
    receptions: [] as Array<{ code: string; name: string; count: number }>
  }
});

const requestCounts = ref<Map<string, number>>(new Map());
const receptionCounts = ref<Map<string, number>>(new Map());
const categoryCounts = ref<Map<string, number>>(new Map());
const requestsByMonth = ref<Array<{ month: string; count: number }>>([]);
const receptionsByMonth = ref<Array<{ month: string; count: number }>>([]);
const programCounts = ref<Array<{ prgmCode: string; prgmName: string; count: number }>>([]);

const maxCategoryValue = computed(() => {
  const values = Array.from(categoryCounts.value.values());
  return Math.max(...values, 1);
});

const requestChartData = computed(() =>
  stats.statusBreakdown.requests.map((status) => ({
    label: status.name,
    value: requestCounts.value.get(status.code) || 0
  }))
);

const receptionChartData = computed(() =>
  stats.statusBreakdown.receptions.map((status) => ({
    label: status.name,
    value: receptionCounts.value.get(status.code) || 0
  }))
);

const categoryChartData = computed(() =>
  refData.value.categories.map((cat) => ({
    label: cat.categoryName,
    value: categoryCounts.value.get(cat.categoryCode) || 0
  }))
);

const fetchDashboardData = async () => {
  loading.value = true;
  try {
    // Fetch reference data
    const refRes = await referenceService.getAllReferences();
    refData.value = refRes.data;

    // Fetch request statistics
    const reqRes = await requestService.getRequests({ page: 1, limit: 1 });
    stats.totalRequests = reqRes.data.total;

    // Fetch reception statistics
    const recRes = await receptionService.getReceptions({ page: 1, limit: 1 });
    stats.totalReceptions = recRes.data.total;

    // Calculate reference data counts
    stats.totalContacts = refData.value.contacts.length;
    stats.totalOrganizations = refData.value.organizations.length;
    stats.totalCategories = refData.value.categories.length;
    stats.totalPrograms = refData.value.programs.length;
    stats.totalCountries = refData.value.countries.length;

    // Build status breakdown templates
    stats.statusBreakdown.requests = refData.value.statusReq.map(s => ({
      code: s.statusCode,
      name: s.statusName,
      count: 0
    }));

    stats.statusBreakdown.receptions = refData.value.statusRes.map(s => ({
      code: s.statusCode,
      name: s.statusName,
      count: 0
    }));

    // Fetch dashboard statistics from API
    const statsRes = await dashboardService.getStatistics();
    const dashboardStats = statsRes.data;

    // Populate request counts by status
    requestCounts.value = new Map();
    (dashboardStats.requestsByStatus || []).forEach(item => {
      requestCounts.value.set(item.statusCode, parseInt(item.count as any));
    });

    // Populate reception counts by status
    receptionCounts.value = new Map();
    (dashboardStats.receptionsByStatus || []).forEach(item => {
      receptionCounts.value.set(item.statusCode, parseInt(item.count as any));
    });

    // Populate category counts
    categoryCounts.value = new Map();
    (dashboardStats.countsByCategory || []).forEach(item => {
      categoryCounts.value.set(item.categoryCode, parseInt(item.count as any));
    });

    // Populate monthly trends
    requestsByMonth.value = (dashboardStats.requestsByMonth || []).map((item: any) => ({
      month: item.month,
      count: parseInt(item.count)
    }));
    receptionsByMonth.value = (dashboardStats.receptionsByMonth || []).map((item: any) => ({
      month: item.month,
      count: parseInt(item.count)
    }));

    // Populate program counts
    programCounts.value = (dashboardStats.programCounts || []).map((item: any) => ({
      prgmCode: item.prgmCode,
      prgmName: item.prgmName,
      count: parseInt(item.count)
    }));

    // Update last updated timestamp
    const now = new Date();
    lastUpdated.value = now.toLocaleString();

  } catch (error: any) {
    console.error('Failed to fetch dashboard data:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchDashboardData();
});
</script>

<style scoped>
.dashboard-container {
  padding: 2rem;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  min-height: calc(100vh - 64px);
}

.dashboard-header {
  margin-bottom: 2rem;
}

.dashboard-header h1 {
  font-size: 1.75rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.35rem 0;
  letter-spacing: -0.02em;
}

.subtitle {
  font-size: 0.925rem;
  color: #64748b;
  margin: 0;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 400px;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3.5px solid #f1f5f9;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 1s cubic-bezier(0.55, 0.15, 0.45, 0.85) infinite;
  margin-bottom: 1.25rem;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.dashboard-content {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.25rem;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 18px -2px rgba(15, 23, 42, 0.04);
  transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  border: 1px solid rgba(241, 245, 249, 0.8);
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 24px -4px rgba(15, 23, 42, 0.08);
}

/* Custom visual accents for dashboard cards */
.requests-theme {
  border-top: 4px solid #2563eb;
}
.requests-theme svg {
  color: #2563eb;
}
.receptions-theme {
  border-top: 4px solid #10b981;
}
.receptions-theme svg {
  color: #10b981;
}
.contacts-theme {
  border-top: 4px solid #f59e0b;
}
.contacts-theme svg {
  color: #f59e0b;
}
.organizations-theme {
  border-top: 4px solid #8b5cf6;
}
.organizations-theme svg {
  color: #8b5cf6;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  margin-bottom: 0.85rem;
}

.card-header h3 {
  font-size: 0.825rem;
  font-weight: 600;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 0;
}

.card-header svg {
  flex-shrink: 0;
}

.card-value {
  font-size: 2.25rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 0.25rem;
  line-height: 1.1;
  letter-spacing: -0.03em;
}

.card-label {
  font-size: 0.775rem;
  color: #94a3b8;
  margin: 0;
  font-weight: 500;
}

.chart-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 18px -2px rgba(15, 23, 42, 0.04);
  border: 1px solid rgba(241, 245, 249, 0.8);
  min-width: 0;
  overflow: hidden;
}

.chart-card.full-width {
  width: 100%;
}

.chart-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.chart-legend-indicator {
  display: flex;
  gap: 1rem;
  font-size: 0.775rem;
  font-weight: 600;
  color: #64748b;
}

.legend-indicator-item {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.legend-indicator-item .dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.legend-indicator-item .dot.blue {
  background-color: #2563eb;
}

.legend-indicator-item .dot.emerald {
  background-color: #10b981;
}

.chart-card h3 {
  font-size: 0.95rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 1rem 0;
  letter-spacing: -0.01em;
}

.chart-header-row h3 {
  margin: 0;
}

.analytics-row-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.25rem;
}

.reference-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.25rem;
}

.info-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 18px -2px rgba(15, 23, 42, 0.04);
  border: 1px solid rgba(241, 245, 249, 0.8);
  min-width: 0;
}

.info-card-header {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #f1f5f9;
}

.info-card-header h3 {
  font-size: 0.875rem;
  font-weight: 600;
  color: #334155;
  margin: 0;
}

.info-card-header svg {
  color: #64748b;
  flex-shrink: 0;
}

.info-card-content .count {
  font-size: 1.75rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.85rem 0;
  letter-spacing: -0.02em;
}

.item-list {
  list-style: none;
  margin: 0;
  padding: 0;
  font-size: 0.8rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.item-list li {
  padding: 0.35rem 0.5rem;
  color: #475569;
  background-color: #f8fafc;
  border-radius: 6px;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.more-items-indicator {
  color: #94a3b8 !important;
  background-color: transparent !important;
  font-size: 0.75rem;
  font-style: italic;
  padding-left: 0.25rem !important;
}

.text-muted {
  color: #64748b;
  font-size: 0.8rem;
  line-height: 1.4;
  margin: 0;
}

.dashboard-footer-info {
  text-align: center;
  padding: 1rem 0;
  color: #94a3b8;
  font-size: 0.8rem;
  font-weight: 500;
}

/* Large tablets and narrow desktops */
@media (max-width: 1100px) {
  .analytics-row-grid {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 1024px) {
  .dashboard-container {
    padding: 1.5rem;
  }
}

@media (max-width: 768px) {
  .dashboard-container {
    padding: 1rem;
  }

  .dashboard-header h1 {
    font-size: 1.5rem;
  }

  .cards-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .analytics-row-grid,
  .reference-cards-grid {
    grid-template-columns: 1fr;
  }

  .card-value {
    font-size: 1.875rem;
  }

  .chart-card {
    padding: 1rem;
  }
}

@media (max-width: 480px) {
  .dashboard-container {
    padding: 0.75rem;
  }

  .cards-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .stat-card {
    padding: 1.25rem;
  }

  .card-value {
    font-size: 1.5rem;
  }

  .dashboard-header h1 {
    font-size: 1.25rem;
  }
}
</style>
