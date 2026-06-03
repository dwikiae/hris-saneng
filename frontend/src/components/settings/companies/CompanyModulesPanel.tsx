"use client";

import { useTranslation } from "react-i18next";
import { EmptyState } from "@/components/shared/EmptyState";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { Button } from "@/components/ui/button";
import type { CompanyModuleSummary } from "@/types/company";

interface CompanyModulesPanelProps {
  modules: CompanyModuleSummary[];
  isDisabled?: boolean;
}

export function CompanyModulesPanel({ modules, isDisabled = true }: CompanyModulesPanelProps) {
  const { t } = useTranslation("platform");

  if (modules.length === 0) {
    return (
      <EmptyState
        title={t("settingsCompanies.modules.emptyTitle")}
        description={t("settingsCompanies.api.modulesNotReady")}
      />
    );
  }

  return (
    <div className="divide-y rounded-lg border border-border bg-card">
      {modules.map((module) => (
        <div key={module.code} className="flex flex-col gap-3 p-4 md:flex-row md:items-center md:justify-between">
          <div>
            <div className="flex flex-wrap items-center gap-2">
              <h3 className="text-sm font-semibold text-foreground">{module.name}</h3>
              <StatusBadge
                label={module.isActive ? t("settingsCompanies.status.active") : t("settingsCompanies.status.inactive")}
                tone={module.isActive ? "success" : "neutral"}
              />
            </div>
            <p className="mt-1 text-sm text-muted-foreground">{module.description ?? module.code}</p>
          </div>
          <Button type="button" variant="outline" disabled={isDisabled}>
            {module.isActive ? t("settingsCompanies.modules.disable") : t("settingsCompanies.modules.enable")}
          </Button>
        </div>
      ))}
    </div>
  );
}
