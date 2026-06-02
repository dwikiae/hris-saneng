"use client";

import { FormEvent, useState } from "react";
import { useTranslation } from "react-i18next";
import type { DocumentItem } from "@/types/pemberkasan";

interface UploadModalProps {
  document: DocumentItem;
  isUploading: boolean;
  onClose: () => void;
  onUpload: (file: File) => void;
}

export function UploadModal({ document, isUploading, onClose, onUpload }: UploadModalProps) {
  const { t } = useTranslation(["common", "pemberkasan"]);
  const [file, setFile] = useState<File | null>(null);

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    if (file) {
      onUpload(file);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4">
      <form onSubmit={submit} className="w-full max-w-md rounded-lg bg-white p-5 shadow-xl">
        <h2 className="text-lg font-semibold text-slate-950">{t("pemberkasan:upload.title")}</h2>
        <p className="mt-1 text-sm text-slate-600">
          {t(`pemberkasan:document.${document.document_type}`)}
        </p>
        <label className="mt-4 block text-sm font-medium text-slate-700">
          <span>{t("pemberkasan:upload.choose")}</span>
          <input
            required
            type="file"
            className="mt-2 w-full rounded-md border border-slate-300 px-3 py-2"
            onChange={(event) => setFile(event.target.files?.[0] ?? null)}
          />
        </label>
        <div className="mt-5 flex justify-end gap-3">
          <button type="button" className="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700" onClick={onClose}>
            {t("common:actions.cancel")}
          </button>
          <button type="submit" disabled={isUploading} className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">
            {t("pemberkasan:upload.submit")}
          </button>
        </div>
      </form>
    </div>
  );
}
