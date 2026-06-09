"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Archive, Edit, Plus } from "lucide-react";
import { useState } from "react";
import { useTranslation } from "react-i18next";
import { ConfirmDialog } from "@/components/platform/ConfirmDialog";
import { platformToast } from "@/components/platform/ToastProvider";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { ListPageTemplate, type ListColumn } from "@/components/templates";
import { Button } from "@/components/ui/button";
import { employeeMasterService } from "@/services/employee-master.service";
import type {
  EmployeeMasterPayload,
  EmployeeMasterRecord
} from "@/types/employee-settings";
import type { EmployeeSettingsSection } from "./settings-sections";
import { EmployeeMasterModal } from "./EmployeeMasterModal";

export function EmployeeMasterSection({
  company,
  section
}: {
  company: string;
  section: EmployeeSettingsSection;
}) {
  const { t } = useTranslation("platform");
  const queryClient = useQueryClient();
  const [search, setSearch] = useState("");
  const [activeFilter, setActiveFilter] = useState<"all" | "active" | "inactive">("all");
  const [editingRecord, setEditingRecord] = useState<EmployeeMasterRecord | null>(null);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [archiveTarget, setArchiveTarget] = useState<EmployeeMasterRecord | null>(null);
  const entityLabel = t(`employeesSettings.entities.${section.entityKey}`);
  const isReady = Boolean(section.masterKind);
  const isActive = activeFilter === "all" ? null : activeFilter === "active";

  const query = useQuery({
    queryKey: ["employees", "settings", company, section.masterKind, search, isActive],
    queryFn: () => employeeMasterService.list(company, section.masterKind!, { search, isActive }),
    enabled: isReady
  });

  const mutation = useMutation({
    mutationFn: (payload: EmployeeMasterPayload) =>
      editingRecord
        ? employeeMasterService.update(company, section.masterKind!, editingRecord.id, payload)
        : employeeMasterService.create(company, section.masterKind!, payload),
    onSuccess: () => {
      setIsModalOpen(false);
      setEditingRecord(null);
      queryClient.invalidateQueries({ queryKey: ["employees", "settings", company, section.masterKind] });
      platformToast.success(t(editingRecord ? "employeesSettings.toast.updated" : "employeesSettings.toast.created"));
    },
    onError: (error) => platformToast.error(error instanceof Error ? error.message : t("employeesSettings.toast.failed"))
  });

  const archiveMutation = useMutation({
    mutationFn: (record: EmployeeMasterRecord) => employeeMasterService.archive(company, section.masterKind!, record.id),
    onSuccess: () => {
      setArchiveTarget(null);
      queryClient.invalidateQueries({ queryKey: ["employees", "settings", company, section.masterKind] });
      platformToast.success(t("employeesSettings.toast.archived"));
    },
    onError: (error) => platformToast.error(error instanceof Error ? error.message : t("employeesSettings.toast.failed"))
  });

  const data = isReady ? query.data ?? [] : [];
  const columns = columnsFor(section, t, (record) => {
    setEditingRecord(record);
    setIsModalOpen(true);
  }, setArchiveTarget);

  return (
    <>
      <ListPageTemplate
        title={t(section.labelKey)}
        description={t("employeesSettings.master.description", { entity: entityLabel })}
        searchValue={search}
        searchPlaceholder={t("employeesSettings.master.searchPlaceholder")}
        onSearchChange={setSearch}
        filters={
          <select
            className="h-9 rounded-md border border-input bg-background px-3 text-sm"
            value={activeFilter}
            onChange={(event) => setActiveFilter(event.target.value as "all" | "active" | "inactive")}
            disabled={!isReady}
          >
            <option value="all">{t("employeesSettings.filters.all")}</option>
            <option value="active">{t("employeesSettings.filters.active")}</option>
            <option value="inactive">{t("employeesSettings.filters.inactive")}</option>
          </select>
        }
        activeFilterCount={activeFilter === "all" ? 0 : 1}
        onResetFilters={() => setActiveFilter("all")}
        actions={[
          {
            id: "new",
            label: t("employeesSettings.actions.new", { entity: entityLabel }),
            icon: <Plus className="h-4 w-4" />,
            variant: "primary",
            disabled: !isReady,
            onClick: () => {
              setEditingRecord(null);
              setIsModalOpen(true);
            }
          }
        ]}
        showSummaryStrip={false}
        columns={columns}
        data={data}
        getRowId={(record) => String(record.id)}
        mobileCard={(record) => (
          <div className="space-y-2">
            <p className="font-medium text-foreground">{record.name}</p>
            <p className="text-sm text-muted-foreground">{record.code}</p>
            <StatusBadge label={statusLabel(record.isActive, t)} tone={record.isActive ? "success" : "neutral"} />
          </div>
        )}
        emptyState={{
          title: isReady ? t("employeesSettings.empty.title") : t("employeesSettings.api.notReadyTitle"),
          description: isReady ? t("employeesSettings.empty.description") : t("employeesSettings.api.notReadyDescription")
        }}
        isLoading={isReady && query.isLoading}
        error={query.error instanceof Error ? query.error : null}
      />

      {section.masterKind ? (
        <EmployeeMasterModal
          open={isModalOpen}
          company={company}
          kind={section.masterKind}
          entityLabel={entityLabel}
          record={editingRecord}
          isSubmitting={mutation.isPending}
          onOpenChange={setIsModalOpen}
          onSubmit={(payload) => mutation.mutate(payload)}
        />
      ) : null}

      <ConfirmDialog
        open={Boolean(archiveTarget)}
        onOpenChange={(open) => !open && setArchiveTarget(null)}
        title={t("employeesSettings.archive.title", { entity: archiveTarget?.name ?? entityLabel })}
        description={t("employeesSettings.archive.description", { entity: archiveTarget?.name ?? entityLabel })}
        confirmLabel={t("employeesSettings.actions.archive")}
        confirmVariant="danger"
        onConfirm={() => archiveTarget && archiveMutation.mutate(archiveTarget)}
      />
    </>
  );
}

