import api from './api';
import type { DataReception } from '../types';

export interface GetReceptionsParams {
  search?: string;
  categoryCode?: string | '';
  statusCode?: string | '';
  dateStart?: string;
  dateEnd?: string;
  sortBy?: string;
  sortOrder?: 'asc' | 'desc';
  page?: number;
  limit?: number;
}

export interface GetReceptionsResponse {
  data: DataReception[];
  total: number;
  page: number;
  limit: number;
  totalPages: number;
}

export interface DataReceptionPayload {
  receptionId?: number;
  cId: number;
  dateReceived: string;
  dateProvided: string | null;
  categoryCode: string;
  details: string | null;
  size?: number | null;
  cost?: number | null;
  statusCode: string;
  remarks: string;
  programs: string[];
}

export default {
  getReceptions(params: GetReceptionsParams) {
    return api.get<GetReceptionsResponse>('/receptions.php', { params });
  },

  createReception(data: Omit<DataReceptionPayload, 'receptionId'>) {
    return api.post<{ message: string; receptionId: number }>('/receptions.php', data);
  },

  updateReception(data: DataReceptionPayload) {
    return api.put<{ message: string }>('/receptions.php', data);
  },

  deleteReception(receptionId: number) {
    return api.delete<{ message: string }>(`/receptions.php?receptionId=${receptionId}`);
  }
};
