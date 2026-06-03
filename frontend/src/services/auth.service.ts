import { privatePath, requestJson, requestPrivateJson } from "@/services/api-client";
import type {
  AuthUser,
  ChangePasswordRequest,
  ForgotPasswordRequest,
  LoginRequest,
  LoginResponse,
  ResetPasswordRequest,
  SetPasswordRequest
} from "@/types/auth";

export const authService = {
  login(payload: LoginRequest): Promise<LoginResponse> {
    return requestJson<LoginResponse>(privatePath("/auth/login"), {
      method: "POST",
      body: JSON.stringify(payload)
    });
  },
  logout(): Promise<null> {
    return requestPrivateJson<null>("/auth/logout", { method: "POST" });
  },
  currentUser(): Promise<AuthUser> {
    return requestPrivateJson<AuthUser>("/auth/me");
  },
  forgotPassword(payload: ForgotPasswordRequest): Promise<null> {
    return requestJson<null>(privatePath("/auth/forgot-password"), {
      method: "POST",
      body: JSON.stringify(payload)
    });
  },
  resetPassword(payload: ResetPasswordRequest): Promise<null> {
    return requestJson<null>(privatePath("/auth/reset-password"), {
      method: "POST",
      body: JSON.stringify(payload)
    });
  },
  setPassword(payload: SetPasswordRequest): Promise<null> {
    return requestJson<null>(privatePath("/auth/set-password"), {
      method: "POST",
      body: JSON.stringify(payload)
    });
  },
  changePassword(payload: ChangePasswordRequest): Promise<null> {
    return requestPrivateJson<null>("/auth/change-password", {
      method: "POST",
      body: JSON.stringify(payload)
    });
  },
  updatePreferences(language: "id" | "en"): Promise<{ language_preference: string }> {
    return requestPrivateJson<{ language_preference: string }>("/users/me/preferences", {
      method: "PATCH",
      body: JSON.stringify({ language_preference: language })
    });
  }
};
