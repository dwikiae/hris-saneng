"use client";

import { useMutation, useQuery } from "@tanstack/react-query";
import { useParams, useRouter } from "next/navigation";
import { useTranslation } from "react-i18next";
import { platformToast } from "@/components/platform/ToastProvider";
import { UserForm } from "@/components/settings/access/UserForm";
import { SettingsNav } from "@/components/settings/SettingsNav";
import { EmptyState } from "@/components/shared/EmptyState";
import { LoadingSkeleton } from "@/components/shared/LoadingSkeleton";
import { companyService } from "@/services/company.service";
import { roleService } from "@/services/role.service";
import { userService } from "@/services/user.service";
import type { UserPayload } from "@/types/access";

export default function EditUserPage() {
  const { t } = useTranslation("platform");
  const params = useParams<{ id: string }>();
  const router = useRouter();
  const userId = params.id;
  const userQuery = useQuery({ queryKey: ["settings", "users", userId], queryFn: () => userService.detail(userId) });
  const companiesQuery = useQuery({ queryKey: ["settings", "companies", "lookup"], queryFn: () => companyService.list({ perPage: 100 }) });
  const rolesQuery = useQuery({ queryKey: ["settings", "roles", "lookup"], queryFn: () => roleService.list({ perPage: 100 }) });
  const mutation = useMutation({
    mutationFn: (payload: UserPayload) => userService.update(userId, payload),
    onSuccess: (user) => {
      platformToast.success(t("settingsAccess.toast.userUpdated"));
      router.push(`/dashboard/settings/users/${user.id}`);
    },
    onError: () => platformToast.error(t("settingsAccess.api.usersNotReady"))
  });

  if (userQuery.isLoading) {
    return <LoadingSkeleton rows={5} />;
  }

  if (userQuery.isError || !userQuery.data) {
    return <EmptyState title={t("settingsAccess.api.title")} description={t("settingsAccess.api.usersNotReady")} />;
  }

  return (
    <div className="space-y-6">
      <SettingsNav />
      <UserForm
        mode="edit"
        user={userQuery.data}
        companies={companiesQuery.data?.items ?? userQuery.data.companies ?? []}
        roles={rolesQuery.data?.items ?? userQuery.data.roles ?? []}
        lookupUnavailable={companiesQuery.isError || rolesQuery.isError}
        isSubmitting={mutation.isPending}
        onSubmit={(payload) => mutation.mutate(payload)}
      />
    </div>
  );
}
