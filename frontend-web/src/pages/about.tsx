import Head from "next/head";
import Image from "next/image";
import type { GetStaticProps } from "next";
import { serverSideTranslations } from "next-i18next/pages/serverSideTranslations";
import { useTranslation } from "react-i18next";
import Layout from "@/components/layout/Layout";

const missionKeys = ["one", "two", "three"];
const valueKeys = ["safety", "quality", "integrity"];

export default function AboutPage() {
  const { t } = useTranslation(["common", "about"]);

  return (
    <Layout>
      <Head>
        <title>{t("about:meta.title")}</title>
      </Head>
      <main>
        <section className="relative h-[420px] overflow-hidden bg-slate-950 text-white">
          <Image
            src="https://images.unsplash.com/photo-1581094794329-c8112a89af12?w=1600&q=80"
            alt={t("about:hero.imageAlt")}
            fill
            priority
            sizes="100vw"
            className="object-cover opacity-55"
          />
          <div className="relative mx-auto flex h-full w-full max-w-6xl flex-col justify-center px-4 sm:px-6 lg:px-8">
            <p className="text-sm font-semibold uppercase tracking-wide text-sky-200">
              {t("common:company.name")}
            </p>
            <h1 className="mt-3 max-w-3xl text-4xl font-bold sm:text-5xl">
              {t("about:hero.title")}
            </h1>
          </div>
        </section>

        <section className="bg-white py-14">
          <div className="mx-auto grid w-full max-w-6xl gap-10 px-4 sm:px-6 lg:grid-cols-[1.2fr_0.8fr] lg:px-8">
            <div>
              <h2 className="text-3xl font-bold text-slate-950">
                {t("about:story.title")}
              </h2>
              <p className="mt-5 whitespace-pre-line text-base leading-8 text-slate-600">
                {t("about:story.body")}
              </p>
            </div>
            <div className="rounded-lg bg-slate-50 p-6">
              <h2 className="text-2xl font-bold text-slate-950">
                {t("about:vision.title")}
              </h2>
              <p className="mt-4 text-sm leading-7 text-slate-600">
                {t("about:vision.body")}
              </p>
              <h2 className="mt-8 text-2xl font-bold text-slate-950">
                {t("about:mission.title")}
              </h2>
              <ul className="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                {missionKeys.map((key) => (
                  <li key={key}>{t(`about:mission.items.${key}`)}</li>
                ))}
              </ul>
            </div>
          </div>
        </section>

        <section className="py-14">
          <div className="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
            <h2 className="text-3xl font-bold text-slate-950">
              {t("about:values.title")}
            </h2>
            <div className="mt-8 grid gap-4 md:grid-cols-3">
              {valueKeys.map((key) => (
                <article key={key} className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                  <h3 className="text-lg font-semibold text-slate-950">
                    {t(`about:values.items.${key}.title`)}
                  </h3>
                  <p className="mt-3 text-sm leading-6 text-slate-600">
                    {t(`about:values.items.${key}.body`)}
                  </p>
                </article>
              ))}
            </div>
          </div>
        </section>
      </main>
    </Layout>
  );
}

export const getStaticProps: GetStaticProps = async ({ locale }) => ({
  props: {
    ...(await serverSideTranslations(locale ?? "id", ["common", "about"])),
  },
});
