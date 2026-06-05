"use client";

import { useRouter, useSearchParams } from "next/navigation";
import { Suspense } from "react";
import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { EmployeeCompanySelector, useEmployeeCompanyOptions } from "@/components/employees/settings/EmployeeCompanySelector";
import { EmployeeMasterSection } from "@/components/employees/settings/EmployeeMasterSection";
import { EmployeeSettingsForm } from "@/components/employees/settings/EmployeeSettingsForm";
import { SettingsModuleLayout } from "@/components/employees/settings/SettingsModuleLayout";
import {
  defaultEmployeeSettingsSection,
  employeeSettingsGroups,
  findEmployeeSettingsSection
} from "@/components/employees/settings/settings-sections";
import { EmptyState } from "@/components/shared/EmptyState";
import { LoadingSkeleton } from "@/components/shared/LoadingSkeleton";
import { useAuthStore } from "@/stores/auth.store";

export default function EmployeeSettingsPage() {
  return (
    <Suspense fallback={<LoadingSkeleton rows={3} itemClassName="h-20" />}>
      <EmployeeSettingsPageContent />
    </Suspense>
  );
}

function EmployeeSettingsPageContent() {
  const { t } = useTranslation("platform");
  const router = useRouter();
  const searchParams = useSearchParams();
  const user = useAuthStore((state) => state.user);
  const isHydrated = useAuthStore((state) => state.isHydrated);
  const hasPermission = useAuthStore((state) => state.hasPermission("karyawan.settings"));
  const activeCompanyId = useAuthStore((state) => state.activeCompanyId);
  const setActiveCompanyId = useAuthStore((state) => state.setActiveCompanyId);
  const companyOptions = useEmployeeCompanyOptions(user);
  const [company, setCompany] = useState(activeCompanyId ?? "");
  const sectionParam = searchParams.get("section");
  const activeSection = findEmployeeSettingsSection(sectionParam);

  useEffect(() => {
    if (isHydrated && !hasPermission) {
      router.replace("/dashboard/employees");
    }
  }, [hasPermission, isHydrated, router]);

  useEffect(() => {
    if (!company && companyOptions.length > 0) {
      const initial = companyOptions[0].id;
      setCompany(initial);
      setActiveCompanyId(initial);
    }
  }, [company, companyOptions, setActiveCompanyId]);

  useEffect(() => {
    if (!sectionParam) {
      router.replace(`/dashboard/employees/settings?section=${defaultEmployeeSettingsSection}`);
    }
  }, [router, sectionParam]);

  const handleCompanyChange = (value: string) => {
    setCompany(value);
    setActiveCompanyId(value);
  };

  if (!isHydrated || !hasPermission) {
    return <LoadingSkeleton rows={3} itemClassName="h-20" />;
  }

  const headerAction = (
    <EmployeeCompanySelector options={companyOptions} value={company} onChange={handleCompanyChange} />
  );

  return (
    <SettingsModuleLayout
      title={t("employeesSettings.title")}
      description={t("employeesSettings.description")}
      groups={employeeSettingsGroups}
      activeSection={activeSection}
      headerAction={headerAction}
      onSectionChange={(slug) => router.push(`/dashboard/employees/settings?section=${slug}`)}
    >
      {!company ? (
        <EmptyState
          title={t("employeesSettings.company.emptyTitle")}
          description={t("employeesSettings.company.emptyDescription")}
        />
      ) : activeSection.slug === "pengaturan" ? (
        <EmployeeSettingsForm company={company} />
      ) : (
        <EmployeeMasterSection company={company} section={activeSection} />
      )}
    </SettingsModuleLayout>
  );
}
