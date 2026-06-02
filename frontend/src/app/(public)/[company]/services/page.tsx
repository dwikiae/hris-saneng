"use client";

import { useTranslation } from "react-i18next";
import { SectionHeader } from "@/components/core/public-layout/SectionHeader";

const itemKeys = ["employee", "recruitment", "website"] as const;

export default function ServicesPage() {
  const { t } = useTranslation("services");

  return (
    <main>
      <SectionHeader namespace="services" eyebrowKey="page.eyebrow" titleKey="page.title" bodyKey="page.body" />
      <section className="mx-auto grid max-w-6xl gap-4 px-4 py-10 sm:px-6 md:grid-cols-3 lg:px-8">
        {itemKeys.map((key) => (
          <article key={key} className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 className="text-lg font-semibold text-slate-950">{t(`items.${key}.title`)}</h2>
            <p className="mt-3 text-sm leading-6 text-slate-600">{t(`items.${key}.body`)}</p>
          </article>
        ))}
      </section>
    </main>
  );
}
