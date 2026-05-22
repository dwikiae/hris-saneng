import { useTranslation } from "next-i18next/pages";
import type {
  DocumentItem as PemberkasanDocument,
  DocumentStatus,
} from "@/types/pemberkasan";

interface DocumentItemProps {
  document: PemberkasanDocument;
  status: DocumentStatus;
  disabled?: boolean;
  onUpload: (document: PemberkasanDocument) => void;
}

function fileNameFromPath(document: PemberkasanDocument): string | null {
  const value = document.file_name ?? document.file_path ?? document.path;

  if (!value) {
    return null;
  }

  return value.split("/").filter(Boolean).pop() ?? value;
}

export default function DocumentItem({
  document,
  status,
  disabled = false,
  onUpload,
}: DocumentItemProps) {
  const { t } = useTranslation("pemberkasan");
  const isDone = status === "done";
  const isUploading = status === "uploading";
  const fileName = fileNameFromPath(document);

  const badgeClass = isDone
    ? "border-emerald-200 bg-emerald-50 text-emerald-700"
    : isUploading
      ? "border-amber-200 bg-amber-50 text-amber-700"
      : "border-slate-200 bg-slate-50 text-slate-600";

  return (
    <li className="flex flex-col gap-3 border-b border-slate-200 px-4 py-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
      <div className="flex min-w-0 items-start gap-3">
        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-slate-100 text-slate-600">
          <svg
            aria-hidden="true"
            className="h-5 w-5"
            fill="none"
            stroke="currentColor"
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth="2"
            viewBox="0 0 24 24"
          >
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
            <path d="M14 2v6h6" />
            <path d="M16 13H8" />
            <path d="M16 17H8" />
            <path d="M10 9H8" />
          </svg>
        </div>
        <div className="min-w-0">
          <div className="flex flex-wrap items-center gap-2">
            <h3 className="text-sm font-semibold text-slate-950">
              {t(`documents.${document.document_type}.name`)}
            </h3>
            <span className="text-xs font-medium text-slate-500">
              {document.required
                ? t("documents.required")
                : t("documents.optional")}
            </span>
          </div>
          <p className="mt-1 truncate text-sm text-slate-500">
            {fileName ?? t("documents.noFile")}
          </p>
        </div>
      </div>

      <div className="flex shrink-0 items-center justify-between gap-3 sm:justify-end">
        <span
          className={`rounded-full border px-3 py-1 text-xs font-semibold ${badgeClass}`}
        >
          {t(`status.${status}`)}
        </span>
        <button
          type="button"
          className="inline-flex h-10 w-10 items-center justify-center rounded-md border border-slate-300 text-slate-700 transition hover:border-slate-500 disabled:cursor-not-allowed disabled:opacity-50"
          disabled={disabled || isUploading}
          onClick={() => onUpload(document)}
          aria-label={t("actions.uploadDocument", {
            document: t(`documents.${document.document_type}.name`),
          })}
          title={t("actions.upload")}
        >
          <svg
            aria-hidden="true"
            className="h-5 w-5"
            fill="none"
            stroke="currentColor"
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth="2"
            viewBox="0 0 24 24"
          >
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <path d="M17 8 12 3 7 8" />
            <path d="M12 3v12" />
          </svg>
        </button>
      </div>
    </li>
  );
}