function columnsFor(
  section: EmployeeSettingsSection,
  t: (key: string) => string,
  onEdit: (record: EmployeeMasterRecord) => void,
  onArchive: (record: EmployeeMasterRecord) => void
): Array<ListColumn<EmployeeMasterRecord>> {
  const columns: Array<ListColumn<EmployeeMasterRecord>> = [
    { key: "code", header: t("employeesSettings.columns.code"), cell: (record) => record.code },
    { key: "name", header: t("employeesSettings.columns.name"), cell: (record) => record.name }
  ];

  if (section.masterKind === "employee-levels") {
    columns.push({ key: "order", header: t("employeesSettings.columns.order"), cell: (record) => "order" in record ? record.order : "-" });
  }
  if (section.masterKind === "departments") {
    columns.push({
      key: "totalEmployees",
      header: t("employeesSettings.columns.totalEmployees"),
      cell: (record) => ("totalEmployees" in record ? record.totalEmployees ?? 0 : 0)
    });
  }
  if (section.masterKind === "job-positions") {
    columns.push({
      key: "department",
      header: t("employeesSettings.columns.department"),
      cell: (record) => ("departmentId" in record ? record.departmentId ?? "-" : "-")
    });
  }
  if (section.masterKind === "contract-types") {
    columns.push({ key: "type", header: t("employeesSettings.columns.type"), cell: (record) => "type" in record ? record.type.toUpperCase() : "-" });
    columns.push({
      key: "maxDuration",
      header: t("employeesSettings.columns.maxDuration"),
      cell: (record) => ("maxDurationMonths" in record ? record.maxDurationMonths ?? "-" : "-")
    });
  }
  if (section.masterKind === "banks") {
    columns.push({ key: "swift", header: t("employeesSettings.columns.swift"), cell: (record) => "swift" in record ? record.swift ?? "-" : "-" });
  }
  if (section.masterKind === "document-types") {
    columns.push({
      key: "mandatory",
      header: t("employeesSettings.columns.mandatory"),
      cell: (record) => ("isMandatory" in record && record.isMandatory ? t("employeesSettings.values.yes") : t("employeesSettings.values.no"))
    });
  }
  if (section.masterKind === "education-levels") {
    columns.push({ key: "order", header: t("employeesSettings.columns.order"), cell: (record) => "order" in record ? record.order : "-" });
  }
  if (section.masterKind === "work-locations") {
    columns.push({ key: "city", header: t("employeesSettings.columns.city"), cell: (record) => "cityId" in record ? record.cityId ?? "-" : "-" });
    columns.push({ key: "address", header: t("employeesSettings.columns.address"), cell: (record) => "address" in record ? record.address ?? "-" : "-" });
  }

  columns.push(
    {
      key: "status",
      header: t("employeesSettings.columns.status"),
      cell: (record) => <StatusBadge label={statusLabel(record.isActive, t)} tone={record.isActive ? "success" : "neutral"} />
    },
    {
      key: "actions",
      header: t("employeesSettings.columns.actions"),
      cell: (record) => (
        <div className="flex flex-wrap gap-2">
          <Button size="sm" variant="outline" onClick={() => onEdit(record)}>
            <Edit className="h-4 w-4" />
            {t("employeesSettings.actions.edit")}
          </Button>
          <Button size="sm" variant="outline" onClick={() => onArchive(record)}>
            <Archive className="h-4 w-4" />
            {t("employeesSettings.actions.archive")}
          </Button>
        </div>
      )
    }
  );

  return columns;
}

function statusLabel(isActive: boolean, t: (key: string) => string): string {
  return isActive ? t("employeesSettings.status.active") : t("employeesSettings.status.inactive");
}
