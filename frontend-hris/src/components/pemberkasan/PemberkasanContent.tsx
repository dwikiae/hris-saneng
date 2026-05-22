import { useTranslation } from "next-i18next/pages";
import DocumentItemRow from "@/components/pemberkasan/DocumentItem";
import { DocumentType, type DocumentItem, type PemberkasanSession } from "@/types/pemberkasan";
import {
  getDepartment,
  getPosition,
  getWhatsappNumber,
  isDocumentDone,
} from "@/utils/pemberkasan";

type PageState = "loading" | "error" | "active" | "complete";

interface PemberkasanContentProps {
  pageState: PageState;
  session: PemberkasanSession | null;
  progress: {
    uploaded: number;
    total: number;
    percentage: number;
  };
  countdown: string;
  uploadingType: DocumentType | null;
  onSelectDocument: (document: DocumentItem) => void;
}

export default function PemberkasanContent({
  pageState,
  session,
  progress,
  countdown,
  uploadingType,
  onSelectDocument,
}: PemberkasanContentProps) {
  const { t } = useTranslation("pemberkasan");

  if (pageState === "loading") {
    return <StatePanel title={t("loading.title")} body={t("loading.body")} />;
  }

  if (pageState === "error") {
    return <StatePanel title={t("error.title")} body={t("error.body")} />;
  }

  if (!session) {
    return null;
  }

  const position = getPosition(session);
  const department = getDepartment(session);
  const whatsapp = getWhatsappNumber(session);

  return (
    <div className="space-y-5">
      <section className="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-4 text-emerald-900 sm:px-5">
        <p className="text-sm font-semibold uppercase tracking-wide">
          {t("banner.eyebrow")}
        </p>
        <h1 className="mt-2 text-2xl font-semibold text-emerald-950 sm:text-3xl">
          {t("banner.title")}
        </h1>
        <p className="mt-2 max-w-3xl text-sm leading-6">
          {t("banner.body")}
        </p>
      </section>

      <section className="grid gap-4 md:grid-cols-[1fr_280px]">
        <InfoPanel
          label={t("job.label")}
          title={position ?? t("job.unavailable")}
          body={department ?? t("department.unavailable")}
        />
        <InfoPanel
          label={t("countdown.label")}
          title={countdown}
          body={t("countdown.format")}
          titleClassName="text-2xl"
        />
      </section>

      <section className="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div className="border-b border-slate-200 p-5">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
              <h2 className="text-lg font-semibold text-slate-950">
                {t("progress.title")}
              </h2>
              <p className="mt-1 text-sm text-slate-600">
                {t("progress.body", {
                  uploaded: progress.uploaded,
                  total: progress.total,
                })}
              </p>
            </div>
            <p className="text-sm font-semibold text-slate-700">
              {t("progress.count", {
                uploaded: progress.uploaded,
                total: progress.total,
              })}
            </p>
          </div>
          <div className="mt-4 h-3 overflow-hidden rounded-full bg-slate-100">
            <div
              className="h-full rounded-full bg-emerald-500 transition-all"
              style={{ width: `${progress.percentage}%` }}
            />
          </div>
        </div>

        <ul>
          {session.documents.map((document) => (
            <DocumentItemRow
              key={document.document_type}
              document={document}
              status={
                uploadingType === document.document_type
                  ? "uploading"
                  : isDocumentDone(document)
                    ? "done"
                    : "pending"
              }
              disabled={uploadingType !== null}
              onUpload={onSelectDocument}
            />
          ))}
        </ul>
      </section>

      {pageState === "complete" ? (
        <section className="rounded-md border border-emerald-200 bg-emerald-50 p-5 text-emerald-900">
          <h2 className="text-lg font-semibold text-emerald-950">
            {t("complete.title")}
          </h2>
          <p className="mt-2 text-sm leading-6">{t("complete.body")}</p>
        </section>
      ) : null}

      <section className="rounded-lg border border-slate-200 bg-white p-5 text-sm leading-6 text-slate-600 shadow-sm">
        <h2 className="text-base font-semibold text-slate-950">
          {t("help.title")}
        </h2>
        <p className="mt-1">
          {whatsapp
            ? t("help.bodyWithNumber", { number: whatsapp })
            : t("help.bodyWithoutNumber")}
        </p>
      </section>
    </div>
  );
}

interface InfoPanelProps {
  label: string;
  title: string;
  body: string;
  titleClassName?: string;
}

function InfoPanel({ label, title, body, titleClassName }: InfoPanelProps) {
  return (
    <div className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
      <p className="text-sm font-medium uppercase tracking-wide text-slate-500">
        {label}
      </p>
      <h2
        className={`mt-1 font-semibold text-slate-950 ${
          titleClassName ?? "text-xl"
        }`}
      >
        {title}
      </h2>
      <p className="mt-1 text-sm text-slate-600">{body}</p>
    </div>
  );
}

interface StatePanelProps {
  title: string;
  body: string;
}

function StatePanel({ title, body }: StatePanelProps) {
  return (
    <section className="mx-auto mt-12 w-full max-w-xl rounded-lg border border-slate-200 bg-white p-6 text-center shadow-sm">
      <h1 className="text-xl font-semibold text-slate-950">{title}</h1>
      <p className="mt-3 text-sm leading-6 text-slate-600">{body}</p>
    </section>
  );
}
