"use client";

import { useTranslation } from "react-i18next";
import type { JobPosting } from "@/types/recruitment";

interface JobCardProps {
  job: JobPosting;
  onApply: (jobId: number) => void;
}

function jobTitle(job: JobPosting): string {
  return job.title ?? job.nama_posisi ?? job.position_name ?? job.code ?? "-";
}

function department(job: JobPosting): string {
  return job.department ?? job.departemen ?? job.department_name ?? "-";
}

export function JobCard({ job, onApply }: JobCardProps) {
  const { t } = useTranslation(["common", "recruitment"]);

  return (
    <article className="flex h-full flex-col rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
      <div className="flex-1">
        <h3 className="text-lg font-semibold text-slate-950">{jobTitle(job)}</h3>
        <p className="mt-2 text-sm font-medium text-slate-500">
          {t("recruitment:job.department")}: {department(job)}
        </p>
        <p className="mt-4 line-clamp-4 text-sm leading-6 text-slate-600">
          {job.description ?? job.deskripsi_pekerjaan ?? job.requirements ?? job.persyaratan_umum ?? "-"}
        </p>
      </div>
      <button
        type="button"
        className="mt-5 rounded-md bg-sky-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-600"
        onClick={() => onApply(job.id)}
      >
        {t("common:actions.apply")}
      </button>
    </article>
  );
}
