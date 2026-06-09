"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Save, Send } from "lucide-react";
import { useRouter, useSearchParams } from "next/navigation";
import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { ConfirmDialog } from "@/components/platform/ConfirmDialog";
import { platformToast } from "@/components/platform/ToastProvider";
import { EmployeeCompanyContextBar, useEmployeeCompanyContext } from "@/components/employees/EmployeeCompanyContextBar";
import { EmptyState } from "@/components/shared/EmptyState";
import { LoadingSkeleton } from "@/components/shared/LoadingSkeleton";
import { Button } from "@/components/ui/button";
import { FormPageTemplate, type FormTemplateSection } from "@/components/templates";
import { employeeMasterService } from "@/services/employee-master.service";
import { employeeSettingsService } from "@/services/employee-settings.service";
import { employeeLookupService, employeeService } from "@/services/employee.service";
import { wilayahService } from "@/services/wilayah.service";
import { useAuthStore } from "@/stores/auth.store";
import type { EmployeeContractPayload, EmployeeDetail } from "@/types/employee";
import type { EmployeeMasterRecord } from "@/types/employee-settings";
import { contractSections } from "./EmployeeContractSections";
import { employmentSections } from "./EmployeeEmploymentSections";
import { profileSections } from "./EmployeeProfileSections";
import { sensitiveSections } from "./EmployeeSensitiveSections";
import type { PlatformT } from "./employee-form-section-types";
import {
  applyProbationDefault,
  initialEmployeeFormState,
  stateFromEmployee,
  toEmployeePayload,
  validateEmployeeForm,
  type EmployeeFormErrors,
  type EmployeeFormMode,
  type EmployeeFormState,
  type EmployeeFormTab
} from "./employee-form-state";
import { employeeFormTabs, firstErrorField, tabHasError } from "./employee-form-tabs";

interface EmployeeFormPageProps {
  mode: EmployeeFormMode;
  employeeId?: string;
}

