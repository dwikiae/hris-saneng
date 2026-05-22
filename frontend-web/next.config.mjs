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
};

export default nextConfig;
