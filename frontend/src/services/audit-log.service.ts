import { requestPrivateBlob, requestPrivateJson } from "@/services/api-client";
import type { AuditLogFilters, AuditLogListResponse } from "@/types/settings-platform";

function queryString(filters: AuditLogFilters): string {
  const query = new URLSearchParams();

  Object.entries(filters).forEach(([key, value]) => {
    if (value !== undefined && value !== "") {
      query.set(
        key === "dateFrom" ? "date_from" : key === "dateTo" ? "date_to" : key === "perPage" ? "per_page" : key,
        String(value)
      );
    }
  });

  const serialized = query.toString();
  return serialized ? `?${serialized}` : "";
}

function downloadBlob(blob: Blob, filename: string): void {
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement("a");

  link.href = url;
  link.download = filename;
  link.click();
  window.URL.revokeObjectURL(url);
}

export const auditLogService = {
  list(filters: AuditLogFilters = {}): Promise<AuditLogListResponse> {
    return requestPrivateJson<AuditLogListResponse>(`/instance/audit${queryString(filters)}`);
  },
  async exportLogs(format: "xlsx" | "csv", filters: AuditLogFilters = {}): Promise<void> {
    const blob = await requestPrivateBlob("/instance/audit/export", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ format, filters })
    });

    downloadBlob(blob, `audit-log.${format}`);
  }
};
