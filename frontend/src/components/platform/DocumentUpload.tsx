"use client";

import { Archive, Download, FileText, UploadCloud } from "lucide-react";
import { useRef, useState } from "react";
import { useTranslation } from "react-i18next";
import { platformToast } from "@/components/platform/ToastProvider";
import { Button } from "@/components/ui/button";
import { documentService } from "@/services/platform.service";
import { privateHeaders, privatePath } from "@/services/api-client";
import type { ApiResponse } from "@/types/api";
import { unwrapApiData } from "@/types/api";
import type { PlatformDocument } from "@/types/platform";

interface DocumentUploadProps {
  uploadEndpoint: string;
  documents: PlatformDocument[];
  fieldName?: string;
  maxSizeMb?: number;
  allowedMimeTypes?: string[];
  allowedExtensions?: string[];
  onUploaded?: (document: PlatformDocument) => void;
  onArchived?: (document: PlatformDocument) => void;
  archiveEndpoint?: (document: PlatformDocument) => string;
  uploadFile?: (file: File, onProgress: (progress: number) => void) => Promise<PlatformDocument>;
  archiveFile?: (document: PlatformDocument) => Promise<null>;
}

const defaultMimeTypes = [
  "application/pdf",
  "image/png",
  "image/jpeg",
  "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
  "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
];
const defaultExtensions = [".pdf", ".png", ".jpg", ".jpeg", ".docx", ".xlsx"];

function formatBytes(bytes: number) {
  if (bytes < 1024 * 1024) {
    return `${Math.round(bytes / 1024)} KB`;
  }

  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function uploadWithProgress(
  endpoint: string,
  file: File,
  fieldName: string,
  onProgress: (progress: number) => void
): Promise<PlatformDocument> {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    const formData = new FormData();
    formData.append(fieldName, file);
    xhr.open("POST", privatePath(endpoint));

    Object.entries(privateHeaders()).forEach(([key, value]) => {
      if (typeof value === "string") {
        xhr.setRequestHeader(key, value);
      }
    });
    xhr.setRequestHeader("Accept", "application/json");
    xhr.upload.onprogress = (event) => {
      if (event.lengthComputable) {
        onProgress(Math.round((event.loaded / event.total) * 100));
      }
    };
    xhr.onload = () => {
      try {
        const payload = JSON.parse(xhr.responseText) as ApiResponse<PlatformDocument>;
        if (xhr.status >= 200 && xhr.status < 300 && payload.success) {
          resolve(unwrapApiData(payload));
          return;
        }
        reject(new Error(payload.message));
      } catch (error) {
        reject(error instanceof Error ? error : new Error("upload.failed"));
      }
    };
    xhr.onerror = () => reject(new Error("upload.failed"));
    xhr.send(formData);
  });
}

export function DocumentUpload({
  uploadEndpoint,
  documents,
  fieldName = "document",
  maxSizeMb = 5,
  allowedMimeTypes = defaultMimeTypes,
  allowedExtensions = defaultExtensions,
  onUploaded,
  onArchived,
  archiveEndpoint,
  uploadFile,
  archiveFile
}: DocumentUploadProps) {
  const { t } = useTranslation("platform");
  const inputRef = useRef<HTMLInputElement>(null);
  const [progressByFile, setProgressByFile] = useState<Record<string, number>>({});
  const maxBytes = maxSizeMb * 1024 * 1024;

  const validate = (file: File) => {
    const extension = `.${file.name.split(".").pop() ?? ""}`.toLowerCase();
    return file.size <= maxBytes && allowedMimeTypes.includes(file.type) && allowedExtensions.includes(extension);
  };

  const handleFiles = async (files: FileList | File[]) => {
    for (const file of Array.from(files)) {
      if (!validate(file)) {
        platformToast.error(t("platformBehavior.documents.invalid"));
        continue;
      }

      try {
        const document = uploadFile
          ? await uploadFile(file, (progress) => setProgressByFile((current) => ({ ...current, [file.name]: progress })))
          : await uploadWithProgress(uploadEndpoint, file, fieldName, (progress) =>
              setProgressByFile((current) => ({ ...current, [file.name]: progress }))
            );
        onUploaded?.(document);
        platformToast.success(t("platformBehavior.documents.uploaded"));
      } catch {
        platformToast.error(t("platformBehavior.documents.failed"));
      } finally {
        setProgressByFile((current) => {
          const next = { ...current };
          delete next[file.name];
          return next;
        });
      }
    }
  };

  const archiveDocument = async (document: PlatformDocument) => {
    if (!archiveEndpoint && !archiveFile) {
      return;
    }

    if (archiveFile) {
      await archiveFile(document);
    } else if (archiveEndpoint) {
      await documentService.archive(archiveEndpoint(document));
    }
    onArchived?.(document);
  };

  return (
    <div className="space-y-4">
      <div
        className="flex min-h-36 flex-col items-center justify-center rounded-lg border border-dashed border-border bg-slate-50 px-4 py-6 text-center"
        onDragOver={(event) => event.preventDefault()}
        onDrop={(event) => {
          event.preventDefault();
          void handleFiles(event.dataTransfer.files);
        }}
      >
        <UploadCloud className="h-8 w-8 text-primary" aria-hidden="true" />
        <p className="mt-2 text-sm font-medium text-foreground">{t("platformBehavior.documents.drop")}</p>
        <p className="text-xs text-muted-foreground">
          {t("platformBehavior.documents.limit", { size: maxSizeMb })}
        </p>
        <Button type="button" variant="outline" className="mt-4" onClick={() => inputRef.current?.click()}>
          {t("platformBehavior.documents.browse")}
        </Button>
        <input
          ref={inputRef}
          type="file"
          multiple
          className="hidden"
          onChange={(event) => {
            if (event.target.files) {
              void handleFiles(event.target.files);
            }
          }}
        />
      </div>

      {Object.entries(progressByFile).map(([name, progress]) => (
        <div key={name} className="space-y-1">
          <div className="flex justify-between text-xs text-muted-foreground">
            <span>{name}</span>
            <span>{progress}%</span>
          </div>
          <div className="h-2 rounded-full bg-slate-100">
            <div className="h-2 rounded-full bg-primary" style={{ width: `${progress}%` }} />
          </div>
        </div>
      ))}

      <div className="divide-y rounded-lg border border-border bg-background">
        {documents.map((document) => (
          <div key={document.id} className="flex flex-col gap-3 p-3 sm:flex-row sm:items-center">
            <FileText className="h-5 w-5 text-primary" aria-hidden="true" />
            <div className="min-w-0 flex-1">
              <p className="truncate text-sm font-medium text-foreground">{document.name}</p>
              <p className="text-xs text-muted-foreground">
                {formatBytes(document.sizeBytes)} • {document.uploadedBy ?? "-"} • {document.uploadedAt ?? "-"}
              </p>
            </div>
            <div className="flex gap-2">
              <Button type="button" variant="outline" size="sm" onClick={() => void documentService.download(document.id)}>
                <Download className="mr-2 h-4 w-4" aria-hidden="true" />
                {t("platformBehavior.documents.download")}
              </Button>
              <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={!archiveEndpoint && !archiveFile}
                onClick={() => void archiveDocument(document)}
              >
                <Archive className="mr-2 h-4 w-4" aria-hidden="true" />
                {t("platformBehavior.documents.archive")}
              </Button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
