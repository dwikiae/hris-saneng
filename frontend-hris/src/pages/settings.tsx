import Head from "next/head";
import type { GetStaticProps } from "next";
import { serverSideTranslations } from "next-i18next/pages/serverSideTranslations";
import { useTranslation } from "next-i18next/pages";
import PlatformLayout from "@/components/platform/PlatformLayout";

const settingKeys = ["company", "permissions", "retention", "integrations"];

export default function SettingsPage() {
  const { t } = useTranslation("platform");

  return (
    <PlatformLayout
      titleKey="platform:platform.settings.title"
      descriptionKey="platform:platform.settings.description"
    >
      <Head>
        <title>{t("platform.settings.meta")}</title>
      </Head>
      <section className="grid gap-4 md:grid-cols-2">
        {settingKeys.map((key) => (
          <article key={key} className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-lg font-semibold text-slate-950">
              {t(`platform.settings.cards.${key}.title`)}
            </p>
            <p className="mt-2 text-sm leading-6 text-slate-600">
              {t(`platform.settings.cards.${key}.body`)}
            </p>
            <button className="mt-5 rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-emerald-500 hover:text-emerald-700">
              {t("platform.settings.actions.open")}
            </button>
          </article>
        ))}
      </section>
    </PlatformLayout>
  );
}

export const getStaticProps: GetStaticProps = async ({ locale }) => ({
  props: {
    ...(await serverSideTranslations(locale ?? "id", ["common", "platform"])),
  },
});
