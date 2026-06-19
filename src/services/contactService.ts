import api from './api';
import type { Contact } from '../types';

export interface GetContactsParams {
  search?: string;
  sortBy?: string;
  sortOrder?: 'asc' | 'desc';
  page?: number;
  limit?: number;
}

export interface GetContactsResponse {
  data: Contact[];
  total: number;
  page: number;
  limit: number;
  totalPages: number;
}

export interface ContactPayload {
  cId?: number;
  cName: string;
  emailId: string;
  orgCode: string;
}

export default {
  getContacts(params: GetContactsParams) {
    return api.get<GetContactsResponse>('/contacts.php', { params });
  },

  createContact(data: Omit<ContactPayload, 'cId'>) {
    return api.post<Contact>('/contacts.php', data);
  },

  updateContact(data: ContactPayload) {
    return api.put<{ message: string }>('/contacts.php', data);
  },

  deleteContact(cId: number) {
    return api.delete<{ message: string }>(`/contacts.php?cId=${cId}`);
  }
};
