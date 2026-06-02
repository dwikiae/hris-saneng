"use client";

import { useTranslation } from "react-i18next";

interface SectionHeaderProps {
  namespace: string;
  eyebrowKey: string;
  titleKey: string;
  bodyKey: string;
}

export function SectionHeader({ namespace, eyebrowKey, titleKey, bodyKey }: SectionHeaderProps) {
  const { t } = useTranslation(namespace);

  return (
    <section className="border-b border-slate-200 bg-white">
      <div className="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
        <p className="text-sm font-semibold uppercase tracking-wide text-sky-700">
          {t(eyebrowKey)}
        </p>
        <h1 className="mt-3 max-w-3xl text-3xl font-semibold text-slate-950 sm:text-4xl">
          {t(titleKey)}
        </h1>
        <p className="mt-4 max-w-2xl text-base leading-7 text-slate-600">{t(bodyKey)}</p>
      </div>
    </section>
  );
}
