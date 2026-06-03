"use client";

import Link from "next/link";
import { AlertCircle, ChevronLeft, ChevronRight } from "lucide-react";
import { useTranslation } from "react-i18next";
import { LoadingSkeleton } from "@/components/shared/LoadingSkeleton";
import { Button, type ButtonProps } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import type { PaginationState, SummaryItem, TemplateAction } from "./types";

export function SummaryStrip({ items }: { items: SummaryItem[] }) {
  return (
    <div className="grid gap-3 rounded-lg border border-border bg-card p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
      {items.length > 0
        ? items.map((item) => (
            <div key={item.label}>
              <p className="text-xs font-medium uppercase text-muted-foreground">{item.label}</p>
              <p className="mt-1 text-xl font-semibold text-foreground">{item.value}</p>
              {item.helper ? <p className="mt-1 text-xs text-muted-foreground">{item.helper}</p> : null}
            </div>
          ))
        : null}
    </div>
  );
}

export function ListSkeleton() {
  return (
    <div className="space-y-4">
      <LoadingSkeleton rows={1} itemClassName="h-16" />
      <LoadingSkeleton rows={1} itemClassName="h-20" />
      <LoadingSkeleton rows={8} itemClassName="h-12" />
    </div>
  );
}

export function ErrorPanel({ message }: { message: string }) {
  const { t } = useTranslation("platform");

  return (
    <div className="flex items-start gap-3 rounded-lg border border-destructive/20 bg-red-50 p-4 text-sm text-red-700">
      <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
      <div>
        <p className="font-semibold">{t("templates.error.title")}</p>
        <p className="mt-1">{message}</p>
      </div>
    </div>
  );
}

export function Pagination({ pagination }: { pagination: PaginationState }) {
  const { t } = useTranslation("platform");
  const first = pagination.total === 0 ? 0 : (pagination.page - 1) * pagination.perPage + 1;
  const last = Math.min(pagination.page * pagination.perPage, pagination.total);
  const maxPage = Math.max(1, Math.ceil(pagination.total / pagination.perPage));

  return (
    <div className="flex flex-col gap-3 rounded-lg border border-border bg-card p-4 text-sm text-muted-foreground md:flex-row md:items-center md:justify-between">
      <p>{t("templates.list.pagination", { first, last, total: pagination.total })}</p>
      <div className="flex items-center gap-2">
        <Button
          type="button"
          variant="outline"
          size="icon"
          disabled={pagination.page <= 1}
          onClick={() => pagination.onPageChange(pagination.page - 1)}
        >
          <ChevronLeft className="h-4 w-4" />
        </Button>
        <span className="font-medium text-foreground">
          {pagination.page} / {maxPage}
        </span>
        <Button
          type="button"
          variant="outline"
          size="icon"
          disabled={pagination.page >= maxPage}
          onClick={() => pagination.onPageChange(pagination.page + 1)}
        >
          <ChevronRight className="h-4 w-4" />
        </Button>
      </div>
    </div>
  );
}

export function TemplateActionGroup({
  actions,
  compact = false
}: {
  actions: TemplateAction[];
  compact?: boolean;
}) {
  if (actions.length === 0) {
    return null;
  }

  return (
    <div className={cn("flex flex-wrap gap-2", compact ? "justify-start" : "justify-end")}>
      {actions.map((action) => {
        if (action.custom) {
          return <div key={action.id}>{action.custom}</div>;
        }

        if (action.href) {
          return (
            <Button key={action.id} asChild variant={buttonVariantFor(action)}>
              <Link href={action.href}>
                {action.icon ? <span className="mr-2">{action.icon}</span> : null}
                {action.label}
              </Link>
            </Button>
          );
        }

        return (
          <Button
            key={action.id}
            type="button"
            variant={buttonVariantFor(action)}
            disabled={action.disabled}
            onClick={action.onClick}
          >
            {action.icon ? <span className="mr-2">{action.icon}</span> : null}
            {action.label}
          </Button>
        );
      })}
    </div>
  );
}

export function buttonVariantFor(action: TemplateAction): ButtonProps["variant"] {
  if (action.variant === "primary") {
    return "default";
  }

  if (action.variant === "danger") {
    return "destructive";
  }

  if (action.variant === "ghost") {
    return "ghost";
  }

  if (action.variant === "secondary") {
    return "secondary";
  }

  return "outline";
}
