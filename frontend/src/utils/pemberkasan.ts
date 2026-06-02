import { DocumentType, type DocumentItem, type PemberkasanSession } from "@/types/pemberkasan";

export const requiredDocumentTypes = [
  DocumentType.Ktp,
  DocumentType.Kk,
  DocumentType.Npwp,
  DocumentType.Rekening,
  DocumentType.BpjsKesehatan,
  DocumentType.BpjsKetenagakerjaan
];

export function normalizeDocuments(documents: DocumentItem[]): DocumentItem[] {
  return requiredDocumentTypes.map((documentType) => {
    const document = documents.find((item) => item.document_type === documentType);

    return document ?? { document_type: documentType, uploaded_at: null, required: true };
  });
}

export function completedRequiredCount(documents: DocumentItem[]): number {
  return documents.filter((document) => requiredDocumentTypes.includes(document.document_type) && Boolean(document.uploaded_at)).length;
}

export function getExpiredAt(session: PemberkasanSession): Date | null {
  const value = session.token_expired_at ?? session.expires_at;

  return value ? new Date(value) : null;
}

export function formatCountdown(expiredAt: Date | null, now: number, fallback: string): string {
  if (!expiredAt) {
    return fallback;
  }

  const remainingSeconds = Math.max(0, Math.floor((expiredAt.getTime() - now) / 1000));
  const hours = Math.floor(remainingSeconds / 3600);
  const minutes = Math.floor((remainingSeconds % 3600) / 60);

  return `${hours}h ${minutes}m`;
}