export function EmployeeFormPage({ mode, employeeId }: EmployeeFormPageProps) {
  const { t } = useTranslation("platform");
  const router = useRouter();
  const searchParams = useSearchParams();
  const queryClient = useQueryClient();
  const { activeCompanyId, hasCompanyContext } = useEmployeeCompanyContext();
  const canViewSensitive = useAuthStore((state) => state.hasPermission("employee.view_sensitive"));
  const canApprove = useAuthStore((state) => state.hasPermission("employee.approve"));
  const requestedTab = searchParams.get("tab") as EmployeeFormTab | null;
  const visibleTabs = canViewSensitive ? employeeFormTabs : employeeFormTabs.filter((tab) => tab !== "data-sensitif");
  const activeTab = requestedTab && visibleTabs.includes(requestedTab) ? requestedTab : "profil";
  const [state, setState] = useState<EmployeeFormState>(initialEmployeeFormState);
  const [initialSnapshot, setInitialSnapshot] = useState(() => JSON.stringify(initialEmployeeFormState));
  const [errors, setErrors] = useState<EmployeeFormErrors>({});

  const detailQuery = useQuery({
    queryKey: ["employees", activeCompanyId, "detail", employeeId],
    queryFn: () => employeeService.getDetail(employeeId ?? ""),
    enabled: mode === "edit" && Boolean(employeeId) && hasCompanyContext
  });
  const contractsQuery = useQuery({
    queryKey: ["employees", activeCompanyId, "detail", employeeId, "contracts"],
    queryFn: () => employeeService.getContracts(employeeId ?? ""),
    enabled: mode === "edit" && Boolean(employeeId) && hasCompanyContext
  });
  const emergencyContactsQuery = useQuery({
    queryKey: ["employees", activeCompanyId, "detail", employeeId, "emergency-contacts"],
    queryFn: () => employeeService.getEmergencyContacts(employeeId ?? ""),
    enabled: mode === "edit" && Boolean(employeeId) && hasCompanyContext
  });
  const lookups = useEmployeeFormLookups(activeCompanyId, state.departmentId, state.provinceId, state.domicileProvinceId);
  const isLoading = detailQuery.isLoading || contractsQuery.isLoading || emergencyContactsQuery.isLoading || lookups.isLoading;
  const isDirty = JSON.stringify(state) !== initialSnapshot;

  useEffect(() => {
    if (mode !== "edit" || !detailQuery.data) return;
    const contract = contractsQuery.data?.find((item) => item.status === "active") ?? contractsQuery.data?.[0] ?? null;
    const emergencyContact = emergencyContactsQuery.data?.[0] ?? null;
    const nextState = stateFromEmployee(detailQuery.data, contract, emergencyContact);
    setState(nextState);
    setInitialSnapshot(JSON.stringify(nextState));
  }, [contractsQuery.data, detailQuery.data, emergencyContactsQuery.data, mode]);

  const saveMutation = useMutation({
    mutationFn: (intent: "draft" | "submit") => saveEmployee(intent),
    onSuccess: async (employee) => {
      platformToast.success(t("employeesForm.toast.saved"));
      await queryClient.invalidateQueries({ queryKey: ["employees"] });
      router.push(`/dashboard/employees/${employee.id}`);
    },
    onError: () => platformToast.error(t("employeesDetail.toast.failed"))
  });

  if (!hasCompanyContext) {
    return (
      <>
        <EmployeeCompanyContextBar />
        <EmptyState title={t("employeesForm.empty.companyTitle")} description={t("employeesForm.empty.companyDescription")} />
      </>
    );
  }

  if (detailQuery.isError) {
    return (
      <EmptyState
        title={t("employeesDetail.api.detailTitle")}
        description={t("employeesDetail.api.detail")}
        actionLabel={t("employeesDetail.actions.retry")}
        onAction={() => void detailQuery.refetch()}
      />
    );
  }

  if (isLoading) {
    return <LoadingSkeleton rows={5} itemClassName="h-20" />;
  }

  const employeeName = detailQuery.data?.name ?? state.name;
  const backUrl = mode === "new" ? "/dashboard/employees" : `/dashboard/employees/${employeeId}`;
  const sections = buildSections(activeTab, state, errors, t, update, lookups);

  return (
    <>
      <EmployeeCompanyContextBar />
      <FormPageTemplate
        title={mode === "new" ? t("employeesForm.new.title") : t("employeesForm.edit.title", { name: employeeName })}
        backUrl={backUrl}
        backLabel={t("employeesForm.actions.cancel")}
        breadcrumbs={breadcrumbs(mode, employeeName, t)}
        tabs={visibleTabs.map((tab) => ({
          slug: tab,
          label: t(`employeesForm.tabs.${tab}`),
          href: `${mode === "new" ? "/dashboard/employees/new" : `/dashboard/employees/${employeeId}/edit`}?tab=${tab}`,
          hasError: tabHasError(tab, errors)
        }))}
        activeTab={activeTab}
        sections={sections}
        isDirty={isDirty}
        isSubmitting={saveMutation.isPending}
        footerActions={[
          { id: "cancel", label: t("employeesForm.actions.cancel"), variant: "secondary", custom: cancelAction(isDirty, backUrl, t, router.push) },
          { id: "draft", label: t("employeesForm.actions.saveDraft"), icon: <Save className="h-4 w-4" />, variant: "secondary", disabled: !isDirty, onClick: () => submit("draft") },
          { id: "submit", label: canApprove ? t("employeesForm.actions.saveActivate") : t("employeesForm.actions.submitApproval"), icon: <Send className="h-4 w-4" />, variant: "primary", onClick: () => submit("submit") }
        ]}
      />
    </>
  );

  function update<K extends keyof EmployeeFormState>(field: K, value: EmployeeFormState[K]) {
    setState((current) => {
      const next = { ...current, [field]: value };
      if (field === "departmentId") {
        next.positionId = "";
      }
      return field === "joinDate" ? applyProbationDefault(next, lookups.settings) : next;
    });
  }

  function submit(intent: "draft" | "submit") {
    const nextErrors = validateEmployeeForm(state, t);
    setErrors(nextErrors);
    const firstError = firstErrorField(nextErrors);
    if (firstError) {
      document.getElementById(firstError)?.scrollIntoView({ behavior: "smooth", block: "center" });
      return;
    }
    saveMutation.mutate(intent);
  }

  async function saveEmployee(intent: "draft" | "submit"): Promise<EmployeeDetail> {
    let employee =
      mode === "new"
        ? intent === "draft"
          ? await employeeService.saveDraft(toEmployeePayload(state, lookups.employmentTypes, "new"))
          : await employeeService.create(toEmployeePayload(state, lookups.employmentTypes, "new"))
        : await employeeService.update(employeeId ?? "", toEmployeePayload(state, lookups.employmentTypes, "edit"));

    if (mode === "edit") {
      await saveContract(employee.id);
      await saveEmergencyContact(employee.id);
    }

    if (state.photoFile) await employeeService.uploadPhoto(employee.id, state.photoFile);

    if (intent === "submit") {
      employee = await employeeService.submitForApproval(employee.id);
      if (canApprove) employee = await employeeService.approve(employee.id);
    }

    return employee;
  }

  async function saveContract(targetEmployeeId: string | number) {
    const payload: EmployeeContractPayload = {
      contract_type: state.contractType,
      contract_number: state.contractNumber || null,
      start_date: state.contractStartDate,
      end_date: state.contractType === "pkwt" ? state.contractEndDate || null : null,
      notes: state.contractNotes || null
    };

    if (state.contractId) {
      await employeeService.updateContract(targetEmployeeId, state.contractId, payload);
      return;
    }

    await employeeService.createContract(targetEmployeeId, payload);
  }

  async function saveEmergencyContact(targetEmployeeId: string | number) {
    if (!state.emergencyName.trim()) return;

    const payload = {
      name: state.emergencyName.trim(),
      relationship: state.emergencyRelationship || null,
      phone: state.emergencyPhone || null
    };

    if (state.emergencyContactId) {
      await employeeService.updateEmergencyContact(targetEmployeeId, state.emergencyContactId, payload);
      return;
    }

    await employeeService.createEmergencyContact(targetEmployeeId, payload);
  }
}

