"use client";

import type { ReactNode } from "react";
import { useTranslation } from "react-i18next";
import { cn } from "@/lib/utils";
import type { EmployeeSettingsSection, EmployeeSettingsSectionSlug } from "./settings-sections";

interface SettingsModuleLayoutProps {
  title: string;
  description: string;
  groups: Array<{ labelKey: string; items: EmployeeSettingsSection[] }>;
  activeSection: EmployeeSettingsSection;
  onSectionChange: (slug: EmployeeSettingsSectionSlug) => void;
  headerAction?: ReactNode;
  children: ReactNode;
}

export function SettingsModuleLayout({
  title,
  description,
  groups,
  activeSection,
  onSectionChange,
  headerAction,
  children
}: SettingsModuleLayoutProps) {
  const { t } = useTranslation("platform");

  return (
    <section className="space-y-6">
      <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
          <p className="text-sm text-muted-foreground">
            {t("employeesSettings.breadcrumb.dashboard")} / {t("employeesSettings.breadcrumb.employees")}
          </p>
          <h1 className="mt-2 text-2xl font-semibold text-foreground">{title}</h1>
          <p className="mt-1 max-w-3xl text-sm leading-6 text-muted-foreground">{description}</p>
        </div>
        {headerAction}
      </div>

      <div className="md:hidden">
        <label className="text-xs font-medium uppercase text-muted-foreground" htmlFor="employee-settings-section">
          {t("employeesSettings.nav.mobileLabel")}
        </label>
        <select
          id="employee-settings-section"
          className="mt-2 h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
          value={activeSection.slug}
          onChange={(event) => onSectionChange(event.target.value as EmployeeSettingsSectionSlug)}
        >
          {groups.map((group) => (
            <optgroup key={group.labelKey} label={t(group.labelKey)}>
              {group.items.map((item) => (
                <option key={item.slug} value={item.slug}>
                  {t(item.labelKey)}
                </option>
              ))}
            </optgroup>
          ))}
        </select>
      </div>

      <div className="grid gap-6 md:grid-cols-[200px_minmax(0,1fr)]">
        <nav className="hidden rounded-lg border border-border bg-card p-3 shadow-sm md:block">
          {groups.map((group, groupIndex) => (
            <div key={group.labelKey} className={groupIndex > 0 ? "mt-5" : undefined}>
              <p className="px-3 pb-2 text-xs font-semibold uppercase text-muted-foreground">{t(group.labelKey)}</p>
              <div className="space-y-1">
                {group.items.map((item) => {
                  const active = item.slug === activeSection.slug;

                  return (
                    <button
                      key={item.slug}
                      type="button"
                      className={cn(
                        "flex h-9 w-full items-center rounded-md border-l-2 border-transparent px-3 text-left text-sm font-medium text-muted-foreground transition hover:bg-slate-50 hover:text-foreground",
                        active && "border-l-primary bg-blue-50 text-blue-700 hover:bg-blue-50 hover:text-blue-700"
                      )}
                      onClick={() => onSectionChange(item.slug)}
                    >
                      <span className="truncate">{t(item.labelKey)}</span>
                    </button>
                  );
                })}
              </div>
            </div>
          ))}
        </nav>
        <div className="min-w-0">{children}</div>
      </div>
    </section>
  );
}
