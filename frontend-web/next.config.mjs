import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

/** @type {import('next').NextConfig} */
const nextConfig = {
  i18n: {
    defaultLocale: "id",
    locales: ["id", "en"],
  },
  images: {
    remotePatterns: [
      {
        protocol: "https",
        hostname: "images.unsplash.com",
      },
    ],
  },
  reactStrictMode: true,
  webpack: (config) => {
    config.resolve.alias["next-i18next/pages$"] = path.resolve(
      __dirname,
      "src/lib/next-i18next-pages.ts",
    );

    return config;
  },
};

export default nextConfig;
