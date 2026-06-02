import Head from "next/head";
import type { GetStaticProps } from "next";
import { serverSideTranslations } from "next-i18next/pages/serverSideTranslations";
import { useTranslation } from "next-i18next/pages";
import { useRouter } from "next/router";
import { FormEvent, useState } from "react";

type PortalType = "quiz" | "pemberkasan";

const portalTypes: PortalType[] = ["quiz", "pemberkasan"];

export default function CandidatePortalPage() {
  const { t } = useTranslation("common");
  const router = useRouter();
  const [tokens, setTokens] = useState<Record<PortalType, string>>({
    quiz: "",
    pemberkasan: "",
  });

  const openPortal = (event: FormEvent, type: PortalType) => {
    event.preventDefault();

    const token = tokens[type].trim();
    if (!token) {
      return;
    }

    void router.push(`/${type}/${encodeURIComponent(token)}`);
  };

  return (
    <>
      <Head>
        <title>{t("home.meta.title")}</title>
      </Head>
      <main className="min-h-screen bg-slate-50 text-slate-950">
        <section className="border-b border-slate-200 bg-white">
          <div className="mx-auto w-full max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
            <p className="text-sm font-semibold uppercase tracking-wide text-emerald-700">
              {t("home.hero.eyebrow")}
            </p>
            <h1 className="mt-3 max-w-3xl text-3xl font-semibold sm:text-4xl">
              {t("home.hero.title")}
            </h1>
            <p className="mt-4 max-w-2xl text-base leading-7 text-slate-600">
              {t("home.hero.body")}
            </p>
          </div>
        </section>

        <section className="mx-auto grid w-full max-w-5xl gap-4 px-4 py-8 sm:px-6 md:grid-cols-2 lg:px-8">
          {portalTypes.map((type) => (
            <form
              key={type}
              onSubmit={(event) => openPortal(event, type)}
              className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
            >
              <h2 className="text-lg font-semibold text-slate-950">
                {t(`home.portal.${type}.title`)}
              </h2>
              <p className="mt-2 min-h-12 text-sm leading-6 text-slate-600">
                {t(`home.portal.${type}.body`)}
              </p>
              <label className="mt-5 block">
                <span className="text-sm font-medium text-slate-700">
                  {t("home.form.token")}
                </span>
                <input
                  value={tokens[type]}
                  onChange={(event) =>
                    setTokens((current) => ({
                      ...current,
                      [type]: event.target.value,
                    }))
                  }
                  className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                  placeholder={t("home.form.placeholder")}
                />
              </label>
              <button
                type="submit"
                className="mt-4 w-full rounded-md bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                disabled={!tokens[type].trim()}
              >
                {t(`home.portal.${type}.action`)}
              </button>
            </form>
          ))}
        </section>
      </main>
    </>
  );
}

export const getStaticProps: GetStaticProps = async ({ locale }) => ({
  props: {
    ...(await serverSideTranslations(locale ?? "id", ["common"])),
  },
});
