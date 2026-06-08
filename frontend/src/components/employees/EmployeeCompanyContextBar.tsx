"use client";

import { useEffect } from "react";
import { useTranslation } from "react-i18next";
import { useAuthStore } from "@/stores/auth.store";
import { EmployeeCompanySelector, useEmployeeCompanyOptions } from "./settings/EmployeeCompanySelector";

export function useEmployeeCompanyContext() {
  const user = useAuthStore((state) => state.user);
  const activeCompanyId = useAuthStore((state) => state.activeCompanyId);
  const setActiveCompanyId = useAuthStore((state) => state.setActiveCompanyId);
  const options = useEmployeeCompanyOptions(user);
  const hasCompanyContext = options.length > 0 && Boolean(activeCompanyId);

  useEffect(() => {
    if (options.length === 0) {
      if (activeCompanyId !== null) {
        setActiveCompanyId(null);
      }
      return;
    }

    const isValid = options.some((option) => option.id === activeCompanyId);
    if (!isValid) {
      setActiveCompanyId(options[0].id);
    }
  }, [activeCompanyId, options, setActiveCompanyId]);

  return {
    activeCompanyId,
    hasCompanyContext,
    options,
    setActiveCompanyId
  };
}

export function EmployeeCompanyContextBar() {
  const { t } = useTranslation("platform");
  const { activeCompanyId, options, setActiveCompanyId } = useEmployeeCompanyContext();

  if (options.length > 1 && activeCompanyId) {
    return (
      <div className="mb-4 flex items-center justify-end">
        <EmployeeCompanySelector options={options} value={activeCompanyId} onChange={setActiveCompanyId} />
      </div>
    );
  }

  if (options.length === 0) {
    return (
      <div className="mb-4 rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <p className="font-medium">{t("employeesCompanyContext.emptyTitle")}</p>
        <p className="mt-1 text-amber-800">{t("employeesCompanyContext.emptyDescription")}</p>
      </div>
    );
  }

  return null;
}
