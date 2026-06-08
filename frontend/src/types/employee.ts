export type EmployeeStatus = "active" | "approved" | "pending" | "draft" | "rejected" | "inactive" | string;

export interface EmployeeLookup {
  id: number | string;
  code?: string | null;
  name?: string | null;
}

export interface EmployeeListItem {
  id: number | string;
  company_id?: number | string | null;
  employee_number?: string | null;
  name: string;
  email?: string | null;
  department_id?: number | string | null;
  position_id?: number | string | null;
  employment_type_id?: number | string | null;
  join_date?: string | null;
  status?: EmployeeStatus | null;
  archived_at?: string | null;
  archived_by?: number | string | null;
  department?: EmployeeLookup | null;
  position?: EmployeeLookup | null;
  employment_type?: EmployeeLookup | null;
  employmentType?: EmployeeLookup | null;
  photo_url?: string | null;
  avatarUrl?: string | null;
}

export interface EmployeeListParams {
  search?: string;
  status?: string;
  departmentId?: string;
  contractType?: string;
  page?: number;
  perPage?: number;
  sortBy?: string;
  sortDir?: "asc" | "desc";
  archived?: boolean;
}

export interface EmployeeListMeta {
  current_page?: number;
  per_page?: number;
  total?: number;
}

export interface EmployeeListResponse {
  items: EmployeeListItem[];
  meta?: EmployeeListMeta;
}

export interface MasterDataOption {
  id: number | string;
  code?: string | null;
  name: string;
  is_active?: boolean | null;
}

export interface EmployeeDetail extends EmployeeListItem {
  nickname?: string | null;
  phone?: string | null;
  address?: string | null;
  province_id?: string | number | null;
  city_id?: string | number | null;
  domicile_address?: string | null;
  domicile_province_id?: string | number | null;
  domicile_city_id?: string | number | null;
  birth_date?: string | null;
  birth_place?: string | null;
  country_of_birth?: string | null;
  gender?: string | null;
  religion_id?: number | string | null;
  marital_status_id?: number | string | null;
  blood_type_id?: number | string | null;
  nationality?: string | null;
  employee_level_id?: number | string | null;
  work_location_id?: number | string | null;
  supervisor_id?: number | string | null;
  probation_end_date?: string | null;
  end_date?: string | null;
  bank_name?: string | null;
  nik?: string | null;
  npwp?: string | null;
  passport_number?: string | null;
  bank_account_number?: string | null;
  salary?: string | number | null;
  allowances?: string | number | null;
  deductions?: string | number | null;
  religion?: EmployeeLookup | null;
  marital_status?: EmployeeLookup | null;
  blood_type?: EmployeeLookup | null;
  province?: EmployeeLookup | null;
  city?: EmployeeLookup | null;
  domicile_province?: EmployeeLookup | null;
  domicile_city?: EmployeeLookup | null;
  employee_level?: EmployeeLookup | null;
  work_location?: EmployeeLookup | null;
  supervisor?: EmployeeLookup | null;
}

export interface EmployeePayload {
  employee_number?: string;
  name: string;
  nickname?: string | null;
  email: string;
  phone: string;
  address: string;
  province_id?: string | null;
  city_id?: string | null;
  domicile_address?: string | null;
  domicile_province_id?: string | null;
  domicile_city_id?: string | null;
  birth_date: string;
  birth_place: string;
  country_of_birth?: string | null;
  gender: string;
  religion_id?: string | number | null;
  marital_status_id?: string | number | null;
  blood_type_id?: string | number | null;
  nationality?: string | null;
  passport_number?: string | null;
  department_id: string | number;
  position_id: string | number;
  employment_type_id: string | number;
  employee_level_id?: string | number | null;
  work_location_id?: string | number | null;
  supervisor_id?: string | number | null;
  join_date: string;
  probation_end_date?: string | null;
  end_date?: string | null;
  nik: string;
  npwp?: string | null;
  bank_name?: string | null;
  bank_account_number?: string | null;
  salary?: string | number | null;
  allowances?: string | number | null;
  deductions?: string | number | null;
  consent_at?: string;
  approver_id?: string | number | null;
}

export type EmployeeUpdatePayload = Partial<EmployeePayload>;

