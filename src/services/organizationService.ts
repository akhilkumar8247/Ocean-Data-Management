import api from './api';
import type { Organization } from '../types';

export interface GetOrganizationsParams {
  search?: string;
  sortBy?: string;
  sortOrder?: 'asc' | 'desc';
  page?: number;
  limit?: number;
}

export interface GetOrganizationsResponse {
  data: Organization[];
  total: number;
  page: number;
  limit: number;
  totalPages: number;
}

export interface OrganizationPayload {
  oldOrgCode?: string;
  orgCode: string;
  orgName: string;
  address: string;
  countryCode: number;
}

export default {
  getOrganizations(params: GetOrganizationsParams) {
    return api.get<GetOrganizationsResponse>('/organizations.php', { params });
  },

  createOrganization(data: OrganizationPayload) {
    return api.post<Organization>('/organizations.php', data);
  },

  updateOrganization(data: OrganizationPayload) {
    return api.put<{ message: string }>('/organizations.php', data);
  },

  deleteOrganization(orgCode: string) {
    return api.delete<{ message: string }>(`/organizations.php?orgCode=${orgCode}`);
  }
};
