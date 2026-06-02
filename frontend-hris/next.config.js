/** @type {import('next').NextConfig} */
const { i18n } = require('./next-i18next.config')
const path = require('path')

module.exports = {
  reactStrictMode: true,
  i18n,
  webpack: (config) => {
    config.resolve.alias['next-i18next/pages'] = path.resolve(
      __dirname,
      'src/lib/next-i18next-pages.ts',
    )
    config.resolve.alias['next-i18next/pages/serverSideTranslations'] = path.resolve(
      __dirname,
      'src/lib/next-i18next-pages-serverSideTranslations.ts',
    )

    return config
  },
}
