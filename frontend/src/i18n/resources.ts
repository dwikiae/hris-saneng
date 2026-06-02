import aboutEn from "@/locales/en/about.json";
import commonEn from "@/locales/en/common.json";
import contactEn from "@/locales/en/contact.json";
import homeEn from "@/locales/en/home.json";
import pemberkasanEn from "@/locales/en/pemberkasan.json";
import platformEn from "@/locales/en/platform.json";
import quizEn from "@/locales/en/quiz.json";
import recruitmentEn from "@/locales/en/recruitment.json";
import servicesEn from "@/locales/en/services.json";
import aboutId from "@/locales/id/about.json";
import commonId from "@/locales/id/common.json";
import contactId from "@/locales/id/contact.json";
import homeId from "@/locales/id/home.json";
import pemberkasanId from "@/locales/id/pemberkasan.json";
import platformId from "@/locales/id/platform.json";
import quizId from "@/locales/id/quiz.json";
import recruitmentId from "@/locales/id/recruitment.json";
import servicesId from "@/locales/id/services.json";

export const defaultLanguage = "id";
export const supportedLanguages = ["id", "en"] as const;

export const resources = {
  id: {
    about: aboutId,
    common: commonId,
    contact: contactId,
    home: homeId,
    pemberkasan: pemberkasanId,
    platform: platformId,
    quiz: quizId,
    recruitment: recruitmentId,
    services: servicesId
  },
  en: {
    about: aboutEn,
    common: commonEn,
    contact: contactEn,
    home: homeEn,
    pemberkasan: pemberkasanEn,
    platform: platformEn,
    quiz: quizEn,
    recruitment: recruitmentEn,
    services: servicesEn
  }
};
