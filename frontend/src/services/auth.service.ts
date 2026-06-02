import { requestPrivateJson } from "@/services/api-client";
import type { AuthUser } from "@/types/auth";

export const authService = {
  currentUser(): Promise<AuthUser> {
    return requestPrivateJson<AuthUser>("/auth/me");
  }
};
