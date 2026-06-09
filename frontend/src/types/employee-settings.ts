export type EmployeeMasterKind = "employee-levels" | "work-locations";

export interface EmployeeMasterListParams {
  search?: string;
  isActive?: boolean | null;
}

export interface EmployeeMasterBase {
  id: number | string;
  companyId?: number | string;
  code: string;
  name: string;
  isActive: boolean;
  status?: "active" | "archived" | string;
  createdAt?: string | null;
  updatedAt?: string | null;
}

export interface EmployeeLevel extends EmployeeMasterBase {
  description?: string | null;
  order: number;
}

export interface WorkLocation extends EmployeeMasterBase {
  address?: string | null;
  cityId?: string | null;
}

export type EmployeeMasterRecord = EmployeeLevel | WorkLocation;

export interface EmployeeLevelPayload {
  code?: string;
  name: string;
  description?: string | null;
  order?: number;
  is_active?: boolean;
}

export interface WorkLocationPayload {
  code?: string;
  name: string;
  address?: string | null;
  city_id?: string | null;
  is_active?: boolean;
}

export type EmployeeMasterPayload = EmployeeLevelPayload | WorkLocationPayload;

export interface EmployeeModuleSettings {
  employee_number_format: string;
  number_format?: string;
  number_format_tokens_available?: EmployeeNumberFormatToken[];
  probation_days: number;
  contract_expiry_notify_days: number;
  pkwt_max_months: number;
}

export interface EmployeeNumberFormatToken {
  token: string;
  description: {
    id: string;
    en: string;
  };
  example: string;
  requires_employee_data: boolean;
}

export interface EmployeeNumberFormatPreview {
  preview: string;
  next_sequence: number;
  tokens_used: string[];
  tokens_available: EmployeeNumberFormatToken[];
}

export interface WilayahProvince {
  code: string;
  name: string;
}

export interface WilayahCity {
  code: string;
  provinceCode: string;
  name: string;
}

export interface WilayahCountry {
  code: string;
  name: string;
  isActive: boolean;
}
