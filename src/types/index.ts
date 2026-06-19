export interface Country {
  countryCode: number;
  countryName: string;
}

export interface Organization {
  orgCode: string;
  orgName: string;
  address: string;
  countryCode: number;
  countryName?: string;
}

export interface Contact {
  cId: number;
  cName: string;
  emailId: string;
  orgCode: string;
  orgName?: string;
  countryName?: string;
}

export interface Category {
  categoryCode: string;
  categoryName: string;
}

export interface Program {
  prgmCode: string;
  prgmName: string;
}

export interface StatusReq {
  statusCode: string;
  statusName: string;
}

export interface StatusRes {
  statusCode: string;
  statusName: string;
}

export interface ProgramLink {
  prgmCode: string;
  prgmName: string;
}

export interface DataRequest {
  requestId?: number;
  cId: number;
  cName?: string;
  emailId?: string;
  orgName?: string;
  countryName?: string;
  dateReceived: string;
  dateProvided: string | null;
  categoryCode: string;
  categoryName?: string;
  details: string | null;
  size?: number | null;
  cost?: number | null;
  statusCode: string;
  statusName?: string;
  remarks: string;
  programs?: ProgramLink[];
  programCodes: string[];
}

export interface DataReception {
  receptionId?: number;
  cId: number;
  cName?: string;
  emailId?: string;
  orgName?: string;
  countryName?: string;
  dateReceived: string;
  dateProvided: string | null;
  categoryCode: string;
  categoryName?: string;
  details: string | null;
  size?: number | null;
  cost?: number | null;
  statusCode: string;
  statusName?: string;
  remarks: string;
  programs?: ProgramLink[];
  programCodes: string[];
}

export interface ReferenceData {
  countries: Country[];
  organizations: Organization[];
  contacts: Contact[];
  categories: Category[];
  programs: Program[];
  statusReq: StatusReq[];
  statusRes: StatusRes[];
}

export interface ToastMessage {
  id: number;
  type: 'success' | 'error' | 'info';
  text: string;
}
