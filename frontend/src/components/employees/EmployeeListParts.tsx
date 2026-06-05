import { Archive, ArrowDown, ArrowUp, Edit, Eye, MoreVertical, Plus, RotateCcw } from "lucide-react";
import Link from "next/link";
import { useTranslation } from "react-i18next";
import { ExportButton } from "@/components/platform/ExportButton";
import { PermissionGate } from "@/components/platform/PermissionGate";
import { AvatarWithInfo } from "@/components/shared/AvatarWithInfo";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger
} from "@/components/ui/dropdown-menu";
import type { ListColumn } from "@/components/templates";
import type { EmployeeListItem, MasterDataOption } from "@/types/employee";
import { contractLabel, formatDate, statusLabel, statusTone } from "./employee-list-utils";

export type ConfirmAction = { type: "archive" | "restore"; employee: EmployeeListItem } | null;

export function employeeActions(
  archived: boolean,
  selectedRowIds: string[],
  filters: Record<string, string>,
  t: (key: string) => string
) {
  const exportButton = (
    <ExportButton
      endpoint="/employees/export"
      filename={archived ? "employees-archived" : "employees"}
      filters={{ ...filters, archived }}
      formats={["xlsx", "csv"]}
    />
  );

  if (archived) {
    return [{ id: "export", label: t("platformBehavior.export.label"), custom: exportButton }];
  }

  return [
    { id: "archive", label: t("employeesList.actions.viewArchive"), href: "/dashboard/employees/archived" },
    { id: "export", label: t("platformBehavior.export.label"), custom: exportButton },
    {
      id: "new",
      label: t("employeesList.actions.new"),
      custom: (
        <PermissionGate permission="employee.create">
          <Button asChild>
            <Link href="/dashboard/employees/new">
              <Plus className="mr-2 h-4 w-4" aria-hidden="true" />
              {t("employeesList.actions.new")}
            </Link>
          </Button>
        </PermissionGate>
      ),
      disabled: selectedRowIds.length > 0
    }
  ];
}

export function employeeColumns(config: {
  archived: boolean;
  employmentTypes: MasterDataOption[];
  sortBy: string;
  sortDir: "asc" | "desc";
  onSort: (key: string) => void;
  onAction: (action: ConfirmAction) => void;
  onNavigate: (href: string) => void;
  t: (key: string) => string;
}): Array<ListColumn<EmployeeListItem>> {
  const base: Array<ListColumn<EmployeeListItem>> = [
    {
      key: "name",
      header: <SortButton label={config.t("employeesList.columns.name")} field="name" activeField={config.sortBy} direction={config.sortDir} onSort={config.onSort} />,
      cell: (employee) => <AvatarWithInfo name={employee.name} subtitle={employee.email ?? employee.position?.name} imageUrl={employee.avatarUrl ?? employee.photo_url} />
    },
    {
      key: "employee_number",
      header: <SortButton label={config.t("employeesList.columns.employeeId")} field="employee_number" activeField={config.sortBy} direction={config.sortDir} onSort={config.onSort} />,
      cell: (employee) => employee.employee_number ?? "-"
    },
    { key: "position", header: config.t("employeesList.columns.position"), cell: (employee) => employee.position?.name ?? "-" },
    { key: "department", header: config.t("employeesList.columns.department"), cell: (employee) => employee.department?.name ?? "-" },
    {
      key: "contract",
      header: config.t("employeesList.columns.contractType"),
      cell: (employee) => <StatusBadge label={contractLabel(employee, config.employmentTypes)} tone="neutral" />
    },
    {
      key: "status",
      header: config.t("employeesList.columns.status"),
      cell: (employee) => <StatusBadge label={statusLabel(employee.status, config.t)} tone={statusTone(employee.status)} />
    }
  ];

  if (config.archived) {
    base.push(
      { key: "archived_by", header: config.t("employeesList.columns.archivedBy"), cell: (employee) => employee.archived_by ?? "-" },
      { key: "archived_at", header: config.t("employeesList.columns.archivedAt"), cell: (employee) => formatDate(employee.archived_at) }
    );
  }

  base.push({
    key: "actions",
    header: config.t("employeesList.columns.actions"),
    className: "w-16",
    cell: (employee) => <EmployeeRowActions employee={employee} config={config} />
  });

  return base;
}

