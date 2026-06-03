import { create } from "zustand";
import type { AuthUser } from "@/types/auth";

interface AuthState {
  user: AuthUser | null;
  permissions: string[];
  isHydrated: boolean;
  setUser: (user: AuthUser | null) => void;
  hasPermission: (permission: string) => boolean;
}

export const useAuthStore = create<AuthState>((set, get) => ({
  user: null,
  permissions: [],
  isHydrated: false,
  setUser: (user) =>
    set({
      user,
      permissions: user?.permissions ?? [],
      isHydrated: true
    }),
  hasPermission: (permission) => get().permissions.includes(permission)
}));
