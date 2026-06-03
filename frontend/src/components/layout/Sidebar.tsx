"use client";

import {
  BriefcaseBusiness,
  Building2,
  CalendarDays,
  Gauge,
  LogOut,
  Settings,
  UserCircle,
  Users
} from "lucide-react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { useTranslation } from "react-i18next";
import { PermissionGate } from "@/components/platform/PermissionGate";
import { Separator } from "@/components/ui/separator";
import { cn } from "@/lib/utils";

const mainNavItems = [
  { href: "/dashboard", labelKey: "nav.dashboard", icon: Gauge },
  { href: "/dashboard/employees", labelKey: "nav.employees", icon: Users },
  { href: "/dashboard/calendar", labelKey: "nav.calendar", icon: CalendarDays },
  { href: "/dashboard/recruitment", labelKey: "nav.recruitment", icon: BriefcaseBusiness },
  { href: "/dashboard/assets", labelKey: "nav.assets", icon: Building2 },
  { href: "/dashboard/website", labelKey: "nav.website", icon: Building2 }
];

const footerNavItems = [
  { href: "/dashboard/settings", labelKey: "nav.settings", icon: Settings },
  { href: "/dashboard/profile", labelKey: "nav.profile", icon: UserCircle },
  { href: "/dashboard/logout", labelKey: "nav.logout", icon: LogOut }
];

function isActivePath(pathname: string, href: string): boolean {
  return href === "/dashboard" ? pathname === href : pathname.startsWith(href);
}

interface SidebarProps {
  onNavigate?: () => void;
}

export function Sidebar({ onNavigate }: SidebarProps) {
  const pathname = usePathname();
  const { t } = useTranslation("platform");

  return (
    <div className="flex h-full w-[240px] flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground">
      <div className="flex h-14 items-center gap-3 border-b border-sidebar-border px-5">
        <div className="flex h-8 w-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
          <Building2 className="h-4 w-4" aria-hidden="true" />
        </div>
        <div className="min-w-0">
          <p className="truncate text-sm font-semibold">{t("brand.name")}</p>
          <p className="truncate text-xs text-muted-foreground">{t("brand.eyebrow")}</p>
        </div>
      </div>

      <nav className="flex-1 space-y-1 px-3 py-4">
        {mainNavItems.map((item) => {
          const Icon = item.icon;
          const active = isActivePath(pathname, item.href);

          return (
            <Link
              key={item.href}
              href={item.href}
              onClick={onNavigate}
              className={cn(
                "flex h-10 items-center gap-3 rounded-md border-l-2 border-transparent px-3 text-sm font-medium text-muted-foreground transition hover:bg-slate-50 hover:text-foreground",
                active && "border-l-primary bg-blue-100 text-blue-700 hover:bg-blue-100 hover:text-blue-700"
              )}
            >
              <Icon className="h-4 w-4" aria-hidden="true" />
              <span className="truncate">{t(item.labelKey)}</span>
            </Link>
          );
        })}
      </nav>

      <div className="px-3 pb-4">
        <Separator className="mb-3" />
        <nav className="space-y-1">
          {footerNavItems.map((item) => {
            const Icon = item.icon;
            const active = isActivePath(pathname, item.href);
            const link = (
              <Link
                key={item.href}
                href={item.href}
                onClick={onNavigate}
                className={cn(
                  "flex h-10 items-center gap-3 rounded-md border-l-2 border-transparent px-3 text-sm font-medium text-muted-foreground transition hover:bg-slate-50 hover:text-foreground",
                  active && "border-l-primary bg-blue-100 text-blue-700 hover:bg-blue-100 hover:text-blue-700"
                )}
              >
                <Icon className="h-4 w-4" aria-hidden="true" />
                <span className="truncate">{t(item.labelKey)}</span>
              </Link>
            );

            return item.href === "/dashboard/settings" ? (
              <PermissionGate key={item.href} permission="platform.settings">
                {link}
              </PermissionGate>
            ) : (
              link
            );
          })}
        </nav>
      </div>
    </div>
  );
}