export function EmployeeFilters(props: {
  status: string;
  departmentId: string;
  contractType: string;
  departments: MasterDataOption[];
  onStatusChange: (value: string) => void;
  onDepartmentChange: (value: string) => void;
  onContractTypeChange: (value: string) => void;
}) {
  const { t } = useTranslation("platform");

  return (
    <>
      <select className="h-9 rounded-md border border-input bg-background px-3 text-sm" value={props.status} onChange={(event) => props.onStatusChange(event.target.value)}>
        <option value="">{t("employeesList.filters.statusAll")}</option>
        <option value="active">{t("employeesList.status.active")}</option>
        <option value="probation">{t("employeesList.status.probation")}</option>
        <option value="pending">{t("employeesList.status.pending")}</option>
        <option value="inactive">{t("employeesList.status.inactive")}</option>
      </select>
      <select className="h-9 rounded-md border border-input bg-background px-3 text-sm" value={props.departmentId} onChange={(event) => props.onDepartmentChange(event.target.value)}>
        <option value="">{t("employeesList.filters.departmentAll")}</option>
        {props.departments.map((department) => (
          <option key={department.id} value={department.id}>{department.name}</option>
        ))}
      </select>
      <select className="h-9 rounded-md border border-input bg-background px-3 text-sm" value={props.contractType} onChange={(event) => props.onContractTypeChange(event.target.value)}>
        <option value="">{t("employeesList.filters.contractAll")}</option>
        <option value="PKWT">{t("employeesList.contract.pkwt")}</option>
        <option value="PKWTT">{t("employeesList.contract.pkwtt")}</option>
      </select>
    </>
  );
}

export function EmployeeMobileCard({ employee, employmentTypes }: { employee: EmployeeListItem; employmentTypes: MasterDataOption[] }) {
  const { t } = useTranslation("platform");

  return (
    <div className="space-y-3">
      <div className="flex items-start justify-between gap-3">
        <AvatarWithInfo name={employee.name} subtitle={employee.employee_number ?? "-"} imageUrl={employee.avatarUrl ?? employee.photo_url} />
        <StatusBadge label={statusLabel(employee.status, t)} tone={statusTone(employee.status)} />
      </div>
      <div className="space-y-1 text-sm text-muted-foreground">
        <p>{employee.position?.name ?? "-"}</p>
        <p>{contractLabel(employee, employmentTypes)}</p>
      </div>
    </div>
  );
}

export function ArchiveBanner() {
  const { t } = useTranslation("platform");
  return <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">{t("employeesList.archiveMode")}</div>;
}

function SortButton(props: {
  label: string;
  field: string;
  activeField: string;
  direction: "asc" | "desc";
  onSort: (field: string) => void;
}) {
  const active = props.activeField === props.field;

  return (
    <button type="button" className="inline-flex items-center gap-1 font-medium" onClick={() => props.onSort(props.field)}>
      {props.label}
      {active ? props.direction === "asc" ? <ArrowUp className="h-3 w-3" /> : <ArrowDown className="h-3 w-3" /> : null}
    </button>
  );
}

function EmployeeRowActions({
  employee,
  config
}: {
  employee: EmployeeListItem;
  config: Parameters<typeof employeeColumns>[0];
}) {
  return (
    <div onClick={(event) => event.stopPropagation()}>
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button type="button" size="icon" variant="ghost" aria-label={config.t("employeesList.actions.openMenu")}>
            <MoreVertical className="h-4 w-4" aria-hidden="true" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem onClick={() => config.onNavigate(`/dashboard/employees/${employee.id}`)}>
            <Eye className="h-4 w-4" aria-hidden="true" />
            {config.t("employeesList.actions.detail")}
          </DropdownMenuItem>
          {!config.archived ? (
            <>
              <DropdownMenuItem onClick={() => config.onNavigate(`/dashboard/employees/${employee.id}/edit`)}>
                <Edit className="h-4 w-4" aria-hidden="true" />
                {config.t("employeesList.actions.edit")}
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => config.onAction({ type: "archive", employee })}>
                <Archive className="h-4 w-4" aria-hidden="true" />
                {config.t("employeesList.actions.archive")}
              </DropdownMenuItem>
            </>
          ) : (
            <DropdownMenuItem onClick={() => config.onAction({ type: "restore", employee })}>
              <RotateCcw className="h-4 w-4" aria-hidden="true" />
              {config.t("employeesList.actions.restore")}
            </DropdownMenuItem>
          )}
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
}
