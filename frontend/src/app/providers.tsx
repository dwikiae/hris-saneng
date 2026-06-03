"use client";

import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import type { ReactNode } from "react";
import { useState } from "react";
import { AuthHydrator } from "@/components/core/platform/AuthHydrator";
import { ToastProvider } from "@/components/platform/ToastProvider";
import { LocaleProvider } from "@/i18n/LocaleProvider";

interface AppProvidersProps {
  children: ReactNode;
}

export function AppProviders({ children }: AppProvidersProps) {
  const [queryClient] = useState(
    () =>
      new QueryClient({
        defaultOptions: {
          queries: {
            staleTime: 60_000
          }
        }
      })
  );

  return (
    <QueryClientProvider client={queryClient}>
      <LocaleProvider>
        <AuthHydrator />
        {children}
        <ToastProvider />
      </LocaleProvider>
    </QueryClientProvider>
  );
}
