"use client";

import { useTranslation } from "react-i18next";
import type { DocumentItem as DocumentItemType, DocumentType } from "@/types/pemberkasan";

interface DocumentItemProps {
  document: DocumentItemType;
  onSelect: (document: DocumentItemType) => void;
}

export function DocumentItem({ document, onSelect }: DocumentItemProps) {
  const { t } = useTranslation(["common", "pemberkasan"]);
  const done = Boolean(document.uploaded_at);

  return (
    <article className="flex flex-col gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h3 className="text-sm font-semibold text-slate-950">
          {t(`pemberkasan:document.${document.document_type as DocumentType}`)}
        </h3>
        <p className={done ? "mt-1 text-sm font-medium text-emerald-700" : "mt-1 text-sm font-medium text-slate-500"}>
          {done ? t("pemberkasan:document.done") : t("pemberkasan:document.pending")}
        </p>
      </div>
      <button
        type="button"
        className="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-emerald-500 hover:text-emerald-700"
        onClick={() => onSelect(document)}
      >
        {t("common:actions.upload")}
      </button>
    </article>
  );
}
