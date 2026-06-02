"use client";

import { useTranslation } from "react-i18next";
import { DocumentItem } from "@/components/modules/recruitment/pemberkasan/DocumentItem";
import type { DocumentItem as DocumentItemType, DocumentType, PemberkasanSession } from "@/types/pemberkasan";

interface PemberkasanContentProps {
  pageState: "loading" | "error" | "active" | "complete";
  session: PemberkasanSession | null;
  progress: {
    uploaded: number;
    total: number;
    percentage: number;
  };
  countdown: string;
  uploadingType: DocumentType | null;
  onSelectDocument: (document: DocumentItemType) => void;
}

export function PemberkasanContent({ pageState, session, progress, countdown, onSelectDocument }: PemberkasanContentProps) {
  const { t } = useTranslation("pemberkasan");

  if (pageState === "loading" || pageState === "error") {
    return <StatePanel title={t(`${pageState}.title`)} body={t(`${pageState}.body`)} />;
  }

  return (
    <div className="space-y-5">
      <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 className="text-xl font-semibold text-slate-950">
              {session?.applicant_name ?? session?.position_name ?? "-"}
            </h2>
            <p className="mt-1 text-sm text-slate-500">{countdown}</p>
          </div>
          <p className="text-sm font-semibold text-emerald-700">
            {t("progress.label", { uploaded: progress.uploaded, total: progress.total })}
          </p>
        </div>
        <div className="mt-4 h-2 rounded-full bg-slate-100">
          <div className="h-2 rounded-full bg-emerald-600" style={{ width: `${progress.percentage}%` }} />
        </div>
      </section>
      {pageState === "complete" ? <StatePanel title={t("complete.title")} body={t("complete.body")} /> : null}
      <section className="grid gap-3">
        {(session?.documents ?? []).map((document) => (
          <DocumentItem key={document.document_type} document={document} onSelect={onSelectDocument} />
        ))}
      </section>
    </div>
  );
}

function StatePanel({ title, body }: { title: string; body: string }) {
  return (
    <section className="rounded-lg border border-slate-200 bg-white p-8 text-center shadow-sm">
      <h2 className="text-xl font-semibold text-slate-950">{title}</h2>
      <p className="mt-2 text-sm leading-6 text-slate-600">{body}</p>
    </section>
  );
}
