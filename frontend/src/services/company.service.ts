import { requestPrivateJson } from "@/services/api-client";
import type {
  CompanyListResponse,
  CompanyModuleSummary,
  CompanyPayload,
  PlatformCompany
} from "@/types/company";

interface CompanyListParams {
  search?: string;
  page?: number;
  perPage?: number;
}

function queryString(params: CompanyListParams): string {
  const query = new URLSearchParams();

  if (params.search) {
    query.set("search", params.search);
  }
  if (params.page) {
    query.set("page", String(params.page));
  }
  if (params.perPage) {
    query.set("per_page", String(params.perPage));
  }

  const serialized = query.toString();
  return serialized ? `?${serialized}` : "";
}

export const companyService = {
  list(params: CompanyListParams = {}): Promise<CompanyListResponse> {
    return requestPrivateJson<CompanyListResponse>(`/instance/companies${queryString(params)}`);
  },
  detail(companyId: string | number): Promise<PlatformCompany> {
    return requestPrivateJson<PlatformCompany>(`/instance/companies/${companyId}`);
  },
  create(payload: CompanyPayload): Promise<PlatformCompany> {
    return requestPrivateJson<PlatformCompany>("/instance/companies", {
      method: "POST",
      body: JSON.stringify(payload)
    });
  },
  update(companyId: string | number, payload: CompanyPayload): Promise<PlatformCompany> {
    return requestPrivateJson<PlatformCompany>(`/instance/companies/${companyId}`, {
      method: "PUT",
      body: JSON.stringify(payload)
    });
  },
  archive(companyId: string | number): Promise<null> {
    return requestPrivateJson<null>(`/instance/companies/${companyId}`, {
      method: "DELETE"
    });
  },
  modules(): Promise<CompanyModuleSummary[]> {
    return requestPrivateJson<CompanyModuleSummary[]>("/instance/modules");
  }
};
