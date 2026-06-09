export type EmployeeMasterKind =
  | "departments"
  | "job-positions"
  | "employee-levels"
  | "contract-types"
  | "work-locations"
  | "religions"
  | "banks"
  | "document-types"
  | "education-levels";

export interface EmployeeMasterListParams {
  search?: string;
  isActive?: boolean | null;
  departmentId?: string | number | null;
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

export interface DepartmentMaster extends EmployeeMasterBase {
  description?: string | null;
  parentId?: number | string | null;
  totalEmployees?: number;
}

export interface JobPositionMaster extends EmployeeMasterBase {
  departmentId?: number | string | null;
  description?: string | null;
}

export interface ContractTypeMaster extends EmployeeMasterBase {
  type: "pkwt" | "pkwtt";
  description?: string | null;
  maxDurationMonths?: number | null;
}

export interface BankMaster extends EmployeeMasterBase {
  swift?: string | null;
}

export interface DocumentTypeMaster extends EmployeeMasterBase {
  isMandatory: boolean;
  description?: string | null;
}

export interface WorkLocation extends EmployeeMasterBase {
  address?: string | null;
  cityId?: string | null;
}

export interface EducationLevelMaster extends EmployeeMasterBase {
  order: number;
}

export type EmployeeMasterRecord =
  | DepartmentMaster
  | JobPositionMaster
  | ContractTypeMaster
  | BankMaster
  | DocumentTypeMaster
  | EducationLevelMaster
  | EmployeeLevel
  | WorkLocation;

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

export interface DepartmentMasterPayload {
  code?: string;
  name: string;
  description?: string | null;
  parent_id?: string | number | null;
  is_active?: boolean;
}

export interface JobPositionPayload {
  code?: string;
  name: string;
  department_id?: string | number | null;
  description?: string | null;
  is_active?: boolean;
}

export interface ContractTypePayload {
  code?: string;
  name: string;
  type: "pkwt" | "pkwtt";
  description?: string | null;
  max_duration_months?: number | null;
  is_active?: boolean;
}

export interface ReligionPayload {
  code?: string;
  name: string;
  is_active?: boolean;
}

export interface BankPayload {
  code?: string;
  name: string;
  swift?: string | null;
  is_active?: boolean;
}

export interface DocumentTypePayload {
  code?: string;
  name: string;
  is_mandatory?: boolean;
  description?: string | null;
  is_active?: boolean;
}

export interface EducationLevelMasterPayload {
  code?: string;
  name: string;
  order: number;
  is_active?: boolean;
}

export type EmployeeMasterPayload =
  | EmployeeLevelPayload
  | WorkLocationPayload
  | DepartmentMasterPayload
  | JobPositionPayload
  | ContractTypePayload
  | ReligionPayload
  | BankPayload
  | DocumentTypePayload
  | EducationLevelMasterPayload;

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