function useEmployeeFormLookups(companyId: string | null, departmentId: string, provinceId: string, domicileProvinceId: string) {
  const queries = {
    departments: useQuery({ queryKey: ["employees", "master", companyId, "departments"], queryFn: () => employeeMasterService.list(companyId ?? "", "departments", { isActive: true }), enabled: Boolean(companyId) }),
    positions: useQuery({ queryKey: ["employees", "master", companyId, "job-positions", departmentId], queryFn: () => employeeMasterService.list(companyId ?? "", "job-positions", { isActive: true, departmentId: departmentId || null }), enabled: Boolean(companyId) }),
    contractTypes: useQuery({ queryKey: ["employees", "master", companyId, "contract-types"], queryFn: () => employeeMasterService.list(companyId ?? "", "contract-types", { isActive: true }), enabled: Boolean(companyId) }),
    employmentTypes: useQuery({ queryKey: ["employees", companyId, "lookup", "employment-types"], queryFn: employeeLookupService.employmentTypes, enabled: Boolean(companyId) }),
    religions: useQuery({ queryKey: ["employees", "master", companyId, "religions"], queryFn: () => employeeMasterService.list(companyId ?? "", "religions", { isActive: true }), enabled: Boolean(companyId) }),
    maritalStatuses: useQuery({ queryKey: ["employees", companyId, "lookup", "marital-statuses"], queryFn: employeeLookupService.maritalStatuses, enabled: Boolean(companyId) }),
    bloodTypes: useQuery({ queryKey: ["employees", companyId, "lookup", "blood-types"], queryFn: employeeLookupService.bloodTypes, enabled: Boolean(companyId) }),
    banks: useQuery({ queryKey: ["employees", "master", companyId, "banks"], queryFn: () => employeeMasterService.list(companyId ?? "", "banks", { isActive: true }), enabled: Boolean(companyId) }),
    supervisors: useQuery({ queryKey: ["employees", companyId, "lookup", "supervisors"], queryFn: () => employeeService.list({ status: "active", perPage: 100 }), enabled: Boolean(companyId) }),
    provinces: useQuery({ queryKey: ["wilayah", "provinces"], queryFn: wilayahService.provinces }),
    ktpCities: useQuery({ queryKey: ["wilayah", "cities", provinceId], queryFn: () => wilayahService.cities(provinceId), enabled: Boolean(provinceId) }),
    domicileCities: useQuery({ queryKey: ["wilayah", "cities", domicileProvinceId], queryFn: () => wilayahService.cities(domicileProvinceId), enabled: Boolean(domicileProvinceId) }),
    countries: useQuery({ queryKey: ["wilayah", "countries"], queryFn: wilayahService.countries }),
    levels: useQuery({ queryKey: ["employees", "master", companyId, "employee-levels"], queryFn: () => employeeMasterService.list(companyId ?? "", "employee-levels", { isActive: true }), enabled: Boolean(companyId) }),
    workLocations: useQuery({ queryKey: ["employees", "master", companyId, "work-locations"], queryFn: () => employeeMasterService.list(companyId ?? "", "work-locations", { isActive: true }), enabled: Boolean(companyId) }),
    settings: useQuery({ queryKey: ["employees", "settings", companyId], queryFn: () => employeeSettingsService.get(companyId ?? ""), enabled: Boolean(companyId) })
  };
  const isLoading = Object.values(queries).some((query) => query.isLoading);

  return {
    isLoading,
    departments: toLookupOptions(queries.departments.data ?? []),
    positions: toLookupOptions(queries.positions.data ?? []),
    contractTypes: queries.contractTypes.data ?? [],
    employmentTypes: queries.employmentTypes.data ?? [],
    religions: toLookupOptions(queries.religions.data ?? []),
    maritalStatuses: queries.maritalStatuses.data ?? [],
    bloodTypes: queries.bloodTypes.data ?? [],
    banks: toLookupOptions(queries.banks.data ?? []),
    supervisors: queries.supervisors.data?.items ?? [],
    provinces: queries.provinces.data ?? [],
    ktpCities: queries.ktpCities.data ?? [],
    domicileCities: queries.domicileCities.data ?? [],
    countries: queries.countries.data ?? [],
    levels: queries.levels.data ?? [],
    workLocations: queries.workLocations.data ?? [],
    settings: queries.settings.data
  };
}

