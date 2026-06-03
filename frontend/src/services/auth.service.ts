import { requestPrivateJson } from "@/services/api-client";
import type { AuthUser } from "@/types/auth";

export const authService = {
  currentUser(): Promise<AuthUser> {
    return requestPrivateJson<AuthUser>("/auth/me");
  },
  updatePreferences(language: "id" | "en"): Promise<{ language_preference: string }> {
    return requestPrivateJson<{ language_preference: string }>("/users/me/preferences", {
      method: "PATCH",
      body: JSON.stringify({ language_preference: language })
    });
  }
};
