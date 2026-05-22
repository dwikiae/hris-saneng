import Head from "next/head";
import Image from "next/image";
import type { GetStaticProps } from "next";
import { serverSideTranslations } from "next-i18next/pages/serverSideTranslations";
import { useTranslation } from "react-i18next";
import Layout from "@/components/layout/Layout";

const contactKeys = ["address", "email", "phone", "whatsapp"];

export default function ContactPage() {
  const { t } = useTranslation(["common", "contact"]);

  return (
    <Layout>
      <Head>
        <title>{t("contact:meta.title")}</title>
      </Head>
      <main>
        <section className="relative h-[420px] overflow-hidden bg-slate-950 text-white">
          <Image
            src="https://images.unsplash.com/photo-1497366216548-37526070297c?w=1600&q=80"
            alt={t("contact:hero.imageAlt")}
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
              {t("contact:hero.title")}
            </h1>
            <p className="mt-4 max-w-2xl text-base leading-7 text-slate-100">
              {t("contact:hero.body")}
            </p>
          </div>
        </section>

        <section className="bg-white py-14">
          <div className="mx-auto grid w-full max-w-6xl gap-8 px-4 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8">
            <div className="space-y-4">
              {contactKeys.map((key) => (
                <div key={key} className="rounded-lg border border-slate-200 p-5">
                  <p className="text-sm font-semibold uppercase tracking-wide text-sky-700">
                    {t(`contact:info.${key}.label`)}
                  </p>
                  <p className="mt-2 text-base leading-7 text-slate-700">
                    {t(`common:contact.${key}`)}
                  </p>
                </div>
              ))}
            </div>

            <div className="flex min-h-[360px] flex-col justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
              <h2 className="text-2xl font-bold text-slate-950">
                {t("contact:map.title")}
              </h2>
              <p className="mx-auto mt-3 max-w-lg text-sm leading-6 text-slate-600">
                {t("contact:map.body")}
              </p>
              <a
                href="https://maps.google.com/?q=Kawasan+Industri+Pulogadung"
                target="_blank"
                rel="noopener noreferrer"
                className="mx-auto mt-5 inline-flex rounded-md bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
              >
                {t("contact:map.link")}
              </a>
            </div>
          </div>
        </section>
      </main>
    </Layout>
  );
}

export const getStaticProps: GetStaticProps = async ({ locale }) => ({
  props: {
    ...(await serverSideTranslations(locale ?? "id", ["common", "contact"])),
  },
});
