import "@/styles/globals.css";
import type { AppProps } from "next/app";
import * as nextI18nextPages from "next-i18next/pages";

function App({ Component, pageProps }: AppProps) {
  return <Component {...pageProps} />;
}

const { appWithTranslation } = nextI18nextPages;

export default appWithTranslation(App);
