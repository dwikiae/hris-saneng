"use client";

import { useMutation } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import { platformToast } from "@/components/platform/ToastProvider";
import { supportedLanguages } from "@/i18n/resources";
import { authService } from "@/services/auth.service";
import { useAuthStore } from "@/stores/auth.store";
import type { PlatformLanguage } from "@/types/platform";

export function LanguageSwitcher() {
  const { i18n, t } = useTranslation("common");
  const user = useAuthStore((state) => state.user);
  const setUser = useAuthStore((state) => state.setUser);
  const mutation = useMutation({
    mutationFn: (language: PlatformLanguage) => authService.updatePreferences(language),
    onSuccess: (_, language) => {
      if (user) {
        setUser({ ...user, language_preference: language });
      }
    },
    onError: () => {
      platformToast.error(t("form.failed"));
    }
  });

  const changeLanguage = (language: PlatformLanguage) => {
    window.localStorage.setItem("dictive-hr.language", language);
    void i18n.changeLanguage(language);

    if (user) {
      mutation.mutate(language);
    }
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
          disabled={mutation.isPending}
          onClick={() => changeLanguage(language)}
        >
          {t(`language.${language}`)}
        </button>
      ))}
    </div>
  );
}
