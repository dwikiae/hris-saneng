"use client";

import { useMutation, useQuery } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { useTranslation } from "react-i18next";
import { platformToast } from "@/components/platform/ToastProvider";
import { SettingsNav } from "@/components/settings/SettingsNav";
import { CompanyForm } from "@/components/settings/companies/CompanyForm";
import { companyService } from "@/services/company.service";
import type { CompanyPayload } from "@/types/company";

export default function NewCompanyPage() {
  const { t } = useTranslation("platform");
  const router = useRouter();
  const modulesQuery = useQuery({
    queryKey: ["settings", "modules"],
    queryFn: () => companyService.modules()
  });
  const mutation = useMutation({
    mutationFn: (payload: CompanyPayload) => companyService.create(payload),
    onSuccess: (company) => {
      platformToast.success(t("settingsCompanies.toast.created"));
      router.push(`/dashboard/settings/companies/${company.id}`);
    },
    onError: () => platformToast.error(t("settingsCompanies.api.companiesNotReady"))
  });

  return (
    <div className="space-y-6">
      <SettingsNav />
      <CompanyForm
        mode="new"
        modules={modulesQuery.data ?? []}
        modulesUnavailable={modulesQuery.isError}
        isSubmitting={mutation.isPending}
        onSubmit={(payload) => mutation.mutate(payload)}
      />
    </div>
  );
}
