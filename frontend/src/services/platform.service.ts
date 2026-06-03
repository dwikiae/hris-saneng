import Pusher from "pusher-js";
import {
  privateHeaders,
  privatePath,
  requestPrivateBlob,
  requestPrivateJson,
  requestPrivateMultipart
} from "@/services/api-client";
import type {
  ApprovalTimelineItem,
  ChatActivityItem,
  ChatNoteItem,
  PlatformDocument,
  PlatformNotification,
  PlatformNotificationList
} from "@/types/platform";

export const notificationService = {
  list(perPage = 10): Promise<PlatformNotificationList> {
    return requestPrivateJson<PlatformNotificationList>(`/notifications?per_page=${perPage}`);
  },
  markRead(notificationId: number): Promise<PlatformNotification> {
    return requestPrivateJson<PlatformNotification>(`/notifications/${notificationId}/read`, {
      method: "POST"
    });
  },
  markAllRead(): Promise<{ updated: number }> {
    return requestPrivateJson<{ updated: number }>("/notifications/read-all", {
      method: "POST"
    });
  }
};

export interface ExportRequest {
  endpoint: string;
  format: "xlsx" | "pdf" | "csv";
  filters?: Record<string, string | number | boolean | null>;
  filename: string;
}

export const exportService = {
  async exportFile({ endpoint, format, filters = {}, filename }: ExportRequest): Promise<void> {
    const blob = await requestPrivateBlob(endpoint, {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({ format, filters, filename })
    });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");

    link.href = url;
    link.download = `${filename}.${format}`;
    link.click();
    window.URL.revokeObjectURL(url);
  }
};

export const documentService = {
  upload(endpoint: string, file: File, fieldName = "document"): Promise<PlatformDocument> {
    const formData = new FormData();
    formData.append(fieldName, file);

    return requestPrivateMultipart<PlatformDocument>(endpoint, formData);
  },
  async download(documentId: string | number): Promise<void> {
    const response = await requestPrivateJson<{ url: string }>(`/documents/${documentId}/download`);

    window.open(response.url, "_blank", "noopener,noreferrer");
  },
  archive(endpoint: string): Promise<null> {
    return requestPrivateJson<null>(endpoint, { method: "POST" });
  }
};

export const approvalService = {
  approve(endpoint: string): Promise<unknown> {
    return requestPrivateJson<unknown>(endpoint, { method: "POST" });
  },
  reject(endpoint: string, note: string): Promise<unknown> {
    return requestPrivateJson<unknown>(endpoint, {
      method: "POST",
      body: JSON.stringify({ note })
    });
  }
};

export const chatLogService = {
  activities(endpoint: string): Promise<{ items: ChatActivityItem[]; nextCursor?: string | null }> {
    return requestPrivateJson<{ items: ChatActivityItem[]; nextCursor?: string | null }>(endpoint);
  },
  notes(endpoint: string): Promise<{ items: ChatNoteItem[]; nextCursor?: string | null }> {
    return requestPrivateJson<{ items: ChatNoteItem[]; nextCursor?: string | null }>(endpoint);
  },
  createNote(endpoint: string, body: string): Promise<ChatNoteItem> {
    return requestPrivateJson<ChatNoteItem>(endpoint, {
      method: "POST",
      body: JSON.stringify({ body })
    });
  }
};

export function connectNotificationSocket(
  userId: number,
  onNotification: (notification: PlatformNotification) => void
): () => void {
  const key = process.env.NEXT_PUBLIC_PUSHER_APP_KEY;
  const host = process.env.NEXT_PUBLIC_PUSHER_HOST;
  const port = Number(process.env.NEXT_PUBLIC_PUSHER_PORT ?? 6001);

  if (!key || !host) {
    return () => undefined;
  }

  const pusher = new Pusher(key, {
    wsHost: host,
    wsPort: port,
    forceTLS: process.env.NEXT_PUBLIC_PUSHER_SCHEME === "https",
    enabledTransports: ["ws", "wss"],
    cluster: process.env.NEXT_PUBLIC_PUSHER_APP_CLUSTER ?? "mt1",
    authEndpoint: privatePath("/broadcasting/auth"),
    auth: {
      headers: privateHeaders()
    }
  });
  const channel = pusher.subscribe(`user.${userId}.notifications`);
  channel.bind("notification.created", onNotification);

  return () => {
    channel.unbind("notification.created", onNotification);
    pusher.unsubscribe(`user.${userId}.notifications`);
    pusher.disconnect();
  };
}

export function connectChatSocket(
  channelName: string,
  onNote: (note: ChatNoteItem) => void
): () => void {
  const key = process.env.NEXT_PUBLIC_PUSHER_APP_KEY;
  const host = process.env.NEXT_PUBLIC_PUSHER_HOST;

  if (!key || !host) {
    return () => undefined;
  }

  const pusher = new Pusher(key, {
    wsHost: host,
    wsPort: Number(process.env.NEXT_PUBLIC_PUSHER_PORT ?? 6001),
    forceTLS: process.env.NEXT_PUBLIC_PUSHER_SCHEME === "https",
    enabledTransports: ["ws", "wss"],
    cluster: process.env.NEXT_PUBLIC_PUSHER_APP_CLUSTER ?? "mt1"
  });
  const channel = pusher.subscribe(channelName);
  channel.bind("note.created", onNote);

  return () => {
    channel.unbind("note.created", onNote);
    pusher.unsubscribe(channelName);
    pusher.disconnect();
  };
}
