import {
  DocumentType,
  type DocumentItem,
  type PemberkasanSession,
} from "@/types/pemberkasan";

export const requiredDocumentTypes: DocumentType[] = [
  DocumentType.Ktp,
  DocumentType.Kk,
  DocumentType.Npwp,
  DocumentType.Rekening,
];

const allDocumentTypes: DocumentType[] = [
  ...requiredDocumentTypes,
  DocumentType.BpjsKesehatan,
  DocumentType.BpjsKetenagakerjaan,
];

export function normalizeDocuments(documents: DocumentItem[]): DocumentItem[] {
  const byType = new Map(
    documents.map((document) => [document.document_type, document]),
  );

  return allDocumentTypes.map((documentType) => ({
    document_type: documentType,
    uploaded_at: byType.get(documentType)?.uploaded_at ?? null,
    file_path: byType.get(documentType)?.file_path ?? null,
    path: byType.get(documentType)?.path ?? null,
    file_name: byType.get(documentType)?.file_name ?? null,
    required: requiredDocumentTypes.includes(documentType),
  }));
}

export function isDocumentDone(document: DocumentItem): boolean {
  return Boolean(document.uploaded_at || document.file_path || document.path);
}

export function completedRequiredCount(documents: DocumentItem[]): number {
  return documents.filter(
    (document) => document.required && isDocumentDone(document),
  ).length;
}

export function getPosition(session: PemberkasanSession): string | null {
  return (
    session.position_name ??
    session.job_position_name ??
    session.position ??
    null
  );
}

export function getDepartment(session: PemberkasanSession): string | null {
  return session.department_name ?? session.department ?? null;
}

export function getWhatsappNumber(session: PemberkasanSession): string | null {
  return session.hrd_whatsapp_number ?? session.hr_whatsapp_number ?? null;
}

export function getExpiredAt(session: PemberkasanSession): string | null {
  return session.token_expired_at ?? session.expires_at ?? null;
}

export function formatCountdown(
  expiredAt: string | null,
  currentTime: number,
  fallback: string,
): string {
  if (!expiredAt) {
    return fallback;
  }

  const diff = new Date(expiredAt).getTime() - currentTime;

  if (!Number.isFinite(diff) || diff <= 0) {
    return fallback;
  }

  const totalMinutes = Math.ceil(diff / 60000);
  const days = Math.floor(totalMinutes / 1440);
  const hours = Math.floor((totalMinutes % 1440) / 60);
  const minutes = totalMinutes % 60;

  return [days, hours, minutes]
    .map((value) => String(value).padStart(2, "0"))
    .join(":");
}
