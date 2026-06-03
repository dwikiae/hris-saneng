export type PlatformLanguage = "id" | "en";
export type PlatformDateFormat = "DD/MM/YYYY" | "MM/DD/YYYY" | "YYYY-MM-DD";
export type SmtpEncryption = "tls" | "ssl" | "none";

export interface PlatformConfig {
  timezone?: string;
  defaultLanguage?: PlatformLanguage;
  dateFormat?: PlatformDateFormat;
  sessionDurationHours?: number;
  autoLogoutExpired?: boolean;
  loginLockoutAttempts?: number;
  loginLockoutMinutes?: number;
  smtpHost?: string;
  smtpPort?: number;
  smtpEncryption?: SmtpEncryption;
  smtpUsername?: string;
  smtpPasswordMasked?: string;
  smtpFromName?: string;
  smtpFromEmail?: string;
}

export type PlatformConfigPayload = PlatformConfig & {
  smtpPassword?: string;
};

export interface AuditLogDiff {
  field: string;
  oldValue?: string | number | boolean | null;
  newValue?: string | number | boolean | null;
  isSensitive?: boolean;
}

export interface AuditLogItem {
  id: string | number;
  createdAt: string;
  actor?: string | null;
  action: string;
  module?: string | null;
  entity?: string | null;
  detail?: string | null;
  diffs?: AuditLogDiff[];
}

export interface AuditLogListResponse {
  items: AuditLogItem[];
  meta?: {
    total?: number;
    today?: number;
    this_week?: number;
    current_page?: number;
    per_page?: number;
  };
}

export interface AuditLogFilters {
  search?: string;
  actor?: string;
  module?: string;
  action?: string;
  dateFrom?: string;
  dateTo?: string;
  page?: number;
  perPage?: number;
}

export interface ModuleRegistryItem {
  code: string;
  name: string;
  version?: string;
  description?: string;
  icon?: string;
  dependencies?: string[];
  missingDependencies?: string[];
  isMandatory?: boolean;
  isInstalled?: boolean;
  status?: "installed" | "not_installed";
}
