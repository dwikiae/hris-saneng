"use client";

import { Bell, Menu, Search } from "lucide-react";
import { useTranslation } from "react-i18next";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from "@/components/ui/dropdown-menu";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

interface TopbarProps {
  onOpenSidebar: () => void;
}

export function Topbar({ onOpenSidebar }: TopbarProps) {
  const { t } = useTranslation("platform");

  return (
    <header className="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-border bg-background px-4 lg:px-6">
      <Button
        type="button"
        variant="ghost"
        size="icon"
        className="lg:hidden"
        aria-label={t("actions.openNavigation")}
        onClick={onOpenSidebar}
      >
        <Menu className="h-5 w-5" aria-hidden="true" />
      </Button>

      <div className="relative hidden w-full max-w-xl md:block">
        <Search
          className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
          aria-hidden="true"
        />
        <Input
          type="search"
          placeholder={t("topbar.searchPlaceholder")}
          className="h-9 rounded-md pl-9"
        />
      </div>

      <div className="ml-auto flex items-center gap-2">
        <Button type="button" variant="ghost" size="icon" aria-label={t("topbar.notifications")}>
          <span className="relative">
            <Bell className="h-5 w-5" aria-hidden="true" />
            <span className="absolute -right-1 -top-1 h-2 w-2 rounded-full bg-destructive" />
          </span>
        </Button>

        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button type="button" variant="ghost" className="h-10 gap-2 px-2">
              <Avatar className="h-8 w-8">
                <AvatarFallback>{t("account.initials")}</AvatarFallback>
              </Avatar>
              <span className="hidden text-sm font-medium text-foreground sm:inline">
                {t("account.name")}
              </span>
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" className="w-48">
            <DropdownMenuLabel>{t("account.name")}</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem>{t("nav.profile")}</DropdownMenuItem>
            <DropdownMenuItem>{t("nav.logout")}</DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </header>
  );
}
