"use client";

import { useState } from "react";
import { useTranslation } from "react-i18next";
import { ApplicationForm } from "@/components/modules/recruitment/public/ApplicationForm";
import { JobCard } from "@/components/modules/recruitment/public/JobCard";
import type { JobPosting } from "@/types/recruitment";

interface CareerPageProps {
  jobs: JobPosting[];
}

export function CareerPage({ jobs }: CareerPageProps) {
  const { t } = useTranslation("recruitment");
  const [selectedJobId, setSelectedJobId] = useState<number | null>(null);

  const selectJob = (jobId: number) => {
    setSelectedJobId(jobId);
    window.setTimeout(() => {
      document.getElementById("application-form")?.scrollIntoView({ behavior: "smooth", block: "start" });
    }, 0);
  };

  return (
    <main className="min-h-screen bg-slate-50 text-slate-950">
      <section className="border-b border-slate-200 bg-white">
        <div className="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
          <p className="text-sm font-semibold uppercase tracking-wide text-emerald-700">
            {t("hero.eyebrow")}
          </p>
          <h1 className="mt-3 max-w-3xl text-3xl font-semibold text-slate-950 sm:text-4xl">
            {t("hero.title")}
          </h1>
          <p className="mt-4 max-w-2xl text-base leading-7 text-slate-600">{t("hero.body")}</p>
        </div>
      </section>
      <section className="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <div className="mb-5">
          <h2 className="text-xl font-semibold text-slate-950">{t("list.title")}</h2>
          <p className="mt-1 text-sm text-slate-600">{t("list.count", { count: jobs.length })}</p>
        </div>
        {jobs.length > 0 ? (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {jobs.map((job) => (
              <JobCard key={job.id} job={job} onApply={selectJob} />
            ))}
          </div>
        ) : (
          <div className="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center">
            <h2 className="text-lg font-semibold text-slate-950">{t("empty.title")}</h2>
            <p className="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-600">{t("empty.body")}</p>
          </div>
        )}
      </section>
      <ApplicationForm jobs={jobs} selectedJobId={selectedJobId} />
    </main>
  );
}
