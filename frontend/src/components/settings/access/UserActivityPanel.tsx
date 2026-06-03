"use client";

import { useTranslation } from "react-i18next";
import { EmptyState } from "@/components/shared/EmptyState";
import type { PlatformUser } from "@/types/access";

interface UserActivityPanelProps {
  user: PlatformUser;
}

export function UserActivityPanel({ user }: UserActivityPanelProps) {
  const { t } = useTranslation("platform");
  const history = user.loginHistory ?? [];

  if (history.length === 0) {
    return (
      <EmptyState
        title={t("settingsAccess.users.activityEmptyTitle")}
        description={t("settingsAccess.api.loginHistoryNotReady")}
      />
    );
  }

  return (
    <div className="divide-y rounded-lg border border-border bg-card">
      {history.map((item) => (
        <div key={item.id} className="p-4">
          <p className="text-sm font-medium text-foreground">{item.at}</p>
          <p className="text-sm text-muted-foreground">
            {[item.ip, item.device].filter(Boolean).join(" · ")}
          </p>
        </div>
      ))}
    </div>
  );
}
