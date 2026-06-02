import Link from "next/link";
import { useRouter } from "next/router";
import { useTranslation } from "next-i18next/pages";
import type { ReactNode } from "react";

interface PlatformLayoutProps {
  children: ReactNode;
  titleKey: string;
  descriptionKey: string;
}

const navItems = [
  { href: "/", labelKey: "platform:platform.nav.dashboard" },
  { href: "/employees", labelKey: "platform:platform.nav.employees" },
  { href: "/recruitment", labelKey: "platform:platform.nav.recruitment" },
  { href: "/settings", labelKey: "platform:platform.nav.settings" },
];

function isActivePath(pathname: string, href: string): boolean {
  if (href === "/") {
    return pathname === "/";
  }

  return pathname.startsWith(href);
}

export default function PlatformLayout({
  children,
  titleKey,
  descriptionKey,
}: PlatformLayoutProps) {
  const router = useRouter();
  const { t } = useTranslation(["common", "platform"]);

  return (
    <div className="min-h-screen bg-slate-100 text-slate-950">
      <aside className="fixed inset-y-0 left-0 hidden w-64 border-r border-slate-200 bg-white lg:block">
        <div className="border-b border-slate-200 px-5 py-5">
          <p className="text-sm font-semibold uppercase tracking-wide text-emerald-700">
            {t("platform:platform.brand.eyebrow")}
          </p>
          <p className="mt-1 text-xl font-bold">{t("platform:platform.brand.name")}</p>
        </div>
        <nav className="space-y-1 px-3 py-4">
          {navItems.map((item) => {
            const active = isActivePath(router.pathname, item.href);

            return (
              <Link
                key={item.href}
                href={item.href}
                className={
                  active
                    ? "block rounded-md bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800"
                    : "block rounded-md px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-950"
                }
              >
                {t(item.labelKey)}
              </Link>
            );
          })}
        </nav>
        <div className="absolute bottom-0 w-full border-t border-slate-200 p-4">
          <Link
            href="/portal"
            className="block rounded-md border border-slate-300 px-3 py-2 text-center text-sm font-semibold text-slate-700 transition hover:border-emerald-500 hover:text-emerald-700"
          >
            {t("platform:platform.nav.candidatePortal")}
          </Link>
        </div>
      </aside>

      <div className="lg:pl-64">
        <header className="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
          <div className="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <div className="flex flex-col gap-3 lg:hidden">
              <div>
                <p className="text-sm font-semibold uppercase tracking-wide text-emerald-700">
                  {t("platform:platform.brand.eyebrow")}
                </p>
                <p className="text-lg font-bold">{t("platform:platform.brand.name")}</p>
              </div>
              <nav className="flex gap-2 overflow-x-auto pb-1">
                {navItems.map((item) => {
                  const active = isActivePath(router.pathname, item.href);

                  return (
                    <Link
                      key={item.href}
                      href={item.href}
                      className={
                        active
                          ? "shrink-0 rounded-md bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800"
                          : "shrink-0 rounded-md px-3 py-2 text-sm font-medium text-slate-600"
                      }
                    >
                      {t(item.labelKey)}
                    </Link>
                  );
                })}
              </nav>
            </div>
            <div>
              <h1 className="text-2xl font-semibold text-slate-950">{t(titleKey)}</h1>
              <p className="mt-1 max-w-3xl text-sm leading-6 text-slate-600">
                {t(descriptionKey)}
              </p>
            </div>
          </div>
        </header>
        <main className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">{children}</main>
      </div>
    </div>
  );
}
