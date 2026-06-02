"use client";

import type { ReactNode } from "react";
import { Sidebar } from "@/components/layout/Sidebar";
import { Topbar } from "@/components/layout/Topbar";
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle
} from "@/components/ui/sheet";
import { useUiShellStore } from "@/stores/ui-shell.store";
import { useTranslation } from "react-i18next";

interface AppShellProps {
  children: ReactNode;
}

export function AppShell({ children }: AppShellProps) {
  const { t } = useTranslation("platform");
  const isMobileSidebarOpen = useUiShellStore((state) => state.isMobileSidebarOpen);
  const closeMobileSidebar = useUiShellStore((state) => state.closeMobileSidebar);
  const openMobileSidebar = useUiShellStore((state) => state.openMobileSidebar);

  return (
    <div className="min-h-screen bg-surface text-foreground">
      <div className="fixed inset-y-0 left-0 z-40 hidden lg:block">
        <Sidebar />
      </div>

      <Sheet open={isMobileSidebarOpen} onOpenChange={(open) => !open && closeMobileSidebar()}>
        <SheetContent side="left" className="w-[280px] p-0" closeLabel={t("actions.closeNavigation")}>
          <SheetHeader className="sr-only">
            <SheetTitle>{t("navigation.title")}</SheetTitle>
            <SheetDescription>{t("navigation.description")}</SheetDescription>
          </SheetHeader>
          <Sidebar onNavigate={closeMobileSidebar} />
        </SheetContent>
      </Sheet>

      <div className="lg:pl-[240px]">
        <Topbar onOpenSidebar={openMobileSidebar} />
        <main className="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">{children}</main>
      </div>
    </div>
  );
}
