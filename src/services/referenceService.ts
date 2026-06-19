import api from './api';
import type { ReferenceData, Country, Organization, Contact } from '../types';

export default {
  // Fetch all dropdown resources
  getAllReferences() {
    return api.get<ReferenceData>('/reference.php');
  },

  // Cascade creation endpoints
  createCountry(data: Country) {
    return api.post<Country>('/countries.php', data);
  },

  createOrganization(data: Omit<Organization, 'countryName'>) {
    return api.post<Organization>('/organizations.php', data);
  },

  createContact(data: Omit<Contact, 'cId' | 'orgName' | 'countryName'>) {
    return api.post<Contact>('/contacts.php', data);
  },

  updateContact(data: { cId: number; emailId: string; orgCode: string }) {
    return api.put('/contacts.php', data);
  }
};
