"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useParams, useRouter } from "next/navigation";
import { useTranslation } from "react-i18next";
import { platformToast } from "@/components/platform/ToastProvider";
import { SettingsNav } from "@/components/settings/SettingsNav";
import { CompanyForm } from "@/components/settings/companies/CompanyForm";
import { LoadingSkeleton } from "@/components/shared/LoadingSkeleton";
import { EmptyState } from "@/components/shared/EmptyState";
import { companyService } from "@/services/company.service";
import type { CompanyPayload } from "@/types/company";

export default function EditCompanyPage() {
  const { t } = useTranslation("platform");
  const params = useParams<{ id: string }>();
  const router = useRouter();
  const companyId = params.id;
  const queryClient = useQueryClient();
  const companyQuery = useQuery({
    queryKey: ["settings", "companies", companyId],
    queryFn: () => companyService.detail(companyId)
  });
  const modulesQuery = useQuery({
    queryKey: ["settings", "modules"],
    queryFn: () => companyService.modules()
  });
  const mutation = useMutation({
    mutationFn: (payload: CompanyPayload) => companyService.update(companyId, payload),
    onSuccess: (company) => {
      queryClient.setQueryData(["settings", "companies", companyId], company);
      queryClient.setQueryData(["settings", "companies", company.id], company);
      void queryClient.invalidateQueries({ queryKey: ["settings", "companies"] });
      platformToast.success(t("settingsCompanies.toast.updated"));
      router.push(`/dashboard/settings/companies/${company.id}`);
    },
    onError: () => platformToast.error(t("settingsCompanies.api.companiesNotReady"))
  });

  if (companyQuery.isLoading) {
    return <LoadingSkeleton rows={5} />;
  }

  if (companyQuery.isError || !companyQuery.data) {
    return (
      <EmptyState
        title={t("settingsCompanies.api.title")}
        description={t("settingsCompanies.api.companiesNotReady")}
      />
    );
  }

  return (
    <div className="space-y-6">
      <SettingsNav />
      <CompanyForm
        mode="edit"
        company={companyQuery.data}
        modules={modulesQuery.data ?? companyQuery.data.activeModules ?? []}
        modulesUnavailable={modulesQuery.isError}
        isSubmitting={mutation.isPending}
        onSubmit={(payload) => mutation.mutate(payload)}
      />
    </div>
  );
}
