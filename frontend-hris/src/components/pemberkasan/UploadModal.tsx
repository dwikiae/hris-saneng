import { useRef, useState } from "react";
import { useTranslation } from "next-i18next/pages";
import type { DocumentItem } from "@/types/pemberkasan";

const maxFileSize = 10 * 1024 * 1024;
const allowedMimeTypes = ["application/pdf", "image/jpeg", "image/png"];

interface UploadModalProps {
  document: DocumentItem;
  isUploading: boolean;
  onClose: () => void;
  onUpload: (file: File) => Promise<void>;
}

export default function UploadModal({
  document,
  isUploading,
  onClose,
  onUpload,
}: UploadModalProps) {
  const { t } = useTranslation("pemberkasan");
  const inputRef = useRef<HTMLInputElement>(null);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [errorKey, setErrorKey] = useState<string | null>(null);
  const [isDragging, setIsDragging] = useState(false);

  const validateFile = (file: File): boolean => {
    if (!allowedMimeTypes.includes(file.type)) {
      setErrorKey("upload.errors.type");
      setSelectedFile(null);
      return false;
    }

    if (file.size > maxFileSize) {
      setErrorKey("upload.errors.size");
      setSelectedFile(null);
      return false;
    }

    setErrorKey(null);
    setSelectedFile(file);
    return true;
  };

  const handleFiles = (files: FileList | null) => {
    const [file] = Array.from(files ?? []);

    if (file) {
      validateFile(file);
    }
  };

  const submit = async () => {
    if (!selectedFile || !validateFile(selectedFile)) {
      return;
    }

    await onUpload(selectedFile);
  };

  return (
    <div
      className="fixed inset-0 z-50 flex items-end bg-slate-950/50 p-4 sm:items-center sm:justify-center"
      role="dialog"
      aria-modal="true"
      aria-labelledby="pemberkasan-upload-title"
    >
      <div className="w-full max-w-lg rounded-lg bg-white p-5 shadow-xl">
        <div className="flex items-start justify-between gap-4">
          <div>
            <h2
              id="pemberkasan-upload-title"
              className="text-lg font-semibold text-slate-950"
            >
              {t("upload.title", {
                document: t(`documents.${document.document_type}.name`),
              })}
            </h2>
            <p className="mt-1 text-sm leading-6 text-slate-600">
              {t("upload.description")}
            </p>
          </div>
          <button
            type="button"
            className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-800"
            onClick={onClose}
            disabled={isUploading}
            aria-label={t("actions.close")}
            title={t("actions.close")}
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
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
          </button>
        </div>

        <button
          type="button"
          className={`mt-5 flex min-h-44 w-full flex-col items-center justify-center rounded-md border-2 border-dashed px-4 py-6 text-center transition ${
            isDragging
              ? "border-emerald-400 bg-emerald-50"
              : "border-slate-300 bg-slate-50 hover:border-slate-400"
          }`}
          onClick={() => inputRef.current?.click()}
          onDragEnter={(event) => {
            event.preventDefault();
            setIsDragging(true);
          }}
          onDragOver={(event) => {
            event.preventDefault();
            setIsDragging(true);
          }}
          onDragLeave={(event) => {
            event.preventDefault();
            setIsDragging(false);
          }}
          onDrop={(event) => {
            event.preventDefault();
            setIsDragging(false);
            handleFiles(event.dataTransfer.files);
          }}
        >
          <svg
            aria-hidden="true"
            className="h-9 w-9 text-slate-500"
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
          <span className="mt-3 text-sm font-semibold text-slate-900">
            {selectedFile?.name ?? t("upload.dropzone.title")}
          </span>
          <span className="mt-1 text-xs text-slate-500">
            {t("upload.dropzone.body")}
          </span>
        </button>

        <input
          ref={inputRef}
          type="file"
          className="sr-only"
          accept="application/pdf,image/jpeg,image/png"
          onChange={(event) => handleFiles(event.target.files)}
        />

        {errorKey ? (
          <p className="mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            {t(errorKey)}
          </p>
        ) : null}

        <div className="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <button
            type="button"
            className="rounded-md border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-500 disabled:cursor-not-allowed disabled:opacity-50"
            onClick={onClose}
            disabled={isUploading}
          >
            {t("actions.cancel")}
          </button>
          <button
            type="button"
            className="rounded-md bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
            disabled={!selectedFile || isUploading}
            onClick={() => void submit()}
          >
            {isUploading ? t("actions.uploading") : t("actions.upload")}
          </button>
        </div>
      </div>
    </div>
  );
}
