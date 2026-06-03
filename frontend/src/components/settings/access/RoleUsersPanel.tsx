"use client";

import { useTranslation } from "react-i18next";
import { AccessBadges } from "@/components/settings/access/AccessBadges";
import { EmptyState } from "@/components/shared/EmptyState";
import type { PlatformUser } from "@/types/access";

interface RoleUsersPanelProps {
  users: PlatformUser[];
}

export function RoleUsersPanel({ users }: RoleUsersPanelProps) {
  const { t } = useTranslation("platform");

  if (users.length === 0) {
    return <EmptyState title={t("settingsAccess.roles.usersEmptyTitle")} description={t("settingsAccess.api.roleUsersNotReady")} />;
  }

  return (
    <div className="divide-y rounded-lg border border-border bg-card">
      {users.map((user) => (
        <div key={user.id} className="flex flex-col gap-2 p-4 md:flex-row md:items-center md:justify-between">
          <div>
            <p className="text-sm font-semibold text-foreground">{user.name}</p>
            <p className="text-sm text-muted-foreground">{user.email}</p>
          </div>
          <AccessBadges items={user.companies ?? []} emptyLabel="-" />
        </div>
      ))}
    </div>
  );
}
