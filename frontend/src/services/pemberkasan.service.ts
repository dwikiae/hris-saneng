import { publicPath, requestJson, requestMultipart } from "@/services/api-client";
import type { DocumentItem, DocumentType, PemberkasanSession } from "@/types/pemberkasan";

export const pemberkasanService = {
  getPemberkasan: (token: string): Promise<PemberkasanSession> =>
    requestJson<PemberkasanSession>(publicPath(`/pemberkasan/${encodeURIComponent(token)}`)),

  uploadDocument: (token: string, documentType: DocumentType, file: File): Promise<DocumentItem> => {
    const formData = new FormData();
    formData.append("document_type", documentType);
    formData.append("file", file);

    return requestMultipart<DocumentItem>(
      publicPath(`/pemberkasan/${encodeURIComponent(token)}/upload`),
      formData
    );
  }
};
