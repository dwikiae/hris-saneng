import Head from "next/head";
import type { GetStaticProps } from "next";
import { serverSideTranslations } from "next-i18next/pages/serverSideTranslations";
import { useTranslation } from "next-i18next/pages";
import PlatformLayout from "@/components/platform/PlatformLayout";

const employeeRows = ["asep", "rina", "budi", "siti"];
const columnKeys = ["name", "department", "position", "status"];

export default function EmployeesPage() {
  const { t } = useTranslation("platform");

  return (
    <PlatformLayout
      titleKey="platform:platform.employees.title"
      descriptionKey="platform:platform.employees.description"
    >
      <Head>
        <title>{t("platform.employees.meta")}</title>
      </Head>
      <section className="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div className="flex flex-col gap-3 border-b border-slate-200 p-5 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 className="text-lg font-semibold text-slate-950">
              {t("platform.employees.table.title")}
            </h2>
            <p className="mt-1 text-sm text-slate-600">
              {t("platform.employees.table.description")}
            </p>
          </div>
          <button className="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800">
            {t("platform.employees.actions.add")}
          </button>
        </div>
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-slate-200 text-sm">
            <thead className="bg-slate-50">
              <tr>
                {columnKeys.map((key) => (
                  <th key={key} className="px-5 py-3 text-left font-semibold text-slate-600">
                    {t(`platform.employees.columns.${key}`)}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-200 bg-white">
              {employeeRows.map((key) => (
                <tr key={key}>
                  {columnKeys.map((column) => (
                    <td key={column} className="px-5 py-4 text-slate-700">
                      {t(`platform.employees.rows.${key}.${column}`)}
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    </PlatformLayout>
  );
}

export const getStaticProps: GetStaticProps = async ({ locale }) => ({
  props: {
    ...(await serverSideTranslations(locale ?? "id", ["common", "platform"])),
  },
});
