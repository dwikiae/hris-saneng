"use client";

import { useQuery } from "@tanstack/react-query";
import { Bell } from "lucide-react";
import { useTranslation } from "react-i18next";
import { PageHeader } from "@/components/layout/PageHeader";
import { EmptyState } from "@/components/shared/EmptyState";
import { LoadingSkeleton } from "@/components/shared/LoadingSkeleton";
import { notificationService } from "@/services/platform.service";

export default function NotificationsPage() {
  const { t } = useTranslation("platform");
  const { data, isLoading, isError } = useQuery({
    queryKey: ["platform", "notifications", "page"],
    queryFn: () => notificationService.list(20)
  });
  const notifications = data?.items ?? [];

  return (
    <div className="space-y-6">
      <PageHeader
        title={t("platformBehavior.notifications.pageTitle")}
        description={t("platformBehavior.notifications.pageDescription")}
      />

      {isLoading ? <LoadingSkeleton rows={5} /> : null}
      {isError ? (
        <EmptyState
          title={t("platformBehavior.notifications.errorTitle")}
          description={t("platformBehavior.notifications.errorDescription")}
        />
      ) : null}
      {!isLoading && !isError && notifications.length === 0 ? (
        <EmptyState
          title={t("platformBehavior.notifications.empty")}
          description={t("platformBehavior.notifications.emptyDescription")}
        />
      ) : null}
      <div className="divide-y rounded-lg border border-border bg-background">
        {notifications.map((notification) => (
          <div key={notification.id} className="flex gap-3 p-4">
            <Bell className="mt-1 h-4 w-4 text-primary" aria-hidden="true" />
            <div>
              <p className="text-sm font-medium text-foreground">
                {t(notification.title_key, { defaultValue: notification.title_key })}
              </p>
              <p className="text-sm text-muted-foreground">
                {t(notification.body_key, { defaultValue: notification.body_key })}
              </p>
              <p className="mt-1 text-xs text-muted-foreground">{notification.created_at}</p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
