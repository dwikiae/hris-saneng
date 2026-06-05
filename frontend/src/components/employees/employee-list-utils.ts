import type { EmployeeListItem, MasterDataOption } from "@/types/employee";

export function summaryItems(employees: EmployeeListItem[], total: number | undefined, t: (key: string) => string) {
  return [
    { label: t("employeesList.summary.total"), value: total ?? employees.length },
    { label: t("employeesList.summary.active"), value: employees.filter((employee) => employee.status === "active" || employee.status === "approved").length },
    { label: t("employeesList.summary.probation"), value: employees.filter((employee) => employee.status === "probation").length },
    { label: t("employeesList.summary.pending"), value: employees.filter((employee) => employee.status === "pending").length }
  ];
}

export function contractLabel(employee: EmployeeListItem, employmentTypes: MasterDataOption[]): string {
  const relation = employee.employmentType ?? employee.employment_type;
  const lookup = employmentTypes.find((type) => String(type.id) === String(employee.employment_type_id));
  return relation?.name ?? lookup?.name ?? "-";
}

export function statusLabel(status: string | null | undefined, t: (key: string) => string): string {
  const key = status && ["active", "approved", "pending", "probation", "inactive", "draft", "rejected"].includes(status)
    ? status
    : "unknown";
  return t(`employeesList.status.${key}`);
}

export function statusTone(status: string | null | undefined) {
  if (status === "active" || status === "approved") {
    return "success" as const;
  }
  if (status === "pending" || status === "probation") {
    return "warning" as const;
  }
  if (status === "rejected" || status === "inactive") {
    return "danger" as const;
  }
  return "neutral" as const;
}

export function formatDate(value?: string | null): string {
  if (!value) {
    return "-";
  }

  return new Intl.DateTimeFormat("id-ID", { dateStyle: "medium" }).format(new Date(value));
}
