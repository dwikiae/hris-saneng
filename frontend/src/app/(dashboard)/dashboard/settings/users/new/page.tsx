"use client";

import { useMutation, useQuery } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { useTranslation } from "react-i18next";
import { platformToast } from "@/components/platform/ToastProvider";
import { UserForm } from "@/components/settings/access/UserForm";
import { SettingsNav } from "@/components/settings/SettingsNav";
import { companyService } from "@/services/company.service";
import { roleService } from "@/services/role.service";
import { userService } from "@/services/user.service";
import type { UserPayload } from "@/types/access";

export default function NewUserPage() {
  const { t } = useTranslation("platform");
  const router = useRouter();
  const companiesQuery = useQuery({ queryKey: ["settings", "companies", "lookup"], queryFn: () => companyService.list({ perPage: 100 }) });
  const rolesQuery = useQuery({ queryKey: ["settings", "roles", "lookup"], queryFn: () => roleService.list({ perPage: 100 }) });
  const mutation = useMutation({
    mutationFn: (payload: UserPayload) => userService.create(payload),
    onSuccess: (user) => {
      platformToast.success(t("settingsAccess.toast.userCreated"));
      router.push(`/dashboard/settings/users/${user.id}`);
    },
    onError: () => platformToast.error(t("settingsAccess.api.usersNotReady"))
  });

  return (
    <div className="space-y-6">
      <SettingsNav />
      <UserForm
        mode="new"
        companies={companiesQuery.data?.items ?? []}
        roles={rolesQuery.data?.items ?? []}
        lookupUnavailable={companiesQuery.isError || rolesQuery.isError}
        isSubmitting={mutation.isPending}
        onSubmit={(payload) => mutation.mutate(payload)}
      />
    </div>
  );
}
