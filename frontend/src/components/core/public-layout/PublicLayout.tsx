"use client";

import Link from "next/link";
import { useParams, usePathname } from "next/navigation";
import type { ReactNode } from "react";
import { useTranslation } from "react-i18next";
import { LanguageSwitcher } from "@/components/shared/LanguageSwitcher";

interface PublicLayoutProps {
  children: ReactNode;
}

const navItems = [
  { segment: "", labelKey: "nav.home" },
  { segment: "about", labelKey: "nav.about" },
  { segment: "services", labelKey: "nav.services" },
  { segment: "karir", labelKey: "nav.careers" },
  { segment: "contact", labelKey: "nav.contact" }
];

export function PublicLayout({ children }: PublicLayoutProps) {
  const params = useParams<{ company: string }>();
  const pathname = usePathname();
  const { t } = useTranslation("common");
  const company = params.company ?? "company";

  return (
    <div className="min-h-screen bg-slate-50 text-slate-950">
      <header className="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div className="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
          <Link href={`/${company}`} className="min-w-0">
            <p className="text-xs font-semibold uppercase tracking-wide text-sky-700">
              {t("brand.eyebrow")}
            </p>
            <p className="truncate text-xl font-bold text-slate-950">{t("brand.name")}</p>
          </Link>
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
            <nav className="flex gap-2 overflow-x-auto">
              {navItems.map((item) => {
                const href = item.segment ? `/${company}/${item.segment}` : `/${company}`;
                const active = pathname === href;

                return (
                  <Link
                    key={item.segment || "home"}
                    href={href}
                    className={
                      active
                        ? "shrink-0 rounded-md bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-800"
                        : "shrink-0 rounded-md px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
                    }
                  >
                    {t(item.labelKey)}
                  </Link>
                );
              })}
            </nav>
            <LanguageSwitcher />
          </div>
        </div>
      </header>
      {children}
      <footer className="border-t border-slate-200 bg-white">
        <div className="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-6 text-sm text-slate-500 sm:px-6 lg:px-8">
          <p className="font-semibold text-slate-800">{t("brand.name")}</p>
          <p>{t("brand.shortDescription")}</p>
        </div>
      </footer>
    </div>
  );
}
