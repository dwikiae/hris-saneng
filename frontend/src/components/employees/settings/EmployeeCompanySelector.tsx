"use client";

import { useMemo } from "react";
import { useTranslation } from "react-i18next";
import type { AuthUser } from "@/types/auth";

export interface EmployeeCompanyOption {
  id: string;
  name: string;
}

export function companyOptionsFromUser(user: AuthUser | null): EmployeeCompanyOption[] {
  const seen = new Set<string>();
  const options: EmployeeCompanyOption[] = [];

  for (const role of user?.roles ?? []) {
    if (role.company_id === null || role.company_id === undefined) {
      continue;
    }

    const id = String(role.company_id);
    if (!seen.has(id)) {
      seen.add(id);
      options.push({ id, name: role.company_name ?? id });
    }
  }

  return options;
}

export function useEmployeeCompanyOptions(user: AuthUser | null): EmployeeCompanyOption[] {
  return useMemo(() => companyOptionsFromUser(user), [user]);
}

export function EmployeeCompanySelector({
  options,
  value,
  onChange
}: {
  options: EmployeeCompanyOption[];
  value: string;
  onChange: (value: string) => void;
}) {
  const { t } = useTranslation("platform");

  if (options.length <= 1) {
    return null;
  }

  return (
    <label className="flex min-w-[220px] flex-col gap-1 text-sm font-medium text-muted-foreground">
      {t("employeesSettings.company.label")}
      <select
        className="h-9 rounded-md border border-input bg-background px-3 text-sm font-normal text-foreground"
        value={value}
        onChange={(event) => onChange(event.target.value)}
      >
        {options.map((company) => (
          <option key={company.id} value={company.id}>
            {company.name}
          </option>
        ))}
      </select>
    </label>
  );
}
