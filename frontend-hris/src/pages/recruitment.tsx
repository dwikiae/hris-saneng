import Head from "next/head";
import type { GetStaticProps } from "next";
import { serverSideTranslations } from "next-i18next/pages/serverSideTranslations";
import { useTranslation } from "next-i18next/pages";
import PlatformLayout from "@/components/platform/PlatformLayout";

const pipelineKeys = ["screening", "quiz", "interview", "documents"];
const requisitionKeys = ["operator", "welder", "admin"];

export default function RecruitmentPage() {
  const { t } = useTranslation("platform");

  return (
    <PlatformLayout
      titleKey="platform:platform.recruitment.title"
      descriptionKey="platform:platform.recruitment.description"
    >
      <Head>
        <title>{t("platform.recruitment.meta")}</title>
      </Head>
      <section className="grid gap-4 lg:grid-cols-[1fr_1.2fr]">
        <article className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
          <h2 className="text-lg font-semibold text-slate-950">
            {t("platform.recruitment.pipeline.title")}
          </h2>
          <div className="mt-4 space-y-3">
            {pipelineKeys.map((key) => (
              <div key={key} className="rounded-md border border-slate-200 p-4">
                <div className="flex items-center justify-between gap-3">
                  <p className="text-sm font-semibold text-slate-950">
                    {t(`platform.recruitment.pipeline.${key}.label`)}
                  </p>
                  <p className="text-sm font-semibold text-emerald-700">
                    {t(`platform.recruitment.pipeline.${key}.count`)}
                  </p>
                </div>
                <p className="mt-2 text-sm text-slate-600">
                  {t(`platform.recruitment.pipeline.${key}.helper`)}
                </p>
              </div>
            ))}
          </div>
        </article>

        <article className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
          <div className="flex items-center justify-between gap-3">
            <h2 className="text-lg font-semibold text-slate-950">
              {t("platform.recruitment.requisitions.title")}
            </h2>
            <button className="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800">
              {t("platform.recruitment.actions.add")}
            </button>
          </div>
          <div className="mt-4 grid gap-3">
            {requisitionKeys.map((key) => (
              <div key={key} className="rounded-md border border-slate-200 p-4">
                <p className="text-sm font-semibold text-slate-950">
                  {t(`platform.recruitment.requisitions.${key}.title`)}
                </p>
                <p className="mt-2 text-sm leading-6 text-slate-600">
                  {t(`platform.recruitment.requisitions.${key}.body`)}
                </p>
              </div>
            ))}
          </div>
        </article>
      </section>
    </PlatformLayout>
  );
}

export const getStaticProps: GetStaticProps = async ({ locale }) => ({
  props: {
    ...(await serverSideTranslations(locale ?? "id", ["common", "platform"])),
  },
});
