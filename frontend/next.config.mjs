/** @type {import('next').NextConfig} */
const rawApiBaseUrl = process.env.API_BASE_URL ?? process.env.NEXT_PUBLIC_API_BASE_URL ?? "http://localhost:8000/api/v1";
const apiBaseUrl = rawApiBaseUrl.replace(/\/$/, "");
const apiV1BaseUrl = apiBaseUrl.endsWith("/api/v1") ? apiBaseUrl : `${apiBaseUrl}/api/v1`;

const nextConfig = {
  reactStrictMode: true,
  images: {
    remotePatterns: [
      {
        protocol: "https",
        hostname: "images.unsplash.com"
      }
    ]
  },
  async redirects() {
    return [
      {
        source: "/dashboard/settings",
        destination: "/dashboard/settings/companies",
        permanent: false
      }
    ];
  },
  async rewrites() {
    return [
      {
        source: "/api/v1/:path*",
        destination: `${apiV1BaseUrl}/:path*`
      }
    ];
  }
};

export default nextConfig;
