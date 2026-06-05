import { create } from "zustand";
import type { AuthUser } from "@/types/auth";

function readStoredCompanyId(): string | null {
  if (typeof window === "undefined") return null;
  return window.localStorage.getItem("dictive_hr_active_company_id");
}

function writeStoredCompanyId(id: string | null): void {
  if (typeof window === "undefined") return;
  if (id) {
    window.localStorage.setItem("dictive_hr_active_company_id", id);
  } else {
    window.localStorage.removeItem("dictive_hr_active_company_id");
  }
}

function resolveInitialCompanyId(user: AuthUser | null): string | null {
  const stored = readStoredCompanyId();

  if (stored) {
    const stillValid = user?.roles?.some(
      (role) => role.company_id !== null && role.company_id !== undefined && String(role.company_id) === stored
    );
    if (stillValid) return stored;
  }

  const firstRole = user?.roles?.find(
    (role) => role.company_id !== null && role.company_id !== undefined
  );
  return firstRole ? String(firstRole.company_id) : null;
}

interface AuthState {
  user: AuthUser | null;
  permissions: string[];
  isHydrated: boolean;
  activeCompanyId: string | null;
  setUser: (user: AuthUser | null) => void;
  setActiveCompanyId: (id: string | null) => void;
  hasPermission: (permission: string) => boolean;
}

export const useAuthStore = create<AuthState>((set, get) => ({
  user: null,
  permissions: [],
  isHydrated: false,
  activeCompanyId: null,
  setUser: (user) => {
    const activeCompanyId = resolveInitialCompanyId(user);
    writeStoredCompanyId(activeCompanyId);
    set({
      user,
      permissions: user?.permissions ?? [],
      activeCompanyId,
      isHydrated: true
    });
  },
  setActiveCompanyId: (id) => {
    writeStoredCompanyId(id);
    set({ activeCompanyId: id });
  },
  hasPermission: (permission) => get().permissions.includes(permission)
}));
