"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useTranslation } from "react-i18next";
import { cn } from "@/lib/utils";

const settingsItems = [
  { href: "/dashboard/settings/companies", labelKey: "settingsNav.companies" },
  { href: "/dashboard/settings/users", labelKey: "settingsNav.users" },
  { href: "/dashboard/settings/config", labelKey: "settingsNav.config" },
  { href: "/dashboard/settings/audit", labelKey: "settingsNav.audit" },
  { href: "/dashboard/settings/modules", labelKey: "settingsNav.modules" }
];

export function SettingsNav() {
  const pathname = usePathname();
  const { t } = useTranslation("platform");

  return (
    <nav className="overflow-x-auto border-b border-border pb-3">
      <div className="flex min-w-max gap-2">
        {settingsItems.map((item) => {
          const active = pathname.startsWith(item.href);

          return (
            <Link
              key={item.href}
              href={item.href}
              className={cn(
                "rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition hover:bg-slate-100 hover:text-foreground",
                active && "bg-blue-100 text-blue-700 hover:bg-blue-100 hover:text-blue-700"
              )}
            >
              {t(item.labelKey)}
            </Link>
          );
        })}
      </div>
    </nav>
  );
}
