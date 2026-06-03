"use client";

import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Bell } from "lucide-react";
import Link from "next/link";
import { useEffect } from "react";
import { useTranslation } from "react-i18next";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from "@/components/ui/dropdown-menu";
import { connectNotificationSocket, notificationService } from "@/services/platform.service";
import { useAuthStore } from "@/stores/auth.store";
import type { PlatformNotificationList } from "@/types/platform";

const notificationQueryKey = ["platform", "notifications"];

export function NotificationBell() {
  const { t } = useTranslation("platform");
  const queryClient = useQueryClient();
  const user = useAuthStore((state) => state.user);
  const userId = user?.id;
  const { data } = useQuery({
    queryKey: notificationQueryKey,
    queryFn: () => notificationService.list(10),
    enabled: Boolean(userId)
  });
  const items = data?.items ?? [];
  const unreadCount = items.filter((item) => !item.read_at).length;

  useEffect(() => {
    if (!userId) {
      return undefined;
    }

    return connectNotificationSocket(userId, (notification) => {
      queryClient.setQueryData<PlatformNotificationList>(notificationQueryKey, (current) => {
        const meta = current?.meta ?? { current_page: 1, per_page: 10, total: 0 };
        return {
          items: [notification, ...(current?.items ?? [])].slice(0, 10),
          meta: { ...meta, total: meta.total + 1 }
        };
      });
    });
  }, [queryClient, userId]);

  const markRead = async (notificationId: number) => {
    await notificationService.markRead(notificationId);
    await queryClient.invalidateQueries({ queryKey: notificationQueryKey });
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button type="button" variant="ghost" size="icon" aria-label={t("topbar.notifications")}>
          <span className="relative">
            <Bell className="h-5 w-5" aria-hidden="true" />
            {unreadCount > 0 ? (
              <span className="absolute -right-2 -top-2 min-w-4 rounded-full bg-destructive px-1 text-[10px] font-semibold text-destructive-foreground">
                {unreadCount}
              </span>
            ) : null}
          </span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-80">
        <DropdownMenuLabel>{t("platformBehavior.notifications.title")}</DropdownMenuLabel>
        <DropdownMenuSeparator />
        {items.length > 0 ? (
          items.map((notification) => (
            <DropdownMenuItem
              key={notification.id}
              className="flex cursor-pointer flex-col items-start gap-1 py-3"
              onClick={() => void markRead(notification.id)}
            >
              <span className="text-sm font-medium text-foreground">
                {t(notification.title_key, { defaultValue: notification.title_key })}
              </span>
              <span className="line-clamp-2 text-xs text-muted-foreground">
                {t(notification.body_key, { defaultValue: notification.body_key })}
              </span>
            </DropdownMenuItem>
          ))
        ) : (
          <div className="px-3 py-6 text-sm text-muted-foreground">
            {t("platformBehavior.notifications.empty")}
          </div>
        )}
        <DropdownMenuSeparator />
        <DropdownMenuItem asChild>
          <Link href="/dashboard/notifications">{t("platformBehavior.notifications.viewAll")}</Link>
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
