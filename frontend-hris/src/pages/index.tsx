import Head from "next/head";
import type { GetStaticProps } from "next";
import { serverSideTranslations } from "next-i18next/pages/serverSideTranslations";
import { useTranslation } from "next-i18next/pages";
import PlatformLayout from "@/components/platform/PlatformLayout";

const metricKeys = ["employees", "candidates", "documents", "settings"];
const activityKeys = ["consent", "document", "recruitment", "setting"];

export default function DashboardPage() {
  const { t } = useTranslation("platform");

  return (
    <PlatformLayout
      titleKey="platform:platform.dashboard.title"
      descriptionKey="platform:platform.dashboard.description"
    >
      <Head>
        <title>{t("platform.dashboard.meta")}</title>
      </Head>
      <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {metricKeys.map((key) => (
          <article key={key} className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-sm font-medium text-slate-500">
              {t(`platform.dashboard.metrics.${key}.label`)}
            </p>
            <p className="mt-3 text-3xl font-semibold text-slate-950">
              {t(`platform.dashboard.metrics.${key}.value`)}
            </p>
            <p className="mt-2 text-sm text-slate-600">
              {t(`platform.dashboard.metrics.${key}.helper`)}
            </p>
          </article>
        ))}
      </section>

      <section className="mt-6 grid gap-4 lg:grid-cols-[1.4fr_1fr]">
        <article className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
          <h2 className="text-lg font-semibold text-slate-950">
            {t("platform.dashboard.modules.title")}
          </h2>
          <div className="mt-4 grid gap-3 md:grid-cols-3">
            {["employees", "recruitment", "settings"].map((key) => (
              <div key={key} className="rounded-md border border-slate-200 p-4">
                <p className="text-sm font-semibold text-slate-950">
                  {t(`platform.dashboard.modules.${key}.title`)}
                </p>
                <p className="mt-2 text-sm leading-6 text-slate-600">
                  {t(`platform.dashboard.modules.${key}.body`)}
                </p>
              </div>
            ))}
          </div>
        </article>

        <article className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
          <h2 className="text-lg font-semibold text-slate-950">
            {t("platform.dashboard.activity.title")}
          </h2>
          <div className="mt-4 space-y-3">
            {activityKeys.map((key) => (
              <div key={key} className="border-l-2 border-emerald-500 pl-3">
                <p className="text-sm font-medium text-slate-950">
                  {t(`platform.dashboard.activity.${key}.title`)}
                </p>
                <p className="mt-1 text-xs text-slate-500">
                  {t(`platform.dashboard.activity.${key}.time`)}
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
