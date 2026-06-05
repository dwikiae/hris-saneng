import type { ReactNode } from "react";
import { StatusBadge } from "@/components/shared/StatusBadge";
import type {
  EmployeeContract,
  EmployeeContractStatus,
  EmployeeDetail,
  EmployeeLookup,
  EmployeeStatus
} from "@/types/employee";

export function display(value: ReactNode | null | undefined): ReactNode {
  if (value === null || value === undefined || value === "") {
    return "-";
  }

  return value;
}

export function formatDate(value?: string | null): string {
  if (!value) {
    return "-";
  }

  return new Intl.DateTimeFormat("id-ID", { day: "2-digit", month: "short", year: "numeric" }).format(new Date(value));
}

export function initials(name: string): string {
  return name
    .split(" ")
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join("")
    .toUpperCase();
}

export function lookupName(value?: EmployeeLookup | null): string {
  return value?.name ?? value?.code ?? "-";
}

export function statusTone(status?: EmployeeStatus | null) {
  if (status === "active" || status === "approved") {
    return "success" as const;
  }
  if (status === "pending" || status === "probation" || status === "draft") {
    return "warning" as const;
  }
  if (status === "rejected") {
    return "danger" as const;
  }
  return "neutral" as const;
}

export function contractTone(status?: EmployeeContractStatus | null) {
  if (status === "active") {
    return "success" as const;
  }
  if (status === "draft") {
    return "warning" as const;
  }
  if (status === "terminated") {
    return "danger" as const;
  }
  return "neutral" as const;
}

export function contractTypeLabel(contract?: EmployeeContract | null): string {
  if (!contract?.contract_type) {
    return "-";
  }

  return contract.contract_type.toUpperCase();
}

export function activeContract(contracts: EmployeeContract[]): EmployeeContract | null {
  return contracts.find((contract) => contract.status === "active") ?? contracts[0] ?? null;
}

export function employeeSubtitle(employee: EmployeeDetail): string {
  return [lookupName(employee.position), lookupName(employee.department)].filter((value) => value !== "-").join(" - ");
}

export function EmployeeStatusBadge({ status, label }: { status?: EmployeeStatus | null; label: string }) {
  return <StatusBadge label={label} tone={statusTone(status)} />;
}
