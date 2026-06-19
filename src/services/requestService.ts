import api from './api';
import type { DataRequest } from '../types';

export interface GetRequestsParams {
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

export interface GetRequestsResponse {
  data: DataRequest[];
  total: number;
  page: number;
  limit: number;
  totalPages: number;
}

export interface DataRequestPayload {
  requestId?: number;
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
  getRequests(params: GetRequestsParams) {
    return api.get<GetRequestsResponse>('/requests.php', { params });
  },

  createRequest(data: Omit<DataRequestPayload, 'requestId'>) {
    return api.post<{ message: string; requestId: number }>('/requests.php', data);
  },

  updateRequest(data: DataRequestPayload) {
    return api.put<{ message: string }>('/requests.php', data);
  },

  deleteRequest(requestId: number) {
    return api.delete<{ message: string }>(`/requests.php?requestId=${requestId}`);
  }
};
