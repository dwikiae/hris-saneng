"use client";

import { useTranslation } from "react-i18next";
import { SectionHeader } from "@/components/core/public-layout/SectionHeader";

export default function AboutPage() {
  const { t } = useTranslation("about");

  return (
    <main>
      <SectionHeader namespace="about" eyebrowKey="page.eyebrow" titleKey="page.title" bodyKey="page.body" />
      <section className="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <div className="rounded-lg border border-slate-200 bg-white p-6 text-sm leading-7 text-slate-600 shadow-sm">
          {t("page.compliance")}
        </div>
      </section>
    </main>
  );
}
