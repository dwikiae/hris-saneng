"use client";

import { useQuery } from "@tanstack/react-query";
import { Building2, Plus } from "lucide-react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { useTranslation } from "react-i18next";
import { SettingsNav } from "@/components/settings/SettingsNav";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { ListPageTemplate } from "@/components/templates";
import { companyService } from "@/services/company.service";
import type { PlatformCompany } from "@/types/company";

function statusTone(status?: string | null) {
  if (status === "active") {
    return "success" as const;
  }
  if (status === "inactive") {
    return "warning" as const;
  }
  return "neutral" as const;
}

export default function CompaniesPage() {
  const { t } = useTranslation("platform");
  const router = useRouter();
  const [search, setSearch] = useState("");
  const { data, error, isLoading } = useQuery({
    queryKey: ["settings", "companies", search],
    queryFn: () => companyService.list({ search, perPage: 20 })
  });
  const companies = data?.items ?? [];

  return (
    <div className="space-y-6">
      <SettingsNav />
      <ListPageTemplate
        title={t("settingsCompanies.list.title")}
        description={t("settingsCompanies.list.description")}
        searchValue={search}
        onSearchChange={setSearch}
        actions={[
          {
            id: "new",
            label: t("settingsCompanies.actions.new"),
            href: "/dashboard/settings/companies/new",
            icon: <Plus className="h-4 w-4" />,
            variant: "primary"
          }
        ]}
        columns={[
          {
            key: "logo",
            header: t("settingsCompanies.columns.logo"),
            cell: (company) => (
              <Avatar className="h-9 w-9 rounded-md">
                {company.logoUrl || company.logoPath ? <AvatarImage src={company.logoUrl ?? company.logoPath ?? ""} alt={company.name} /> : null}
                <AvatarFallback className="rounded-md bg-blue-100 text-blue-700">
                  <Building2 className="h-4 w-4" aria-hidden="true" />
                </AvatarFallback>
              </Avatar>
            )
          },
          { key: "name", header: t("settingsCompanies.columns.name"), cell: (company) => company.name },
          { key: "type", header: t("settingsCompanies.columns.type"), cell: (company) => company.companyType ?? "-" },
          { key: "city", header: t("settingsCompanies.columns.city"), cell: (company) => company.city ?? "-" },
          {
            key: "modules",
            header: t("settingsCompanies.columns.modules"),
            cell: (company) => (
              <div className="flex flex-wrap gap-1">
                {(company.activeModules ?? []).slice(0, 3).map((module) => (
                  <Badge key={module.code} variant="outline">
                    {module.name}
                  </Badge>
                ))}
                {(company.activeModules ?? []).length === 0 ? "-" : null}
              </div>
            )
          },
          { key: "employees", header: t("settingsCompanies.columns.employeeCount"), cell: (company) => company.employeeCount ?? "-" },
          {
            key: "status",
            header: t("settingsCompanies.columns.status"),
            cell: (company) => (
              <StatusBadge
                label={t(`settingsCompanies.status.${company.status ?? "unknown"}`)}
                tone={statusTone(company.status)}
              />
            )
          },
          {
            key: "actions",
            header: t("settingsCompanies.columns.actions"),
            cell: (company) => (
              <Button asChild size="sm" variant="outline">
                <Link href={`/dashboard/settings/companies/${company.id}`}>
                  {t("settingsCompanies.actions.open")}
                </Link>
              </Button>
            )
          }
        ]}
        data={companies}
        getRowId={(company: PlatformCompany) => String(company.id)}
        onRowClick={(company) => router.push(`/dashboard/settings/companies/${company.id}`)}
        mobileCard={(company) => (
          <div className="space-y-2">
            <p className="font-medium text-foreground">{company.name}</p>
            <p className="text-sm text-muted-foreground">{[company.companyType, company.city].filter(Boolean).join(" · ") || "-"}</p>
            <StatusBadge label={t(`settingsCompanies.status.${company.status ?? "unknown"}`)} tone={statusTone(company.status)} />
          </div>
        )}
        emptyState={{
          title: t("settingsCompanies.empty.title"),
          description: t("settingsCompanies.empty.description")
        }}
        isLoading={isLoading}
        error={error instanceof Error ? new Error(t("settingsCompanies.api.companiesNotReady")) : null}
      />
    </div>
  );
}
