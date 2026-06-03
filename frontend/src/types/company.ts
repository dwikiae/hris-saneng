export type CompanyStatus = "active" | "inactive" | "archived";

export interface CompanyModuleSummary {
  code: string;
  name: string;
  version?: string | null;
  description?: string | null;
  isInstalled?: boolean;
  isActive?: boolean;
}

export interface PlatformCompany {
  id: number | string;
  name: string;
  legalName?: string | null;
  logoUrl?: string | null;
  logoPath?: string | null;
  tagline?: string | null;
  companyType?: string | null;
  industry?: string | null;
  foundedDate?: string | null;
  address?: string | null;
  city?: string | null;
  province?: string | null;
  postalCode?: string | null;
  phone?: string | null;
  email?: string | null;
  website?: string | null;
  hrPicName?: string | null;
  hrPicPhone?: string | null;
  hrPicEmail?: string | null;
  npwp?: string | null;
  nibOrSiup?: string | null;
  bpjsKetenagakerjaan?: string | null;
  bpjsKesehatan?: string | null;
  wlkpNumber?: string | null;
  directorName?: string | null;
  timezone?: string | null;
  dateFormat?: string | null;
  languageDefault?: "id" | "en" | null;
  smtpHost?: string | null;
  smtpPort?: string | number | null;
  smtpUsername?: string | null;
  smtpPasswordMasked?: string | null;
  smtpFromName?: string | null;
  smtpFromEmail?: string | null;
  loginLockoutAttempts?: number | null;
  loginLockoutMinutes?: number | null;
  sessionDurationHours?: number | null;
  employeeCount?: number | null;
  status?: CompanyStatus | string | null;
  activeModules?: CompanyModuleSummary[];
}

export interface CompanyListResponse {
  items: PlatformCompany[];
  meta?: {
    current_page?: number;
    per_page?: number;
    total?: number;
  };
}

export type CompanyPayload = Partial<Omit<PlatformCompany, "id" | "employeeCount" | "status">> & {
  activeModuleCodes?: string[];
};
