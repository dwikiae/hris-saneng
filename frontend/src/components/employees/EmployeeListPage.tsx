"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { useMemo, useState } from "react";
import { useTranslation } from "react-i18next";
import { ConfirmDialog } from "@/components/platform/ConfirmDialog";
import { ExportButton } from "@/components/platform/ExportButton";
import { platformToast } from "@/components/platform/ToastProvider";
import {
  ArchiveBanner,
  EmployeeFilters,
  EmployeeMobileCard,
  employeeActions,
  employeeColumns,
  type ConfirmAction
} from "@/components/employees/EmployeeListParts";
import { EmployeeCompanyContextBar, useEmployeeCompanyContext } from "@/components/employees/EmployeeCompanyContextBar";
import { summaryItems } from "@/components/employees/employee-list-utils";
import { ListPageTemplate } from "@/components/templates";
import { employeeLookupService, employeeService } from "@/services/employee.service";

interface EmployeeListPageProps {
  archived?: boolean;
}

const defaultPerPage = 20;

export function EmployeeListPage({ archived = false }: EmployeeListPageProps) {
  const { t } = useTranslation("platform");
  const router = useRouter();
  const queryClient = useQueryClient();
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("");
  const [departmentId, setDepartmentId] = useState("");
  const [contractType, setContractType] = useState("");
  const [page, setPage] = useState(1);
  const [sortBy, setSortBy] = useState("name");
  const [sortDir, setSortDir] = useState<"asc" | "desc">("asc");
  const [selectedRowIds, setSelectedRowIds] = useState<string[]>([]);
  const [confirmAction, setConfirmAction] = useState<ConfirmAction>(null);
  const { activeCompanyId, hasCompanyContext } = useEmployeeCompanyContext();

  const params = { search, status, departmentId, contractType, page, perPage: defaultPerPage, sortBy, sortDir };
  const employeesQuery = useQuery({
    queryKey: ["employees", activeCompanyId, archived ? "archived" : "active", params],
    queryFn: () => (archived ? employeeService.listArchived(params) : employeeService.list(params)),
    enabled: hasCompanyContext
  });
  const departmentsQuery = useQuery({
    queryKey: ["employees", activeCompanyId, "departments"],
    queryFn: employeeLookupService.departments,
    enabled: hasCompanyContext
  });
  const employmentTypesQuery = useQuery({
    queryKey: ["employees", activeCompanyId, "employment-types"],
    queryFn: employeeLookupService.employmentTypes,
    enabled: hasCompanyContext
  });

  const employmentTypes = employmentTypesQuery.data ?? [];
  const employees = (employeesQuery.data?.items ?? []).filter((employee) =>
    archived ? Boolean(employee.archived_at) : !employee.archived_at
  );
  const summary = useMemo(() => summaryItems(employees, employeesQuery.data?.meta?.total, t), [employees, employeesQuery.data?.meta?.total, t]);
  const activeFilterCount = [status, departmentId, contractType].filter(Boolean).length;

  const archiveMutation = useMutation({
    mutationFn: employeeService.archive,
    onSuccess: async () => {
      platformToast.success(t("employeesList.toast.archived"));
      setSelectedRowIds([]);
      await queryClient.invalidateQueries({ queryKey: ["employees"] });
    },
    onError: () => platformToast.error(t("employeesList.toast.failed"))
  });
  const restoreMutation = useMutation({
    mutationFn: employeeService.restore,
    onSuccess: async () => {
      platformToast.success(t("employeesList.toast.restored"));
      setSelectedRowIds([]);
      await queryClient.invalidateQueries({ queryKey: ["employees"] });
    },
    onError: () => platformToast.error(t("employeesList.toast.failed"))
  });

  const filters = (
    <EmployeeFilters
      status={status}
      departmentId={departmentId}
      contractType={contractType}
      departments={departmentsQuery.data ?? []}
      onStatusChange={(value) => {
        setPage(1);
        setStatus(value);
      }}
      onDepartmentChange={(value) => {
        setPage(1);
        setDepartmentId(value);
      }}
      onContractTypeChange={(value) => {
        setPage(1);
        setContractType(value);
      }}
    />
  );

  return (
    <>
      <EmployeeCompanyContextBar />
      <ListPageTemplate
        title={t("employeesList.title")}
        breadcrumbs={[
          { label: t("employeesList.breadcrumb.dashboard"), href: "/dashboard" },
          { label: t("employeesList.breadcrumb.employees") }
        ]}
        actions={employeeActions(archived, selectedRowIds, { search, status, departmentId, contractType }, t)}
        banner={archived ? <ArchiveBanner /> : null}
        searchValue={search}
        searchPlaceholder={t("employeesList.searchPlaceholder")}
        onSearchChange={(value) => {
          setPage(1);
          setSearch(value);
        }}
        filters={filters}
        activeFilterCount={activeFilterCount}
        onResetFilters={() => {
          setStatus("");
          setDepartmentId("");
          setContractType("");
          setPage(1);
        }}
        summaryItems={summary}
        columns={employeeColumns({
          archived,
          employmentTypes,
          sortBy,
          sortDir,
          onSort: (key) => {
            setSortBy(key);
            setSortDir(sortBy === key && sortDir === "asc" ? "desc" : "asc");
          },
          onAction: setConfirmAction,
          onNavigate: (href) => router.push(href),
          t
        })}
        data={employees}
        getRowId={(employee) => String(employee.id)}
        selectedRowIds={selectedRowIds}
        onSelectionChange={setSelectedRowIds}
        bulkActions={[
          {
            id: "export-selected",
            label: t("employeesList.actions.exportSelected"),
            custom: (
              <ExportButton
                endpoint="/employees/export"
                filename="employees-selected"
                filters={{ selected_ids: selectedRowIds.join(",") }}
                formats={["xlsx", "csv"]}
              />
            )
          }
        ]}
        onRowClick={(employee) => router.push(`/dashboard/employees/${employee.id}`)}
        mobileCard={(employee) => <EmployeeMobileCard employee={employee} employmentTypes={employmentTypes} />}
        emptyState={{
          title: archived ? t("employeesList.empty.archivedTitle") : t("employeesList.empty.title"),
          description: archived ? t("employeesList.empty.archivedDescription") : t("employeesList.empty.description")
        }}
        isLoading={employeesQuery.isLoading}
        error={employeesQuery.isError ? new Error(t("employeesList.api.notReady")) : null}
        pagination={{
          page,
          perPage: employeesQuery.data?.meta?.per_page ?? defaultPerPage,
          total: archived ? employees.length : employeesQuery.data?.meta?.total ?? employees.length,
          onPageChange: setPage
        }}
      />
      <ConfirmDialog
        open={Boolean(confirmAction)}
        onOpenChange={(open) => !open && setConfirmAction(null)}
        title={confirmAction ? t(`employeesList.confirm.${confirmAction.type}.title`, { name: confirmAction.employee.name }) : ""}
        description={confirmAction ? t(`employeesList.confirm.${confirmAction.type}.description`) : ""}
        confirmLabel={confirmAction ? t(`employeesList.actions.${confirmAction.type}`) : ""}
        confirmVariant={confirmAction?.type === "archive" ? "danger" : "warning"}
        onConfirm={() => {
          if (!confirmAction) {
            return;
          }
          if (confirmAction.type === "archive") {
            archiveMutation.mutate(confirmAction.employee.id);
          } else {
            restoreMutation.mutate(confirmAction.employee.id);
          }
        }}
      />
    </>
  );
}
