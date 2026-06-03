export type PlatformLanguage = "id" | "en";

export interface PlatformNotification {
  id: number;
  type: string;
  title_key: string;
  body_key: string;
  data: Record<string, string | number | boolean | null>;
  read_at: string | null;
  created_at: string | null;
}

export interface PlatformNotificationList {
  items: PlatformNotification[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
  };
}

export interface PlatformDocument {
  id: string | number;
  name: string;
  mimeType: string;
  sizeBytes: number;
  uploadedBy?: string;
  uploadedAt?: string;
  signedUrl?: string;
}

export interface ApprovalTimelineItem {
  id: string | number;
  actor: string;
  action: string;
  createdAt: string;
  note?: string;
}

export interface ChatActivityItem {
  id: string | number;
  actor: string;
  action: string;
  createdAt: string;
  description?: string;
  changes?: Array<{ field: string; oldValue?: string; newValue?: string; sensitive?: boolean }>;
}

export interface ChatNoteItem {
  id: string | number;
  actor: string;
  body: string;
  createdAt: string;
}
