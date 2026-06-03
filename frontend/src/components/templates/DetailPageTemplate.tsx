"use client";

import type { ReactNode } from "react";
import Link from "next/link";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import { ArrowLeft } from "lucide-react";
import { useTranslation } from "react-i18next";
import { LoadingSkeleton } from "@/components/shared/LoadingSkeleton";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { buttonVariantFor } from "./TemplateParts";
import type { BreadcrumbItem, MetaItem, StatusConfig, TemplateAction } from "./types";

export interface DetailTab {
  slug: string;
  label: string;
  content: ReactNode;
  visible?: boolean | (() => boolean);
}

interface DetailPageTemplateProps {
  backUrl: string;
  backLabel: string;
  breadcrumbs: BreadcrumbItem[];
  actions?: TemplateAction[];
  avatarUrl?: string;
  avatarFallback?: string;
  avatarIcon?: ReactNode;
  title: string;
  subtitle?: string;
  entityId?: string;
  metaInfo?: MetaItem[];
  status?: StatusConfig;
  approvalSlot?: ReactNode;
  tabs: DetailTab[];
  defaultTab?: string;
  notesContent?: ReactNode;
  isLoading?: boolean;
}

export function DetailPageTemplate({
  backUrl,
  backLabel,
  breadcrumbs,
  actions = [],
  avatarUrl,
  avatarFallback,
  avatarIcon,
  title,
  subtitle,
  entityId,
  metaInfo = [],
  status,
  approvalSlot,
  tabs,
  defaultTab,
  notesContent,
  isLoading = false
}: DetailPageTemplateProps) {
  const { t } = useTranslation("platform");
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const visibleTabs = [
    ...tabs.filter((tab) => (typeof tab.visible === "function" ? tab.visible() : tab.visible ?? true)),
    {
      slug: "notes",
      label: t("templates.detail.notesTab"),
      content: notesContent ?? <NotesPlaceholder />
    }
  ];
  const requestedTab = searchParams.get("tab");
  const fallbackTab = defaultTab ?? visibleTabs[0]?.slug;
  const activeTab = visibleTabs.some((tab) => tab.slug === requestedTab) ? requestedTab : fallbackTab;
  const activeContent = visibleTabs.find((tab) => tab.slug === activeTab)?.content;

  const changeTab = (slug: string) => {
    const params = new URLSearchParams(searchParams.toString());
    params.set("tab", slug);
    router.push(`${pathname}?${params.toString()}`);
  };

  if (isLoading) {
    return <DetailSkeleton />;
  }

  return (
    <section className="space-y-5">
      <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div className="space-y-2">
          <Link
            href={backUrl}
            className="inline-flex items-center text-sm font-medium text-muted-foreground transition hover:text-foreground"
          >
            <ArrowLeft className="mr-2 h-4 w-4" />
            {backLabel}
          </Link>
          <nav className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
            {breadcrumbs.map((item, index) => (
              <span key={`${item.label}-${index}`} className="flex items-center gap-2">
                {item.href ? <Link href={item.href}>{item.label}</Link> : <span>{item.label}</span>}
                {index < breadcrumbs.length - 1 ? <span>/</span> : null}
              </span>
            ))}
          </nav>
        </div>
        <ActionGroup actions={actions} />
      </div>

      <div className="rounded-lg border border-border bg-card p-5 shadow-sm">
        <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div className="flex min-w-0 gap-4">
            <Avatar className="h-16 w-16 rounded-lg">
              {avatarUrl ? <AvatarImage src={avatarUrl} alt={title} /> : null}
              <AvatarFallback className="rounded-lg bg-blue-100 text-blue-700">
                {avatarIcon ?? avatarFallback ?? title.slice(0, 2).toUpperCase()}
              </AvatarFallback>
            </Avatar>
            <div className="min-w-0">
              <div className="flex flex-wrap items-center gap-2">
                <h1 className="text-2xl font-semibold text-foreground">{title}</h1>
                {status ? <StatusBadge label={status.label} tone={status.tone} /> : null}
              </div>
              {entityId || subtitle ? (
                <p className="mt-1 text-sm text-muted-foreground">
                  {[entityId, subtitle].filter(Boolean).join(" - ")}
                </p>
              ) : null}
              {metaInfo.length > 0 ? (
                <dl className="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm">
                  {metaInfo.map((item) => (
                    <div key={item.label}>
                      <dt className="text-xs font-medium uppercase text-muted-foreground">{item.label}</dt>
                      <dd className="text-foreground">{item.value}</dd>
                    </div>
                  ))}
                </dl>
              ) : null}
            </div>
          </div>
          {approvalSlot}
        </div>
      </div>

      <div className="overflow-x-auto border-b border-border">
        <div className="flex min-w-max gap-4">
          {visibleTabs.map((tab) => (
            <button
              key={tab.slug}
              type="button"
              className={cn(
                "border-b-2 px-1 pb-3 text-sm font-medium transition",
                tab.slug === activeTab
                  ? "border-primary text-primary"
                  : "border-transparent text-muted-foreground hover:text-foreground"
              )}
              onClick={() => changeTab(tab.slug)}
            >
              {tab.label}
            </button>
          ))}
        </div>
      </div>

      <div className="min-h-48">{activeContent}</div>
    </section>
  );
}

function NotesPlaceholder() {
  const { t } = useTranslation("platform");

  return (
    <div className="rounded-lg border border-dashed border-border bg-card p-6 text-sm text-muted-foreground">
      {t("templates.detail.notesPlaceholder")}
    </div>
  );
}

function DetailSkeleton() {
  return (
    <div className="space-y-5">
      <LoadingSkeleton rows={1} itemClassName="h-20" />
      <LoadingSkeleton rows={1} itemClassName="h-28" />
      <LoadingSkeleton rows={1} itemClassName="h-12" />
      <LoadingSkeleton rows={3} itemClassName="h-16" />
    </div>
  );
}

function ActionGroup({ actions }: { actions: TemplateAction[] }) {
  if (actions.length === 0) {
    return null;
  }

  return (
    <div className="flex flex-wrap gap-2">
      {actions.map((action) =>
        action.href ? (
          <Button key={action.id} asChild variant={variantFor(action)}>
            <Link href={action.href}>
              {action.icon ? <span className="mr-2">{action.icon}</span> : null}
              {action.label}
            </Link>
          </Button>
        ) : (
          <Button
            key={action.id}
            type="button"
            variant={variantFor(action)}
            disabled={action.disabled}
            onClick={action.onClick}
          >
            {action.icon ? <span className="mr-2">{action.icon}</span> : null}
            {action.label}
          </Button>
        )
      )}
    </div>
  );
}

function variantFor(action: TemplateAction) {
  return buttonVariantFor(action);
}
