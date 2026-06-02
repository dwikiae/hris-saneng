"use client";

import Image from "next/image";
import Link from "next/link";
import { useParams } from "next/navigation";
import { useTranslation } from "react-i18next";

const statKeys = ["companies", "modules", "candidate"] as const;
const serviceKeys = ["dashboard", "website", "candidate"] as const;

export default function CompanyHomePage() {
  const { t } = useTranslation(["common", "home"]);
  const params = useParams<{ company: string }>();
  const company = params.company ?? "company";

  return (
    <main>
      <section className="relative min-h-[560px] overflow-hidden bg-slate-950 text-white">
        <Image
          src="https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=1600&q=80"
          alt={t("home:hero.imageAlt")}
          fill
          priority
          sizes="100vw"
          className="object-cover opacity-45"
        />
        <div className="relative mx-auto flex min-h-[560px] w-full max-w-6xl flex-col justify-center px-4 py-16 sm:px-6 lg:px-8">
          <p className="text-sm font-semibold uppercase tracking-wide text-sky-200">
            {t("common:brand.tagline")}
          </p>
          <h1 className="mt-4 max-w-4xl text-4xl font-bold leading-tight text-white sm:text-6xl">
            {t("home:hero.headline")}
          </h1>
          <p className="mt-5 max-w-2xl text-lg leading-8 text-slate-100">{t("home:hero.sub")}</p>
          <Link
            href={`/${company}/karir`}
            className="mt-8 inline-flex w-fit rounded-md bg-sky-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-500"
          >
            {t("home:hero.cta")}
          </Link>
        </div>
      </section>
      <section className="bg-white py-12">
        <div className="mx-auto grid w-full max-w-6xl gap-4 px-4 sm:px-6 md:grid-cols-3 lg:px-8">
          {statKeys.map((key) => (
            <div key={key} className="border-l-4 border-sky-600 bg-slate-50 p-5">
              <p className="text-3xl font-bold text-slate-950">{t(`home:stats.${key}.value`)}</p>
              <p className="mt-2 text-sm font-medium text-slate-600">
                {t(`home:stats.${key}.label`)}
              </p>
            </div>
          ))}
        </div>
      </section>
      <section className="py-14">
        <div className="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
          <h2 className="text-3xl font-bold text-slate-950">{t("home:sections.serviceTitle")}</h2>
          <div className="mt-8 grid gap-4 md:grid-cols-3">
            {serviceKeys.map((key) => (
              <article key={key} className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 className="text-lg font-semibold text-slate-950">
                  {t(`home:services.${key}.title`)}
                </h3>
                <p className="mt-3 text-sm leading-6 text-slate-600">
                  {t(`home:services.${key}.body`)}
                </p>
              </article>
            ))}
          </div>
        </div>
      </section>
      <section className="bg-slate-900 py-12 text-white">
        <div className="mx-auto flex w-full max-w-6xl flex-col gap-5 px-4 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
          <div>
            <h2 className="text-3xl font-bold text-white">{t("home:cta.title")}</h2>
            <p className="mt-2 text-sm text-slate-300">{t("home:cta.body")}</p>
          </div>
          <Link
            href="/dashboard"
            className="inline-flex w-fit rounded-md bg-white px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-slate-200"
          >
            {t("home:cta.button")}
          </Link>
        </div>
      </section>
    </main>
  );
}
