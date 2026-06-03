"use client";

import type { ReactNode } from "react";
import { useTranslation } from "react-i18next";
import { EmptyState } from "@/components/shared/EmptyState";
import { LoadingSkeleton } from "@/components/shared/LoadingSkeleton";
import { useAuthStore } from "@/stores/auth.store";

export function SettingsAccessGate({ children }: { children: ReactNode }) {
  const { t } = useTranslation("platform");
  const isHydrated = useAuthStore((state) => state.isHydrated);
  const hasPermission = useAuthStore((state) => state.hasPermission("platform.settings"));

  if (!isHydrated) {
    return <LoadingSkeleton rows={3} itemClassName="h-16" />;
  }

  if (!hasPermission) {
    return (
      <EmptyState
        title={t("settingsPlatform.access.deniedTitle")}
        description={t("settingsPlatform.access.deniedDescription")}
      />
    );
  }

  return <>{children}</>;
}
