"use client";

import { useTranslation } from "react-i18next";
import { PlatformLayout } from "@/components/core/platform/PlatformLayout";

const metricKeys = ["employees", "candidates", "documents", "settings"] as const;

export default function DashboardPage() {
  const { t } = useTranslation("platform");

  return (
    <PlatformLayout titleKey="dashboard.title" descriptionKey="dashboard.description">
      <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {metricKeys.map((key) => (
          <article key={key} className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-sm font-medium text-slate-500">{t(`dashboard.metrics.${key}.label`)}</p>
            <p className="mt-3 text-3xl font-semibold text-slate-950">{t(`dashboard.metrics.${key}.value`)}</p>
            <p className="mt-2 text-sm text-slate-600">{t(`dashboard.metrics.${key}.helper`)}</p>
          </article>
        ))}
      </section>
      <section className="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h2 className="text-lg font-semibold text-slate-950">{t("dashboard.activityTitle")}</h2>
        <div className="mt-4 grid gap-3 md:grid-cols-3">
          {["employees", "recruitment", "settings"].map((key) => (
            <div key={key} className="rounded-md border border-slate-200 p-4">
              <p className="text-sm font-semibold text-slate-950">{t(`nav.${key}`)}</p>
              <p className="mt-2 text-sm leading-6 text-slate-600">{t(`${key}.description`)}</p>
            </div>
          ))}
        </div>
      </section>
    </PlatformLayout>
  );
}
