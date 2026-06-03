"use client";

import type { ReactNode } from "react";
import { Building2 } from "lucide-react";
import { useTranslation } from "react-i18next";

interface AuthShellProps {
  title: string;
  description: string;
  children: ReactNode;
}

export function AuthShell({ title, description, children }: AuthShellProps) {
  const { t } = useTranslation("platform");

  return (
    <main className="flex min-h-screen bg-slate-50">
      <section className="hidden flex-1 flex-col justify-between bg-primary px-10 py-10 text-primary-foreground lg:flex">
        <div className="flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-white/15">
            <Building2 className="h-5 w-5" aria-hidden="true" />
          </div>
          <div>
            <p className="text-base font-semibold">{t("brand.name")}</p>
            <p className="text-sm text-white/75">{t("brand.eyebrow")}</p>
          </div>
        </div>
        <div className="max-w-md">
          <p className="text-3xl font-semibold leading-tight">{t("auth.hero.title")}</p>
          <p className="mt-4 text-sm leading-6 text-white/75">{t("auth.hero.description")}</p>
        </div>
      </section>
      <section className="flex min-h-screen flex-1 items-center justify-center px-4 py-8">
        <div className="w-full max-w-md rounded-lg border border-border bg-background p-6 shadow-sm">
          <div className="mb-6 lg:hidden">
            <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-primary-foreground">
              <Building2 className="h-5 w-5" aria-hidden="true" />
            </div>
          </div>
          <h1 className="text-2xl font-semibold text-foreground">{title}</h1>
          <p className="mt-2 text-sm leading-6 text-muted-foreground">{description}</p>
          <div className="mt-6">{children}</div>
        </div>
      </section>
    </main>
  );
}
