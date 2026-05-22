import Head from "next/head";
import Image from "next/image";
import type { GetStaticProps } from "next";
import { serverSideTranslations } from "next-i18next/pages/serverSideTranslations";
import { useTranslation } from "react-i18next";
import Layout from "@/components/layout/Layout";

const serviceKeys = ["painting", "corrosion", "floor", "sandblasting", "heat", "maintenance"];

export default function ServicesPage() {
  const { t } = useTranslation(["common", "services"]);

  return (
    <Layout>
      <Head>
        <title>{t("services:meta.title")}</title>
      </Head>
      <main>
        <section className="relative h-[420px] overflow-hidden bg-slate-950 text-white">
          <Image
            src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1600&q=80"
            alt={t("services:hero.imageAlt")}
            fill
            priority
            sizes="100vw"
            className="object-cover opacity-50"
          />
          <div className="relative mx-auto flex h-full w-full max-w-6xl flex-col justify-center px-4 sm:px-6 lg:px-8">
            <p className="text-sm font-semibold uppercase tracking-wide text-sky-200">
              {t("common:company.tagline")}
            </p>
            <h1 className="mt-3 max-w-3xl text-4xl font-bold sm:text-5xl">
              {t("services:hero.title")}
            </h1>
            <p className="mt-4 max-w-2xl text-base leading-7 text-slate-100">
              {t("services:hero.body")}
            </p>
          </div>
        </section>

        <section className="py-14">
          <div className="mx-auto grid w-full max-w-6xl gap-4 px-4 sm:px-6 md:grid-cols-2 lg:grid-cols-3 lg:px-8">
            {serviceKeys.map((key) => (
              <article key={key} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <p className="text-sm font-semibold uppercase tracking-wide text-sky-700">
                  {t(`services:cards.${key}.eyebrow`)}
                </p>
                <h2 className="mt-3 text-xl font-bold text-slate-950">
                  {t(`services:cards.${key}.title`)}
                </h2>
                <p className="mt-4 text-sm leading-7 text-slate-600">
                  {t(`services:cards.${key}.body`)}
                </p>
              </article>
            ))}
          </div>
        </section>
      </main>
    </Layout>
  );
}

export const getStaticProps: GetStaticProps = async ({ locale }) => ({
  props: {
    ...(await serverSideTranslations(locale ?? "id", ["common", "services"])),
  },
});
