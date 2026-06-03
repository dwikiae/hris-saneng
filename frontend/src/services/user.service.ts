import { requestPrivateJson } from "@/services/api-client";
import type { PlatformUser, UserListResponse, UserPayload } from "@/types/access";

interface UserListParams {
  search?: string;
  status?: string;
  companyId?: string;
  roleId?: string;
  page?: number;
  perPage?: number;
}

function queryString(params: UserListParams): string {
  const query = new URLSearchParams();

  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== "") {
      query.set(key === "companyId" ? "company_id" : key === "roleId" ? "role_id" : key, String(value));
    }
  });

  const serialized = query.toString();
  return serialized ? `?${serialized}` : "";
}

export const userService = {
  list(params: UserListParams = {}): Promise<UserListResponse> {
    return requestPrivateJson<UserListResponse>(`/instance/users${queryString(params)}`);
  },
  detail(userId: string | number): Promise<PlatformUser> {
    return requestPrivateJson<PlatformUser>(`/instance/users/${userId}`);
  },
  create(payload: UserPayload): Promise<PlatformUser> {
    return requestPrivateJson<PlatformUser>("/instance/users", {
      method: "POST",
      body: JSON.stringify(payload)
    });
  },
  update(userId: string | number, payload: UserPayload): Promise<PlatformUser> {
    return requestPrivateJson<PlatformUser>(`/instance/users/${userId}`, {
      method: "PUT",
      body: JSON.stringify(payload)
    });
  },
  archive(userId: string | number): Promise<null> {
    return requestPrivateJson<null>(`/instance/users/${userId}`, { method: "DELETE" });
  },
  resendInvitation(userId: string | number): Promise<null> {
    return requestPrivateJson<null>(`/instance/users/${userId}/resend-invitation`, { method: "POST" });
  }
};
