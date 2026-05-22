import Link from "next/link";
import { useTranslation } from "react-i18next";

const footerLinks = [
  { href: "/", key: "home" },
  { href: "/about", key: "about" },
  { href: "/services", key: "services" },
  { href: "/contact", key: "contact" },
];

export default function Footer() {
  const { t } = useTranslation("common");

  return (
    <footer className="border-t border-slate-200 bg-slate-950 text-white">
      <div className="mx-auto grid w-full max-w-6xl gap-8 px-4 py-8 sm:px-6 md:grid-cols-[1fr_auto] lg:px-8">
        <div>
          <p className="text-lg font-bold">{t("company.name")}</p>
          <p className="mt-2 max-w-xl text-sm leading-6 text-slate-300">
            {t("company.shortDescription")}
          </p>
          <p className="mt-4 text-sm text-slate-400">{t("footer.copyright")}</p>
        </div>
        <div className="flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-300 md:justify-end">
          {footerLinks.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className="transition hover:text-white"
            >
              {t(`nav.${item.key}`)}
            </Link>
          ))}
        </div>
      </div>
    </footer>
  );
}
