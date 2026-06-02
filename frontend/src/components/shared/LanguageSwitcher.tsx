"use client";

import { useTranslation } from "react-i18next";
import { supportedLanguages } from "@/i18n/resources";

export function LanguageSwitcher() {
  const { i18n, t } = useTranslation("common");

  const changeLanguage = (language: string) => {
    window.localStorage.setItem("dictive-hr.language", language);
    void i18n.changeLanguage(language);
  };

  return (
    <div className="inline-flex rounded-md border border-slate-300 bg-white p-1">
      {supportedLanguages.map((language) => (
        <button
          key={language}
          type="button"
          className={
            i18n.language === language
              ? "rounded bg-slate-950 px-3 py-1 text-xs font-semibold text-white"
              : "rounded px-3 py-1 text-xs font-semibold text-slate-600 transition hover:bg-slate-100"
          }
          onClick={() => changeLanguage(language)}
        >
          {t(`language.${language}`)}
        </button>
      ))}
    </div>
  );
}