export interface EmployeePhotoUrls {
  original?: string | null;
  medium?: string | null;
  thumbnail?: string | null;
}

export type EmployeeContractStatus = "draft" | "active" | "expired" | "superseded" | "terminated" | string;
export type EmployeeContractType = "pkwt" | "pkwtt" | string;

export interface EmployeeContract {
  id: number | string;
  employee_id: number | string;
  contract_type: EmployeeContractType;
  contract_number?: string | null;
  start_date?: string | null;
  end_date?: string | null;
  status?: EmployeeContractStatus | null;
  notes?: string | null;
  approved_by?: number | string | null;
  approved_at?: string | null;
  archived_at?: string | null;
  created_at?: string | null;
}

export interface EmployeeFamily {
  id: number | string;
  name: string;
  relationship: "spouse" | "child" | "parent" | "sibling" | "other" | string;
  birth_date?: string | null;
  gender?: string | null;
  occupation?: string | null;
  phone?: string | null;
  is_dependent?: boolean | null;
}

export interface EmployeeEducation {
  id: number | string;
  institution_name: string;
  education_level_id: number | string;
  major?: string | null;
  start_year: number;
  end_year?: number | null;
  gpa?: string | number | null;
  certificate_number?: string | null;
  education_level?: EmployeeLookup | null;
}

export interface EmployeeExperience {
  id: number | string;
  company_name: string;
  position: string;
  start_date: string;
  end_date?: string | null;
  is_current?: boolean | null;
  responsibilities?: string | null;
  reason_leaving?: string | null;
}

export interface EmployeeNote {
  id: number | string;
  content: string;
  type?: "manual" | "system" | string;
  mentioned_users?: Array<number | string> | null;
  created_by?: number | string | null;
  created_at?: string | null;
  created_by_user?: EmployeeLookup | null;
}

export interface EmployeeDocument {
  id: number | string;
  document_type?: string | null;
  original_filename?: string | null;
  mime_type?: string | null;
  size_bytes?: number | null;
  uploaded_by?: number | string | null;
  uploaded_at?: string | null;
}

export interface OffboardingChecklistItem {
  id: number | string;
  offboarding_id: number | string;
  title: string;
  description?: string | null;
  assigned_to?: number | string | null;
  is_completed?: boolean | null;
  completed_by?: number | string | null;
  completed_at?: string | null;
  due_date?: string | null;
  order?: number | null;
}

export interface EmployeeOffboarding {
  id: number | string;
  reason_type: "resignation" | "termination" | "contract_end" | "retirement" | "other" | string;
  reason_detail?: string | null;
  last_working_date: string;
  status: "draft" | "in_progress" | "completed" | string;
  initiated_by?: number | string | null;
  initiated_at?: string | null;
  completed_by?: number | string | null;
  completed_at?: string | null;
  notes?: string | null;
  checklist_items?: OffboardingChecklistItem[];
}

export interface EmployeeOffboardingState {
  offboarding: EmployeeOffboarding | null;
  is_visible: boolean;
}

export interface EmployeeContractPayload {
  contract_type: "pkwt" | "pkwtt";
  contract_number?: string | null;
  start_date: string;
  end_date?: string | null;
  notes?: string | null;
}

export interface EmployeeFamilyPayload {
  name: string;
  relationship: string;
  birth_date?: string | null;
  gender: string;
  occupation?: string | null;
  phone?: string | null;
  is_dependent?: boolean;
}

export interface EmployeeEducationPayload {
  institution_name: string;
  education_level_id: string | number;
  major?: string | null;
  start_year: number;
  end_year?: number | null;
  gpa?: string | number | null;
  certificate_number?: string | null;
}

export interface EmployeeExperiencePayload {
  company_name: string;
  position: string;
  start_date: string;
  end_date?: string | null;
  is_current?: boolean;
  responsibilities?: string | null;
  reason_leaving?: string | null;
}

export interface EmployeeOffboardingPayload {
  reason_type: string;
  reason_detail?: string | null;
  last_working_date: string;
  notes?: string | null;
}

export interface OffboardingChecklistPayload {
  title: string;
  description?: string | null;
  assigned_to?: string | number | null;
  due_date?: string | null;
  order?: number;
}
