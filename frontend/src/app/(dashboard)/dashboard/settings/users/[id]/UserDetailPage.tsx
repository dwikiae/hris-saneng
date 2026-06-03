"use client";

import { useMutation, useQuery } from "@tanstack/react-query";
import { Ban, Mail, Pencil, UserCircle } from "lucide-react";
import { useParams } from "next/navigation";
import { useTranslation } from "react-i18next";
import { ChatLog } from "@/components/platform/ChatLog";
import { platformToast } from "@/components/platform/ToastProvider";
import { UserActivityPanel } from "@/components/settings/access/UserActivityPanel";
import { UserInfoPanel } from "@/components/settings/access/UserInfoPanel";
import { SettingsNav } from "@/components/settings/SettingsNav";
import { EmptyState } from "@/components/shared/EmptyState";
import { DetailPageTemplate } from "@/components/templates";
import { userService } from "@/services/user.service";

function tone(status?: string | null) {
  return status === "active" ? "success" as const : status === "pending" ? "warning" as const : "neutral" as const;
}

export function UserDetailPage() {
  const { t } = useTranslation("platform");
  const params = useParams<{ id: string }>();
  const userId = params.id;
  const userQuery = useQuery({ queryKey: ["settings", "users", userId], queryFn: () => userService.detail(userId) });
  const resendMutation = useMutation({
    mutationFn: () => userService.resendInvitation(userId),
    onSuccess: () => platformToast.success(t("settingsAccess.toast.invitationSent")),
    onError: () => platformToast.error(t("settingsAccess.api.usersNotReady"))
  });
  const archiveMutation = useMutation({
    mutationFn: () => userService.archive(userId),
    onSuccess: () => platformToast.success(t("settingsAccess.toast.userArchived")),
    onError: () => platformToast.error(t("settingsAccess.api.usersNotReady"))
  });
  const user = userQuery.data;

  if (userQuery.isError) {
    return (
      <div className="space-y-6">
        <SettingsNav />
        <EmptyState title={t("settingsAccess.api.title")} description={t("settingsAccess.api.usersNotReady")} />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <SettingsNav />
      <DetailPageTemplate
        backUrl="/dashboard/settings/users"
        backLabel={t("settingsAccess.actions.back")}
        breadcrumbs={[
          { label: t("nav.settings"), href: "/dashboard/settings" },
          { label: t("settingsNav.users"), href: "/dashboard/settings/users" },
          { label: user?.name ?? t("settingsAccess.users.loading") }
        ]}
        actions={
          user
            ? [
                { id: "edit", label: t("settingsAccess.actions.edit"), href: `/dashboard/settings/users/${user.id}/edit`, icon: <Pencil className="h-4 w-4" />, variant: "primary" },
                ...(user.invitationStatus === "pending" ? [{ id: "resend", label: t("settingsAccess.actions.resendInvitation"), icon: <Mail className="h-4 w-4" />, onClick: () => resendMutation.mutate(), disabled: resendMutation.isPending }] : []),
                { id: "archive", label: t("settingsAccess.actions.deactivate"), icon: <Ban className="h-4 w-4" />, variant: "danger", onClick: () => archiveMutation.mutate(), disabled: archiveMutation.isPending }
              ]
            : []
        }
        avatarUrl={user?.avatarUrl ?? undefined}
        avatarIcon={<UserCircle className="h-6 w-6" aria-hidden="true" />}
        title={user?.name ?? t("settingsAccess.users.loading")}
        subtitle={user?.email}
        status={user ? { label: t(`settingsAccess.status.${user.status ?? "unknown"}`), tone: tone(user.status) } : undefined}
        isLoading={userQuery.isLoading}
        tabs={[
          { slug: "informasi", label: t("settingsAccess.users.tabs.info"), content: user ? <UserInfoPanel user={user} /> : null },
          { slug: "aktivitas", label: t("settingsAccess.users.tabs.activity"), content: user ? <UserActivityPanel user={user} /> : null }
        ]}
        notesContent={<ChatLog module="platform" recordId={userId} initialActivities={[]} initialNotes={[]} />}
      />
    </div>
  );
}
