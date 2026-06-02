export enum DocumentType {
  Ktp = "ktp",
  Kk = "kk",
  Npwp = "npwp",
  Rekening = "rekening",
  BpjsKesehatan = "bpjs_kesehatan",
  BpjsKetenagakerjaan = "bpjs_ketenagakerjaan"
}

export type DocumentStatus = "pending" | "uploading" | "done";

export interface DocumentItem {
  document_type: DocumentType;
  uploaded_at: string | null;
  file_path?: string | null;
  path?: string | null;
  file_name?: string | null;
  required?: boolean;
}

export interface PemberkasanSession {
  applicant_id: number;
  applicant_name?: string | null;
  position_name?: string | null;
  job_position_name?: string | null;
  position?: string | null;
  department_name?: string | null;
  department?: string | null;
  hrd_whatsapp_number?: string | null;
  hr_whatsapp_number?: string | null;
  token_expired_at?: string | null;
  expires_at?: string | null;
  documents: DocumentItem[];
}
