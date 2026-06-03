"use client";

import { useTranslation } from "react-i18next";
import { AccessBadges } from "@/components/settings/access/AccessBadges";
import type { PlatformUser } from "@/types/access";

interface UserInfoPanelProps {
  user: PlatformUser;
}

function valueOf(value?: string | number | null) {
  return value === undefined || value === null || value === "" ? "-" : String(value);
}

export function UserInfoPanel({ user }: UserInfoPanelProps) {
  const { t } = useTranslation("platform");

  return (
    <section className="rounded-lg border border-border bg-card p-5">
      <h2 className="text-base font-semibold text-foreground">{t("settingsAccess.users.infoTitle")}</h2>
      <dl className="mt-4 grid gap-4 md:grid-cols-2">
        <div>
          <dt className="text-xs font-medium uppercase text-muted-foreground">{t("settingsAccess.fields.name")}</dt>
          <dd className="mt-1 text-sm text-foreground">{user.name}</dd>
        </div>
        <div>
          <dt className="text-xs font-medium uppercase text-muted-foreground">{t("settingsAccess.fields.email")}</dt>
          <dd className="mt-1 text-sm text-foreground">{user.email}</dd>
        </div>
        <div>
          <dt className="text-xs font-medium uppercase text-muted-foreground">{t("settingsAccess.fields.employee")}</dt>
          <dd className="mt-1 text-sm text-foreground">{valueOf(user.employeeName ?? user.employeeId)}</dd>
        </div>
        <div>
          <dt className="text-xs font-medium uppercase text-muted-foreground">{t("settingsAccess.columns.status")}</dt>
          <dd className="mt-1 text-sm text-foreground">{valueOf(user.status)}</dd>
        </div>
        <div>
          <dt className="text-xs font-medium uppercase text-muted-foreground">{t("settingsAccess.fields.companies")}</dt>
          <dd className="mt-1"><AccessBadges items={user.companies ?? []} emptyLabel="-" /></dd>
        </div>
        <div>
          <dt className="text-xs font-medium uppercase text-muted-foreground">{t("settingsAccess.fields.roles")}</dt>
          <dd className="mt-1"><AccessBadges items={user.roles ?? []} emptyLabel="-" /></dd>
        </div>
      </dl>
    </section>
  );
}
