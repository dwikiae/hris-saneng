"use client";

import { useTranslation } from "react-i18next";
import type { PlatformCompany } from "@/types/company";

interface CompanyInfoPanelProps {
  company: PlatformCompany;
}

function valueOf(value?: string | number | null) {
  return value === undefined || value === null || value === "" ? "-" : String(value);
}

export function CompanyInfoPanel({ company }: CompanyInfoPanelProps) {
  const { t } = useTranslation("platform");
  const groups = [
    {
      title: t("settingsCompanies.form.sections.identity"),
      items: [
        ["name", company.name],
        ["legalName", company.legalName],
        ["tagline", company.tagline],
        ["companyType", company.companyType],
        ["industry", company.industry],
        ["foundedDate", company.foundedDate]
      ]
    },
    {
      title: t("settingsCompanies.form.sections.contact"),
      items: [
        ["address", company.address],
        ["city", company.city],
        ["province", company.province],
        ["postalCode", company.postalCode],
        ["phone", company.phone],
        ["email", company.email],
        ["website", company.website],
        ["hrPicName", company.hrPicName],
        ["hrPicPhone", company.hrPicPhone],
        ["hrPicEmail", company.hrPicEmail]
      ]
    },
    {
      title: t("settingsCompanies.form.sections.legal"),
      items: [
        ["npwp", company.npwp],
        ["nibOrSiup", company.nibOrSiup],
        ["bpjsKetenagakerjaan", company.bpjsKetenagakerjaan],
        ["bpjsKesehatan", company.bpjsKesehatan],
        ["wlkpNumber", company.wlkpNumber],
        ["directorName", company.directorName]
      ]
    },
    {
      title: t("settingsCompanies.form.sections.operations"),
      items: [
        ["timezone", company.timezone],
        ["dateFormat", company.dateFormat],
        ["languageDefault", company.languageDefault],
        ["sessionDurationHours", company.sessionDurationHours],
        ["loginLockoutAttempts", company.loginLockoutAttempts],
        ["loginLockoutMinutes", company.loginLockoutMinutes]
      ]
    }
  ];

  return (
    <div className="space-y-4">
      {groups.map((group) => (
        <section key={group.title} className="rounded-lg border border-border bg-card p-5">
          <h2 className="text-base font-semibold text-foreground">{group.title}</h2>
          <dl className="mt-4 grid gap-4 md:grid-cols-2">
            {group.items.map(([key, value]) => (
              <div key={key}>
                <dt className="text-xs font-medium uppercase text-muted-foreground">
                  {t(`settingsCompanies.fields.${key}`)}
                </dt>
                <dd className="mt-1 text-sm text-foreground">{valueOf(value)}</dd>
              </div>
            ))}
          </dl>
        </section>
      ))}
    </div>
  );
}
