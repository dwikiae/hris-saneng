"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { useTranslation } from "react-i18next";
import { PemberkasanContent } from "@/components/modules/recruitment/pemberkasan/PemberkasanContent";
import { UploadModal } from "@/components/modules/recruitment/pemberkasan/UploadModal";
import { pemberkasanService } from "@/services/pemberkasan.service";
import { DocumentType, type DocumentItem, type PemberkasanSession } from "@/types/pemberkasan";
import { completedRequiredCount, formatCountdown, getExpiredAt, normalizeDocuments, requiredDocumentTypes } from "@/utils/pemberkasan";

type PageState = "loading" | "error" | "active" | "complete";

interface PemberkasanPageProps {
  token: string;
}

export function PemberkasanPage({ token }: PemberkasanPageProps) {
  const { t } = useTranslation("pemberkasan");
  const [pageState, setPageState] = useState<PageState>("loading");
  const [session, setSession] = useState<PemberkasanSession | null>(null);
  const [selectedDocument, setSelectedDocument] = useState<DocumentItem | null>(null);
  const [uploadingType, setUploadingType] = useState<DocumentType | null>(null);
  const [uploadError, setUploadError] = useState(false);
  const [now, setNow] = useState(Date.now());

  const loadSession = useCallback(async () => {
    const data = await pemberkasanService.getPemberkasan(token);
    const documents = normalizeDocuments(data.documents);
    const completedRequired = completedRequiredCount(documents);

    setSession({ ...data, documents });
    setPageState(completedRequired === requiredDocumentTypes.length ? "complete" : "active");
  }, [token]);

  useEffect(() => {
    loadSession().catch(() => setPageState("error"));
  }, [loadSession]);

  useEffect(() => {
    const timer = window.setInterval(() => setNow(Date.now()), 30000);
    return () => window.clearInterval(timer);
  }, []);

  const progress = useMemo(() => {
    const uploaded = completedRequiredCount(session?.documents ?? []);
    return {
      uploaded,
      total: requiredDocumentTypes.length,
      percentage: Math.round((uploaded / requiredDocumentTypes.length) * 100)
    };
  }, [session]);

  const handleUpload = async (file: File) => {
    if (!selectedDocument) {
      return;
    }

    setUploadError(false);
    setUploadingType(selectedDocument.document_type);

    try {
      await pemberkasanService.uploadDocument(token, selectedDocument.document_type, file);
      setSelectedDocument(null);
      await loadSession();
    } catch {
      setUploadError(true);
    } finally {
      setUploadingType(null);
    }
  };

  const expiredAt = session ? getExpiredAt(session) : null;
  const countdown = useMemo(() => formatCountdown(expiredAt, now, t("countdown.unavailable")), [expiredAt, now, t]);

  return (
    <main className="min-h-screen bg-slate-50 text-slate-950">
      <div className="mx-auto flex min-h-screen w-full max-w-5xl flex-col px-4 py-6 sm:px-6 lg:px-8">
        <header className="mb-6 border-b border-slate-200 pb-5">
          <p className="text-sm font-medium uppercase tracking-wide text-slate-500">{t("header.eyebrow")}</p>
          <h1 className="mt-1 text-2xl font-semibold text-slate-950 sm:text-3xl">{t("header.title")}</h1>
        </header>
        <PemberkasanContent
          pageState={pageState}
          session={session}
          progress={progress}
          countdown={countdown}
          uploadingType={uploadingType}
          onSelectDocument={setSelectedDocument}
        />
      </div>
      {selectedDocument ? (
        <UploadModal
          document={selectedDocument}
          isUploading={uploadingType === selectedDocument.document_type}
          onClose={() => {
            if (!uploadingType) {
              setSelectedDocument(null);
              setUploadError(false);
            }
          }}
          onUpload={handleUpload}
        />
      ) : null}
      {uploadError ? (
        <div className="fixed bottom-4 left-4 right-4 z-50 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 shadow-lg sm:left-auto sm:max-w-md" role="alert">
          {t("upload.failed")}
        </div>
      ) : null}
    </main>
  );
}