function buildSections(
  tab: EmployeeFormTab,
  state: EmployeeFormState,
  errors: EmployeeFormErrors,
  t: PlatformT,
  update: <K extends keyof EmployeeFormState>(field: K, value: EmployeeFormState[K]) => void,
  lookups: ReturnType<typeof useEmployeeFormLookups>
): FormTemplateSection[] {
  const context = { state, errors, t, update };
  if (tab === "kepegawaian") return employmentSections(lookups)(context);
  if (tab === "kontrak") return contractSections(lookups.contractTypes)(context);
  if (tab === "data-sensitif") return sensitiveSections(lookups.banks)(context);
  return profileSections(lookups)(context);
}

function breadcrumbs(mode: EmployeeFormMode, employeeName: string, t: PlatformT) {
  return [
    { label: t("employeesList.breadcrumb.dashboard"), href: "/dashboard" },
    { label: t("employeesList.breadcrumb.employees"), href: "/dashboard/employees" },
    ...(mode === "new"
      ? [{ label: t("employeesForm.new.title") }]
      : [{ label: employeeName, href: "#" }, { label: t("employeesForm.edit.shortTitle") }])
  ];
}

function cancelAction(isDirty: boolean, backUrl: string, t: (key: string) => string, navigate: (href: string) => void) {
  if (!isDirty) {
    return (
      <Button type="button" variant="outline" onClick={() => navigate(backUrl)}>
        {t("employeesForm.actions.cancel")}
      </Button>
    );
  }

  return (
    <ConfirmDialog
      title={t("employeesForm.cancel.title")}
      description={t("employeesForm.cancel.description")}
      confirmLabel={t("employeesForm.actions.cancel")}
      confirmVariant="warning"
      onConfirm={() => navigate(backUrl)}
    >
      <Button type="button" variant="outline">
        {t("employeesForm.actions.cancel")}
      </Button>
    </ConfirmDialog>
  );
}

function toLookupOptions(items: EmployeeMasterRecord[]) {
  return items.map((item) => ({ id: item.id, code: item.code, name: item.name }));
}
