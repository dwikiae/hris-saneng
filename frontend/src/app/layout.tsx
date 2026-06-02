import type { ReactNode } from "react";
import { Inter } from "next/font/google";
import "./globals.css";
import { AppProviders } from "@/app/providers";
import { cn } from "@/lib/utils";

const inter = Inter({
  subsets: ["latin"],
  variable: "--font-inter",
  display: "swap"
});

interface RootLayoutProps {
  children: ReactNode;
}

export default function RootLayout({ children }: RootLayoutProps) {
  return (
    <html lang="id">
      <body className={cn(inter.variable, "font-sans")}>
        <AppProviders>{children}</AppProviders>
      </body>
    </html>
  );
}
