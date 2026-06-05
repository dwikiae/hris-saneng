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
