import { useTranslation } from "next-i18next/pages";

type Locale = "id" | "en";

export default function LanguageSwitcher() {
  const { t, i18n } = useTranslation("common");

  const currentLocale = (i18n.language ?? "id") as Locale;
  const nextLocale: Locale = currentLocale === "id" ? "en" : "id";

  const handleSwitch = () => {
    // TODO Sprint 3: replace localStorage with PATCH /api/v1/users/me/language
    void i18n.changeLanguage(nextLocale);
    localStorage.setItem("lang", nextLocale);
  };

  return (
    <button
      onClick={handleSwitch}
      aria-label={t("language.switch")}
      type="button"
    >
      {t(`language.${nextLocale}`)}
    </button>
  );
}
