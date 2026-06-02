"use client";

import i18next from "i18next";
import type { ReactNode } from "react";
import { useEffect } from "react";
import { I18nextProvider } from "react-i18next";
import { defaultLanguage, resources } from "@/i18n/resources";

interface LocaleProviderProps {
  children: ReactNode;
}

if (!i18next.isInitialized) {
  void i18next.init({
    resources,
    lng: defaultLanguage,
    fallbackLng: defaultLanguage,
    defaultNS: "common",
    interpolation: {
      escapeValue: false
    }
  });
}

export function LocaleProvider({ children }: LocaleProviderProps) {
  useEffect(() => {
    const savedLanguage = window.localStorage.getItem("dictive-hr.language");

    if (savedLanguage === "id" || savedLanguage === "en") {
      void i18next.changeLanguage(savedLanguage);
    }
  }, []);

  return <I18nextProvider i18n={i18next}>{children}</I18nextProvider>;
}
