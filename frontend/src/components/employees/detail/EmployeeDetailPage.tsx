"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Archive, Pencil } from "lucide-react";
import Link from "next/link";
import { useParams, useSearchParams } from "next/navigation";
import { useTranslation } from "react-i18next";
import { ConfirmDialog } from "@/components/platform/ConfirmDialog";
import { PermissionGate } from "@/components/platform/PermissionGate";
import { platformToast } from "@/components/platform/ToastProvider";
import { EmployeeCompanyContextBar, useEmployeeCompanyContext } from "@/components/employees/EmployeeCompanyContextBar";
import { EmptyState } from "@/components/shared/EmptyState";
import { Button } from "@/components/ui/button";
import { DetailPageTemplate } from "@/components/templates";
import { employeeService } from "@/services/employee.service";
import { EmployeeApprovalPanel } from "./EmployeeApprovalPanel";
import { EmployeeContractsTab } from "./EmployeeContractsTab";
import { EmployeeDocumentsTab } from "./EmployeeDocumentsTab";
import { EmployeeEducationExperienceTab } from "./EmployeeEducationExperienceTab";
import { EmployeeEmploymentTab } from "./EmployeeEmploymentTab";
import { EmployeeFamilyTab } from "./EmployeeFamilyTab";
import { EmployeeNotesTab } from "./EmployeeNotesTab";
import { EmployeeOffboardingTab } from "./EmployeeOffboardingTab";
import { EmployeeProfileTab } from "./EmployeeProfileTab";
import { employeeSubtitle, formatDate, initials, lookupName, statusTone } from "./detail-utils";

const baseTabs = [
  "profil",
  "kepegawaian",
  "kontrak",
  "keluarga",
  "pendidikan-pengalaman",
  "dokumen",
  "offboarding",
  "catatan"
];

