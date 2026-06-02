import i18next from "i18next";
import { initReactI18next } from "react-i18next";
import aboutEn from "../../public/locales/en/about.json";
import commonEn from "../../public/locales/en/common.json";
import contactEn from "../../public/locales/en/contact.json";
import homeEn from "../../public/locales/en/home.json";
import recruitmentEn from "../../public/locales/en/recruitment.json";
import servicesEn from "../../public/locales/en/services.json";
import aboutId from "../../public/locales/id/about.json";
import commonId from "../../public/locales/id/common.json";
import contactId from "../../public/locales/id/contact.json";
import homeId from "../../public/locales/id/home.json";
import recruitmentId from "../../public/locales/id/recruitment.json";
import servicesId from "../../public/locales/id/services.json";

if (!i18next.isInitialized) {
  void i18next.use(initReactI18next).init({
    defaultNS: "common",
    fallbackLng: "id",
    interpolation: {
      escapeValue: false,
    },
    lng: "id",
    ns: ["common", "home", "about", "services", "contact", "recruitment"],
    resources: {
      en: {
        about: aboutEn,
        common: commonEn,
        contact: contactEn,
        home: homeEn,
        recruitment: recruitmentEn,
        services: servicesEn,
      },
      id: {
        about: aboutId,
        common: commonId,
        contact: contactId,
        home: homeId,
        recruitment: recruitmentId,
        services: servicesId,
      },
    },
  });
}

export default i18next;

