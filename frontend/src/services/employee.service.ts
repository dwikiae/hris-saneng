import { privateHeaders, privatePath, requestPrivateJson } from "@/services/api-client";
import type { ApiResponse } from "@/types/api";
import type {
  EmployeeListItem,
  EmployeeListParams,
  EmployeeListResponse,
  MasterDataOption
} from "@/types/employee";

interface LaravelPaginator<T> {
  data: T[];
  current_page?: number;
  per_page?: number;
  total?: number;
}

function queryString(params: EmployeeListParams): string {
  const query = new URLSearchParams();

  Object.entries(params).forEach(([key, value]) => {
    if (value === undefined || value === "") {
      return;
    }

    const apiKey =
      key === "departmentId"
        ? "department_id"
        : key === "contractType"
          ? "contract_type"
          : key === "perPage"
            ? "per_page"
            : key === "sortBy"
              ? "sort_by"
              : key === "sortDir"
                ? "sort_dir"
                : key;

    query.set(apiKey, String(value));
  });

  const serialized = query.toString();
  return serialized ? `?${serialized}` : "";
}

async function requestEmployeeList(path: string): Promise<EmployeeListResponse> {
  const response = await fetch(privatePath(path), {
    headers: {
      Accept: "application/json",
      ...privateHeaders()
    }
  });
  const payload = (await response.json()) as ApiResponse<LaravelPaginator<EmployeeListItem>>;

  if (!response.ok || !payload.success || !payload.data) {
    throw new Error(payload.message);
  }

  return {
    items: payload.data.data ?? [],
    meta: {
      current_page: payload.data.current_page,
      per_page: payload.data.per_page,
      total: payload.data.total
    }
  };
}

export const employeeService = {
  list(params: EmployeeListParams = {}): Promise<EmployeeListResponse> {
    return requestEmployeeList(`/employees${queryString(params)}`);
  },
  listArchived(params: EmployeeListParams = {}): Promise<EmployeeListResponse> {
    return requestEmployeeList(`/employees${queryString({ ...params, archived: true })}`);
  },
  archive(employeeId: string | number): Promise<null> {
    return requestPrivateJson<null>(`/employees/${employeeId}/archive`, { method: "POST" });
  },
  restore(employeeId: string | number): Promise<EmployeeListItem> {
    return requestPrivateJson<EmployeeListItem>(`/archive/employees/${employeeId}/restore`, { method: "POST" });
  }
};

export const employeeLookupService = {
  departments(): Promise<MasterDataOption[]> {
    return requestPrivateJson<MasterDataOption[]>("/master-data/departments?is_active=1");
  },
  employmentTypes(): Promise<MasterDataOption[]> {
    return requestPrivateJson<MasterDataOption[]>("/master-data/employment-types?is_active=1");
  }
};
