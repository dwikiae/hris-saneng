export type AccessStatus = "active" | "inactive" | "pending" | "archived";

export interface AccessCompanyRef {
  id: string | number;
  name: string;
}

export interface AccessRoleRef {
  id: string | number;
  name: string;
  companyId?: string | number | null;
  companyName?: string | null;
}

export interface PlatformUser {
  id: string | number;
  name: string;
  email: string;
  avatarUrl?: string | null;
  employeeId?: string | number | null;
  employeeName?: string | null;
  companies?: AccessCompanyRef[];
  roles?: AccessRoleRef[];
  status?: AccessStatus | string | null;
  invitationStatus?: "pending" | "accepted" | "expired" | null;
  lastLoginAt?: string | null;
  lastActiveAt?: string | null;
  loginHistory?: Array<{ id: string | number; at: string; ip?: string | null; device?: string | null }>;
}

export interface PlatformRole {
  id: string | number;
  name: string;
  code?: string | null;
  description?: string | null;
  company?: AccessCompanyRef | null;
  userCount?: number | null;
  permissionCount?: number | null;
  status?: AccessStatus | string | null;
  users?: PlatformUser[];
  permissionIds?: Array<string | number>;
}

export interface AccessListMeta {
  current_page?: number;
  per_page?: number;
  total?: number;
  active?: number;
  pending_invitation?: number;
}

export interface UserListResponse {
  items: PlatformUser[];
  meta?: AccessListMeta;
}

export interface RoleListResponse {
  items: PlatformRole[];
  meta?: AccessListMeta;
}

export interface UserPayload {
  name: string;
  email?: string;
  employeeId?: string | number | null;
  companyIds?: Array<string | number>;
  roleIds?: Array<string | number>;
  note?: string | null;
}

export interface RolePayload {
  name: string;
  description?: string | null;
  companyId?: string | number | null;
  copyFromRoleId?: string | number | null;
  copyFromJobPositionId?: string | number | null;
}

export interface PermissionActionNode {
  id: string | number;
  code: string;
  label: string;
  action: string;
}

export interface PermissionMenuNode {
  id: string;
  label: string;
  actions: PermissionActionNode[];
}

export interface PermissionModuleNode {
  id: string;
  label: string;
  menus: PermissionMenuNode[];
}
