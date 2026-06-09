import { requestPrivateJson } from "@/services/api-client";
import type {
  EmployeeMasterKind,
  EmployeeMasterListParams,
  EmployeeMasterPayload,
  EmployeeMasterRecord
} from "@/types/employee-settings";

function queryString(params: EmployeeMasterListParams): string {
  const query = new URLSearchParams();

  if (params.search) {
    query.set("search", params.search);
  }
  if (params.isActive !== null && params.isActive !== undefined) {
    query.set("is_active", params.isActive ? "1" : "0");
  }
  if (params.departmentId !== null && params.departmentId !== undefined && params.departmentId !== "") {
    query.set("department_id", String(params.departmentId));
  }

  const serialized = query.toString();
  return serialized ? `?${serialized}` : "";
}

function masterPath(company: string | number, kind: EmployeeMasterKind, suffix = ""): string {
  return `/${company}/employees/master/${kind}${suffix}`;
}

export const employeeMasterService = {
  list(
    company: string | number,
    kind: EmployeeMasterKind,
    params: EmployeeMasterListParams = {}
  ): Promise<EmployeeMasterRecord[]> {
    return requestPrivateJson<EmployeeMasterRecord[]>(masterPath(company, kind, queryString(params)));
  },
  create(
    company: string | number,
    kind: EmployeeMasterKind,
    payload: EmployeeMasterPayload
  ): Promise<EmployeeMasterRecord> {
    return requestPrivateJson<EmployeeMasterRecord>(masterPath(company, kind), {
      method: "POST",
      body: JSON.stringify(payload)
    });
  },
  update(
    company: string | number,
    kind: EmployeeMasterKind,
    id: string | number,
    payload: EmployeeMasterPayload
  ): Promise<EmployeeMasterRecord> {
    const method = kind === "employee-levels" || kind === "work-locations" ? "PUT" : "PATCH";

    return requestPrivateJson<EmployeeMasterRecord>(masterPath(company, kind, `/${id}`), {
      method,
      body: JSON.stringify(payload)
    });
  },
  archive(company: string | number, kind: EmployeeMasterKind, id: string | number): Promise<null> {
    const method = kind === "employee-levels" || kind === "work-locations" ? "POST" : "PATCH";

    return requestPrivateJson<null>(masterPath(company, kind, `/${id}/archive`), { method });
  },
  restore(company: string | number, kind: EmployeeMasterKind, id: string | number): Promise<null> {
    const method = kind === "employee-levels" || kind === "work-locations" ? "POST" : "PATCH";

    return requestPrivateJson<null>(masterPath(company, kind, `/${id}/restore`), { method });
  }
};
