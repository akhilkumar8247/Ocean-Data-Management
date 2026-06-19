import api from './api';

interface RequestsByStatus {
  statusCode: string;
  statusName: string;
  count: number;
}

interface ReceptionsByStatus {
  statusCode: string;
  statusName: string;
  count: number;
}

interface CountByCategory {
  categoryCode: string;
  categoryName: string;
  count: number;
}

interface ProgramCount {
  prgmCode: string;
  prgmName: string;
  count: number;
}

interface MonthlyCount {
  month: string;
  count: number;
}

interface DashboardStats {
  requestsByStatus: RequestsByStatus[];
  receptionsByStatus: ReceptionsByStatus[];
  countsByCategory: CountByCategory[];
  programCounts: ProgramCount[];
  requestsByMonth: MonthlyCount[];
  receptionsByMonth: MonthlyCount[];
}

const dashboardService = {
  getStatistics: async () => {
    return api.get<DashboardStats>('/stats.php');
  }
};

export default dashboardService;
