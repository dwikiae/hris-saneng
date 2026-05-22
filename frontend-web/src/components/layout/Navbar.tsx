import Link from "next/link";
import { useRouter } from "next/router";
import { useTranslation } from "react-i18next";

const navItems = [
  { href: "/", key: "home" },
  { href: "/about", key: "about" },
  { href: "/services", key: "services" },
  { href: "/contact", key: "contact" },
  { href: "/karir", key: "career" },
];

function isActivePath(currentPath: string, href: string): boolean {
  if (href === "/") {
    return currentPath === "/";
  }

  return currentPath.startsWith(href);
}

export default function Navbar() {
  const { t } = useTranslation("common");
  const router = useRouter();

  return (
    <header className="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
      <nav className="mx-auto flex w-full max-w-6xl flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
        <Link href="/" className="text-xl font-bold text-slate-950">
          {t("company.name")}
        </Link>
        <div className="flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-600">
          {navItems.map((item) => {
            const active = isActivePath(router.pathname, item.href);

            return (
              <Link
                key={item.href}
                href={item.href}
                className={
                  active
                    ? "font-bold text-slate-950 underline underline-offset-8"
                    : "font-medium transition hover:text-slate-950"
                }
              >
                {t(`nav.${item.key}`)}
              </Link>
            );
          })}
        </div>
      </nav>
    </header>
  );
}
