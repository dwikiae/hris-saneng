import { useTranslation } from "next-i18next/pages";
import type { JobPosting } from "@/types/recruitment";

interface JobCardProps {
  job: JobPosting;
  onApply: (jobId: number) => void;
}

function getPositionName(job: JobPosting): string | null {
  return job.nama_posisi ?? job.position_name ?? job.title ?? null;
}

function getDepartmentName(job: JobPosting): string | null {
  return job.departemen ?? job.department_name ?? job.department ?? null;
}

function getDescription(job: JobPosting): string | null {
  return job.deskripsi_pekerjaan ?? job.description ?? null;
}

function truncate(value: string, maxLength: number): string {
  if (value.length <= maxLength) {
    return value;
  }

  return `${value.slice(0, maxLength).trim()}...`;
}

export default function JobCard({ job, onApply }: JobCardProps) {
  const { t } = useTranslation("recruitment");
  const positionName = getPositionName(job) ?? t("job.fallbackPosition");
  const departmentName =
    getDepartmentName(job) ?? t("job.fallbackDepartment");
  const description = truncate(
    getDescription(job) ?? t("job.fallbackDescription"),
    150,
  );

  return (
    <article className="flex h-full flex-col rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
      <div className="flex-1">
        <p className="text-sm font-medium uppercase tracking-wide text-emerald-700">
          {departmentName}
        </p>
        <h2 className="mt-2 text-xl font-semibold text-slate-950">
          {positionName}
        </h2>
        <p className="mt-3 text-sm leading-6 text-slate-600">{description}</p>
      </div>
      <button
        type="button"
        className="mt-5 inline-flex h-11 items-center justify-center rounded-md bg-slate-950 px-4 text-sm font-semibold text-white transition hover:bg-slate-800"
        aria-label={t("actions.applyFor", { position: positionName })}
        onClick={() => onApply(job.id)}
      >
        {t("actions.apply")}
      </button>
    </article>
  );
}
