"use client";

import { BriefcaseBusiness, FileText, Users, Building2 } from "lucide-react";
import { useTranslation } from "react-i18next";
import { PageHeader } from "@/components/layout/PageHeader";
import { DataTable } from "@/components/shared/DataTable";
import { EmptyState } from "@/components/shared/EmptyState";
import { StatCard } from "@/components/shared/StatCard";
import { StatusBadge } from "@/components/shared/StatusBadge";

const metricKeys = ["employees", "candidates", "documents", "settings"] as const;
const metricIcons = {
  employees: Users,
  candidates: BriefcaseBusiness,
  documents: FileText,
  settings: Building2
};

const activityKeys = ["employees", "recruitment", "settings"] as const;

export default function DashboardPage() {
  const { t } = useTranslation("platform");

  return (
    <>
      <PageHeader title={t("dashboard.title")} description={t("dashboard.description")} />
      <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {metricKeys.map((key) => {
          const Icon = metricIcons[key];

          return (
            <StatCard
              key={key}
              label={t(`dashboard.metrics.${key}.label`)}
              value={t(`dashboard.metrics.${key}.value`)}
              helper={t(`dashboard.metrics.${key}.helper`)}
              icon={<Icon className="h-5 w-5" aria-hidden="true" />}
            />
          );
        })}
      </section>

      <section className="mt-6">
        <div className="mb-3 flex items-center justify-between gap-3">
          <h2>{t("dashboard.activityTitle")}</h2>
          <StatusBadge label={t("dashboard.status.ready")} tone="success" />
        </div>
        <DataTable
          columns={[
            { key: "area", header: t("dashboard.activityColumns.area"), cell: (row) => t(`nav.${row}`) },
            {
              key: "summary",
              header: t("dashboard.activityColumns.summary"),
              cell: (row) => t(`${row}.description`)
            }
          ]}
          rows={[...activityKeys]}
          getRowKey={(row) => row}
          emptyContent={
            <EmptyState
              title={t("dashboard.empty.title")}
              description={t("dashboard.empty.description")}
            />
          }
        />
      </section>
    </>
  );
}
