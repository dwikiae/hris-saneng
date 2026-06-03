"use client";

import { useQuery } from "@tanstack/react-query";
import { Building2, Pencil } from "lucide-react";
import { useParams } from "next/navigation";
import { useTranslation } from "react-i18next";
import { ChatLog } from "@/components/platform/ChatLog";
import { CompanyInfoPanel } from "@/components/settings/companies/CompanyInfoPanel";
import { CompanyModulesPanel } from "@/components/settings/companies/CompanyModulesPanel";
import { SettingsNav } from "@/components/settings/SettingsNav";
import { EmptyState } from "@/components/shared/EmptyState";
import { DetailPageTemplate } from "@/components/templates";
import { companyService } from "@/services/company.service";

function statusTone(status?: string | null) {
  if (status === "active") {
    return "success" as const;
  }
  if (status === "inactive") {
    return "warning" as const;
  }
  return "neutral" as const;
}

export function CompanyDetailPage() {
  const { t } = useTranslation("platform");
  const params = useParams<{ id: string }>();
  const companyId = params.id;
  const companyQuery = useQuery({
    queryKey: ["settings", "companies", companyId],
    queryFn: () => companyService.detail(companyId)
  });
  const modulesQuery = useQuery({
    queryKey: ["settings", "modules"],
    queryFn: () => companyService.modules()
  });
  const company = companyQuery.data;

  if (companyQuery.isError) {
    return (
      <div className="space-y-6">
        <SettingsNav />
        <EmptyState
          title={t("settingsCompanies.api.title")}
          description={t("settingsCompanies.api.companiesNotReady")}
        />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <SettingsNav />
      <DetailPageTemplate
        backUrl="/dashboard/settings/companies"
        backLabel={t("settingsCompanies.actions.back")}
        breadcrumbs={[
          { label: t("nav.settings"), href: "/dashboard/settings" },
          { label: t("settingsNav.companies"), href: "/dashboard/settings/companies" },
          { label: company?.name ?? t("settingsCompanies.detail.loading") }
        ]}
        actions={
          company
            ? [
                {
                  id: "edit",
                  label: t("settingsCompanies.actions.edit"),
                  href: `/dashboard/settings/companies/${company.id}/edit`,
                  icon: <Pencil className="h-4 w-4" />,
                  variant: "primary"
                }
              ]
            : []
        }
        avatarUrl={company?.logoUrl ?? company?.logoPath ?? undefined}
        avatarIcon={<Building2 className="h-6 w-6" aria-hidden="true" />}
        title={company?.name ?? t("settingsCompanies.detail.loading")}
        subtitle={[company?.companyType, company?.city].filter(Boolean).join(" · ")}
        entityId={company ? String(company.id) : undefined}
        metaInfo={[
          { label: t("settingsCompanies.fields.companyType"), value: company?.companyType ?? "-" },
          { label: t("settingsCompanies.fields.city"), value: company?.city ?? "-" },
          { label: t("settingsCompanies.fields.employeeCount"), value: company?.employeeCount ?? "-" },
          { label: t("settingsCompanies.fields.languageDefault"), value: company?.languageDefault ?? "-" }
        ]}
        status={
          company
            ? {
                label: t(`settingsCompanies.status.${company.status ?? "unknown"}`),
                tone: statusTone(company.status)
              }
            : undefined
        }
        isLoading={companyQuery.isLoading}
        tabs={[
          {
            slug: "informasi",
            label: t("settingsCompanies.detail.tabs.info"),
            content: company ? <CompanyInfoPanel company={company} /> : null
          },
          {
            slug: "modul",
            label: t("settingsCompanies.detail.tabs.modules"),
            content: (
              <CompanyModulesPanel
                modules={modulesQuery.data ?? company?.activeModules ?? []}
                isDisabled
              />
            )
          }
        ]}
        notesContent={
          <ChatLog
            module="platform"
            recordId={companyId}
            initialActivities={[]}
            initialNotes={[]}
          />
        }
      />
    </div>
  );
}
