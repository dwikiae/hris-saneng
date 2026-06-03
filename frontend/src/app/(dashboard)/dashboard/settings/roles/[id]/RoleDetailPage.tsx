"use client";

import { useMutation, useQuery } from "@tanstack/react-query";
import { ShieldCheck } from "lucide-react";
import { useParams } from "next/navigation";
import { useTranslation } from "react-i18next";
import { ChatLog } from "@/components/platform/ChatLog";
import { platformToast } from "@/components/platform/ToastProvider";
import { PermissionMatrix } from "@/components/settings/access/PermissionMatrix";
import { RoleUsersPanel } from "@/components/settings/access/RoleUsersPanel";
import { SettingsNav } from "@/components/settings/SettingsNav";
import { EmptyState } from "@/components/shared/EmptyState";
import { DetailPageTemplate } from "@/components/templates";
import { roleService } from "@/services/role.service";

export function RoleDetailPage() {
  const { t } = useTranslation("platform");
  const params = useParams<{ id: string }>();
  const roleId = params.id;
  const roleQuery = useQuery({ queryKey: ["settings", "roles", roleId], queryFn: () => roleService.detail(roleId) });
  const roleCompanyId = roleQuery.data?.company?.id;
  const permissionQuery = useQuery({
    queryKey: ["settings", "permissions", "structure", roleCompanyId],
    queryFn: () => roleService.permissionsStructure(roleCompanyId),
    enabled: Boolean(roleCompanyId)
  });
  const saveMutation = useMutation({
    mutationFn: (permissionIds: Array<string | number>) => roleService.updatePermissions(roleId, permissionIds),
    onSuccess: () => platformToast.success(t("settingsAccess.toast.permissionsSaved")),
    onError: () => platformToast.error(t("settingsAccess.api.permissionsNotReady"))
  });
  const role = roleQuery.data;

  if (roleQuery.isError) {
    return (
      <div className="space-y-6">
        <SettingsNav />
        <EmptyState title={t("settingsAccess.api.title")} description={t("settingsAccess.api.rolesNotReady")} />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <SettingsNav />
      <DetailPageTemplate
        backUrl="/dashboard/settings/users?tab=roles"
        backLabel={t("settingsAccess.actions.back")}
        breadcrumbs={[
          { label: t("nav.settings"), href: "/dashboard/settings" },
          { label: t("settingsNav.users"), href: "/dashboard/settings/users?tab=roles" },
          { label: role?.name ?? t("settingsAccess.roles.loading") }
        ]}
        avatarIcon={<ShieldCheck className="h-6 w-6" aria-hidden="true" />}
        title={role?.name ?? t("settingsAccess.roles.loading")}
        subtitle={role?.company?.name ?? "-"}
        metaInfo={[
          { label: t("settingsAccess.columns.userCount"), value: role?.userCount ?? "-" },
          { label: t("settingsAccess.columns.permissionCount"), value: role?.permissionCount ?? "-" }
        ]}
        isLoading={roleQuery.isLoading}
        tabs={[
          {
            slug: "permission",
            label: t("settingsAccess.roles.tabs.permissions"),
            content: permissionQuery.isError ? (
              <EmptyState title={t("settingsAccess.api.title")} description={t("settingsAccess.api.permissionsNotReady")} />
            ) : (
              <PermissionMatrix
                modules={permissionQuery.data ?? []}
                selectedPermissionIds={role?.permissionIds ?? []}
                isSaving={saveMutation.isPending}
                onSave={(ids) => saveMutation.mutate(ids)}
              />
            )
          },
          { slug: "users", label: t("settingsAccess.roles.tabs.users"), content: <RoleUsersPanel users={role?.users ?? []} /> }
        ]}
        notesContent={<ChatLog module="platform" recordId={roleId} initialActivities={[]} initialNotes={[]} />}
      />
    </div>
  );
}
