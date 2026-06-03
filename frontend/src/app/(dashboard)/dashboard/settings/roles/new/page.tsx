"use client";

import { useMutation, useQuery } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { useTranslation } from "react-i18next";
import { platformToast } from "@/components/platform/ToastProvider";
import { RoleForm } from "@/components/settings/access/RoleForm";
import { SettingsNav } from "@/components/settings/SettingsNav";
import { companyService } from "@/services/company.service";
import { roleService } from "@/services/role.service";
import type { RolePayload } from "@/types/access";

export default function NewRolePage() {
  const { t } = useTranslation("platform");
  const router = useRouter();
  const companiesQuery = useQuery({ queryKey: ["settings", "companies", "lookup"], queryFn: () => companyService.list({ perPage: 100 }) });
  const rolesQuery = useQuery({ queryKey: ["settings", "roles", "lookup"], queryFn: () => roleService.list({ perPage: 100 }) });
  const mutation = useMutation({
    mutationFn: (payload: RolePayload) => roleService.create(payload),
    onSuccess: (role) => {
      platformToast.success(t("settingsAccess.toast.roleCreated"));
      router.push(`/dashboard/settings/roles/${role.id}`);
    },
    onError: () => platformToast.error(t("settingsAccess.api.rolesNotReady"))
  });

  return (
    <div className="space-y-6">
      <SettingsNav />
      <RoleForm
        companies={companiesQuery.data?.items ?? []}
        roles={rolesQuery.data?.items ?? []}
        lookupUnavailable={companiesQuery.isError || rolesQuery.isError}
        isSubmitting={mutation.isPending}
        onSubmit={(payload) => mutation.mutate(payload)}
      />
    </div>
  );
}
