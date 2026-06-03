import { requestPrivateJson } from "@/services/api-client";
import type {
  PermissionModuleNode,
  PlatformRole,
  RoleListResponse,
  RolePayload
} from "@/types/access";

interface RoleListParams {
  search?: string;
  companyId?: string;
  page?: number;
  perPage?: number;
}

function queryString(params: RoleListParams): string {
  const query = new URLSearchParams();

  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== "") {
      query.set(key === "companyId" ? "company_id" : key, String(value));
    }
  });

  const serialized = query.toString();
  return serialized ? `?${serialized}` : "";
}

export const roleService = {
  list(params: RoleListParams = {}): Promise<RoleListResponse> {
    return requestPrivateJson<RoleListResponse>(`/instance/roles${queryString(params)}`);
  },
  detail(roleId: string | number): Promise<PlatformRole> {
    return requestPrivateJson<PlatformRole>(`/instance/roles/${roleId}`);
  },
  create(payload: RolePayload): Promise<PlatformRole> {
    return requestPrivateJson<PlatformRole>("/instance/roles", {
      method: "POST",
      body: JSON.stringify(payload)
    });
  },
  update(roleId: string | number, payload: RolePayload): Promise<PlatformRole> {
    return requestPrivateJson<PlatformRole>(`/instance/roles/${roleId}`, {
      method: "PUT",
      body: JSON.stringify(payload)
    });
  },
  archive(roleId: string | number): Promise<null> {
    return requestPrivateJson<null>(`/instance/roles/${roleId}`, { method: "DELETE" });
  },
  permissionsStructure(): Promise<PermissionModuleNode[]> {
    return requestPrivateJson<PermissionModuleNode[]>("/instance/permissions/structure");
  },
  updatePermissions(roleId: string | number, permissionIds: Array<string | number>): Promise<PlatformRole> {
    return requestPrivateJson<PlatformRole>(`/instance/roles/${roleId}/permissions`, {
      method: "PATCH",
      body: JSON.stringify({ permission_ids: permissionIds })
    });
  }
};