export function EmployeeDetailPage() {
  const { t } = useTranslation("platform");
  const params = useParams<{ id: string }>();
  const searchParams = useSearchParams();
  const queryClient = useQueryClient();
  const employeeId = params.id;
  const { activeCompanyId, hasCompanyContext } = useEmployeeCompanyContext();
  const requestedTab = searchParams.get("tab");
  const activeTab = requestedTab && baseTabs.includes(requestedTab) ? requestedTab : "profil";
  const employeeQuery = useQuery({
    queryKey: ["employees", activeCompanyId, "detail", employeeId],
    queryFn: () => employeeService.getDetail(employeeId),
    enabled: hasCompanyContext
  });
  const photoQuery = useQuery({
    queryKey: ["employees", activeCompanyId, "detail", employeeId, "photo"],
    queryFn: () => employeeService.getPhoto(employeeId),
    enabled: Boolean(employeeQuery.data)
  });
  const offboardingQuery = useQuery({
    queryKey: ["employees", activeCompanyId, "detail", employeeId, "offboarding"],
    queryFn: () => employeeService.getOffboarding(employeeId),
    enabled: Boolean(employeeQuery.data)
  });
  const refreshDetail = async () => {
    await queryClient.invalidateQueries({ queryKey: ["employees", "detail", employeeId] });
    await queryClient.invalidateQueries({ queryKey: ["employees"] });
  };
  const archiveMutation = useMutation({
    mutationFn: () => employeeService.archive(employeeId),
    onSuccess: async () => {
      platformToast.success(t("employeesDetail.toast.archived"));
      await refreshDetail();
    },
    onError: () => platformToast.error(t("employeesDetail.toast.failed"))
  });
  const approveMutation = useMutation({
    mutationFn: () => employeeService.approve(employeeId),
    onSuccess: async () => {
      platformToast.success(t("employeesDetail.toast.approved"));
      await refreshDetail();
    },
    onError: () => platformToast.error(t("employeesDetail.toast.failed"))
  });
  const rejectMutation = useMutation({
    mutationFn: (reason: string) => employeeService.reject(employeeId, reason),
    onSuccess: async () => {
      platformToast.success(t("employeesDetail.toast.rejected"));
      await refreshDetail();
    },
    onError: () => platformToast.error(t("employeesDetail.toast.failed"))
  });
  const employee = employeeQuery.data;

  if (!hasCompanyContext) {
    return (
      <>
        <EmployeeCompanyContextBar />
        <EmptyState title={t("employeesForm.empty.companyTitle")} description={t("employeesForm.empty.companyDescription")} />
      </>
    );
  }

  if (employeeQuery.isError) {
    return (
      <>
        <EmployeeCompanyContextBar />
        <EmptyState
          title={t("employeesDetail.api.detailTitle")}
          description={t("employeesDetail.api.detail")}
          actionLabel={t("employeesDetail.actions.retry")}
          onAction={() => void employeeQuery.refetch()}
        />
      </>
    );
  }

  return (
    <>
      <EmployeeCompanyContextBar />
      <DetailPageTemplate
        backUrl="/dashboard/employees"
        backLabel={t("employeesDetail.actions.back")}
        breadcrumbs={[
          { label: t("employeesList.breadcrumb.dashboard"), href: "/dashboard" },
          { label: t("employeesList.breadcrumb.employees"), href: "/dashboard/employees" },
          { label: employee?.name ?? t("employeesDetail.loading") }
        ]}
        actions={employee ? detailActions(employee.id, employee.name, t, () => archiveMutation.mutate()) : []}
        avatarUrl={photoQuery.data?.thumbnail ?? photoQuery.data?.medium ?? employee?.photo_url ?? undefined}
        avatarFallback={employee ? initials(employee.name) : undefined}
        title={employee?.name ?? t("employeesDetail.loading")}
        subtitle={employee ? employeeSubtitle(employee) : undefined}
        entityId={employee?.employee_number ?? String(employeeId)}
        metaInfo={[
          { label: t("employeesDetail.meta.contractType"), value: lookupName(employee?.employment_type ?? employee?.employmentType) },
          { label: t("employeesDetail.meta.workLocation"), value: lookupName(employee?.work_location) },
          { label: t("employeesDetail.meta.joinDate"), value: formatDate(employee?.join_date) },
          { label: t("employeesDetail.meta.status"), value: employee?.status ?? "-" }
        ]}
        status={
          employee
            ? {
                label: t(`employeesList.status.${employee.status ?? "unknown"}`),
                tone: statusTone(employee.status)
              }
            : undefined
        }
        approvalSlot={
          employee?.status === "pending" ? (
            <EmployeeApprovalPanel
              entityName={employee.name}
              t={t}
              onApprove={() => approveMutation.mutate()}
              onReject={(reason) => rejectMutation.mutate(reason)}
            />
          ) : null
        }
        tabs={[
          {
            slug: "profil",
            label: t("employeesDetail.tabs.profile"),
            content: employee ? <EmployeeProfileTab employee={employee} employeeId={employeeId} t={t} /> : null
          },
          {
            slug: "kepegawaian",
            label: t("employeesDetail.tabs.employment"),
            content: employee ? <EmployeeEmploymentTab employee={employee} t={t} /> : null
          },
          {
            slug: "kontrak",
            label: t("employeesDetail.tabs.contracts"),
            content: <EmployeeContractsTab employeeId={employeeId} enabled={activeTab === "kontrak"} t={t} />
          },
          {
            slug: "keluarga",
            label: t("employeesDetail.tabs.family"),
            content: <EmployeeFamilyTab employeeId={employeeId} enabled={activeTab === "keluarga"} t={t} />
          },
          {
            slug: "pendidikan-pengalaman",
            label: t("employeesDetail.tabs.educationExperience"),
            content: (
              <EmployeeEducationExperienceTab
                employeeId={employeeId}
                enabled={activeTab === "pendidikan-pengalaman"}
                t={t}
              />
            )
          },
          {
            slug: "dokumen",
            label: t("employeesDetail.tabs.documents"),
            content: <EmployeeDocumentsTab employeeId={employeeId} enabled={activeTab === "dokumen"} t={t} />
          },
          {
            slug: "offboarding",
            label: t("employeesDetail.tabs.offboarding"),
            visible: Boolean(offboardingQuery.data?.is_visible),
            content: <EmployeeOffboardingTab employeeId={employeeId} enabled={activeTab === "offboarding"} t={t} />
          }
        ]}
        defaultTab="profil"
        notesSlug="catatan"
        notesLabel={t("employeesDetail.tabs.notes")}
        notesContent={<EmployeeNotesTab employeeId={employeeId} />}
        isLoading={employeeQuery.isLoading}
      />
    </>
  );
}

function detailActions(
  employeeId: string | number,
  employeeName: string,
  t: (key: string, options?: Record<string, string>) => string,
  onArchive: () => void
) {
  return [
    {
      id: "edit",
      label: t("employeesDetail.actions.edit"),
      custom: (
        <PermissionGate permission="employee.update">
          <Button asChild>
            <Link href={`/dashboard/employees/${employeeId}/edit`}>
              <Pencil className="mr-2 h-4 w-4" aria-hidden="true" />
              {t("employeesDetail.actions.edit")}
            </Link>
          </Button>
        </PermissionGate>
      )
    },
    {
      id: "archive",
      label: t("employeesDetail.actions.archive"),
      custom: (
        <PermissionGate permission="employee.archive">
          <ConfirmDialog
            title={t("employeesDetail.archive.title", { name: employeeName })}
            description={t("employeesDetail.archive.description")}
            confirmLabel={t("employeesDetail.actions.archive")}
            confirmVariant="danger"
            onConfirm={onArchive}
          >
            <Button type="button" variant="destructive">
              <Archive className="mr-2 h-4 w-4" aria-hidden="true" />
              {t("employeesDetail.actions.archive")}
            </Button>
          </ConfirmDialog>
        </PermissionGate>
      )
    }
  ];
}
