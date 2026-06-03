"use client";

import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import { useTranslation } from "react-i18next";
import { ExportButton } from "@/components/platform/ExportButton";
import { PermissionGate } from "@/components/platform/PermissionGate";
import { SettingsNav } from "@/components/settings/SettingsNav";
import { EmptyState } from "@/components/shared/EmptyState";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle
} from "@/components/ui/sheet";
import { ListPageTemplate } from "@/components/templates";
import { auditLogService } from "@/services/audit-log.service";
import type { AuditLogFilters, AuditLogItem } from "@/types/settings-platform";

export function AuditLogPage() {
  const { t } = useTranslation("platform");
  const [search, setSearch] = useState("");
  const [actor, setActor] = useState("");
  const [module, setModule] = useState("");
  const [action, setAction] = useState("");
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");
  const [selectedLog, setSelectedLog] = useState<AuditLogItem | null>(null);
  const filters: AuditLogFilters = { search, actor, module, action, dateFrom, dateTo, perPage: 20 };
  const query = useQuery({
    queryKey: ["settings", "audit", filters],
    queryFn: () => auditLogService.list(filters)
  });
  const logs = query.data?.items ?? [];
  const exportFilters = { search, actor, module, action, date_from: dateFrom, date_to: dateTo };

  return (
    <div className="space-y-6">
      <SettingsNav />
      <ListPageTemplate
        title={t("settingsPlatform.audit.title")}
        description={t("settingsPlatform.audit.description")}
        searchValue={search}
        onSearchChange={setSearch}
        actions={[
          {
            id: "export",
            label: t("platformBehavior.export.label"),
            custom: <ExportButton endpoint="/instance/audit/export" filename="audit-log" filters={exportFilters} formats={["xlsx", "csv"]} />
          }
        ]}
        filters={<AuditFilters actor={actor} module={module} action={action} dateFrom={dateFrom} dateTo={dateTo} setActor={setActor} setModule={setModule} setAction={setAction} setDateFrom={setDateFrom} setDateTo={setDateTo} />}
        activeFilterCount={[actor, module, action, dateFrom, dateTo].filter(Boolean).length}
        onResetFilters={() => { setActor(""); setModule(""); setAction(""); setDateFrom(""); setDateTo(""); }}
        summaryItems={[
          { label: t("settingsPlatform.audit.summary.total"), value: query.data?.meta?.total ?? "-" },
          { label: t("settingsPlatform.audit.summary.today"), value: query.data?.meta?.today ?? "-" },
          { label: t("settingsPlatform.audit.summary.thisWeek"), value: query.data?.meta?.this_week ?? "-" }
        ]}
        columns={[
          { key: "time", header: t("settingsPlatform.audit.columns.time"), cell: (log) => formatDate(log.createdAt) },
          { key: "actor", header: t("settingsPlatform.audit.columns.actor"), cell: (log) => log.actor ?? "-" },
          { key: "action", header: t("settingsPlatform.audit.columns.action"), cell: (log) => log.action },
          { key: "module", header: t("settingsPlatform.audit.columns.module"), cell: (log) => log.module ?? "-" },
          { key: "entity", header: t("settingsPlatform.audit.columns.entity"), cell: (log) => log.entity ?? "-" },
          { key: "detail", header: t("settingsPlatform.audit.columns.detail"), cell: (log) => <span className="line-clamp-1">{log.detail ?? "-"}</span> }
        ]}
        data={logs}
        getRowId={(log) => String(log.id)}
        onRowClick={setSelectedLog}
        mobileCard={(log) => (
          <div className="space-y-1">
            <p className="font-medium text-foreground">{log.action}</p>
            <p className="text-sm text-muted-foreground">{[log.actor, log.module, formatDate(log.createdAt)].filter(Boolean).join(" - ")}</p>
            <p className="line-clamp-2 text-sm text-muted-foreground">{log.detail ?? "-"}</p>
          </div>
        )}
        emptyState={{
          title: t("settingsPlatform.audit.empty.title"),
          description: t("settingsPlatform.audit.empty.description")
        }}
        isLoading={query.isLoading}
        error={query.isError ? new Error(t("settingsPlatform.audit.api.notReady")) : null}
      />
      <AuditLogDrawer log={selectedLog} onOpenChange={(open) => !open && setSelectedLog(null)} />
    </div>
  );
}

function AuditFilters(props: {
  actor: string;
  module: string;
  action: string;
  dateFrom: string;
  dateTo: string;
  setActor: (value: string) => void;
  setModule: (value: string) => void;
  setAction: (value: string) => void;
  setDateFrom: (value: string) => void;
  setDateTo: (value: string) => void;
}) {
  const { t } = useTranslation("platform");

  return (
    <>
      <Input className="w-40" value={props.actor} placeholder={t("settingsPlatform.audit.filters.actor")} onChange={(event) => props.setActor(event.target.value)} />
      <select className="h-9 rounded-md border border-input bg-background px-3 text-sm" value={props.module} onChange={(event) => props.setModule(event.target.value)}>
        <option value="">{t("settingsPlatform.audit.filters.moduleAll")}</option>
        <option value="core">Core</option>
        <option value="karyawan">Karyawan</option>
        <option value="recruitment">Recruitment</option>
        <option value="aset">Aset</option>
        <option value="website">Website</option>
      </select>
      <select className="h-9 rounded-md border border-input bg-background px-3 text-sm" value={props.action} onChange={(event) => props.setAction(event.target.value)}>
        <option value="">{t("settingsPlatform.audit.filters.actionAll")}</option>
        <option value="created">{t("settingsPlatform.audit.actions.created")}</option>
        <option value="updated">{t("settingsPlatform.audit.actions.updated")}</option>
        <option value="archived">{t("settingsPlatform.audit.actions.archived")}</option>
        <option value="exported">{t("settingsPlatform.audit.actions.exported")}</option>
      </select>
      <Input className="w-36" type="date" value={props.dateFrom} onChange={(event) => props.setDateFrom(event.target.value)} />
      <Input className="w-36" type="date" value={props.dateTo} onChange={(event) => props.setDateTo(event.target.value)} />
    </>
  );
}

function AuditLogDrawer({ log, onOpenChange }: { log: AuditLogItem | null; onOpenChange: (open: boolean) => void }) {
  const { t } = useTranslation("platform");

  return (
    <Sheet open={Boolean(log)} onOpenChange={onOpenChange}>
      <SheetContent className="w-full overflow-y-auto sm:max-w-xl" closeLabel={t("settingsPlatform.audit.drawer.close")}>
        <SheetHeader>
          <SheetTitle>{t("settingsPlatform.audit.drawer.title")}</SheetTitle>
          <SheetDescription>{log ? [log.actor, log.action, formatDate(log.createdAt)].filter(Boolean).join(" - ") : ""}</SheetDescription>
        </SheetHeader>
        {log ? (
          <div className="mt-6 space-y-5">
            <InfoGrid log={log} />
            <div>
              <h3 className="text-sm font-semibold text-foreground">{t("settingsPlatform.audit.drawer.diffTitle")}</h3>
              <div className="mt-3 space-y-3">
                {(log.diffs ?? []).length === 0 ? (
                  <EmptyState title={t("settingsPlatform.audit.empty.diffTitle")} description={t("settingsPlatform.audit.empty.diffDescription")} />
                ) : (
                  log.diffs?.map((diff) => (
                    <div key={diff.field} className="rounded-lg border border-border p-3">
                      <p className="text-sm font-medium text-foreground">{diff.field}</p>
                      <div className="mt-2 grid gap-3 text-sm md:grid-cols-2">
                        <DiffValue label={t("settingsPlatform.audit.drawer.oldValue")} value={diff.oldValue} sensitive={diff.isSensitive} />
                        <DiffValue label={t("settingsPlatform.audit.drawer.newValue")} value={diff.newValue} sensitive={diff.isSensitive} />
                      </div>
                    </div>
                  ))
                )}
              </div>
            </div>
          </div>
        ) : null}
      </SheetContent>
    </Sheet>
  );
}

function InfoGrid({ log }: { log: AuditLogItem }) {
  const { t } = useTranslation("platform");
  const items = [
    [t("settingsPlatform.audit.columns.time"), formatDate(log.createdAt)],
    [t("settingsPlatform.audit.columns.actor"), log.actor ?? "-"],
    [t("settingsPlatform.audit.columns.module"), log.module ?? "-"],
    [t("settingsPlatform.audit.columns.entity"), log.entity ?? "-"],
    [t("settingsPlatform.audit.columns.detail"), log.detail ?? "-"]
  ];

  return (
    <dl className="grid gap-3 rounded-lg border border-border p-4 text-sm">
      {items.map(([label, value]) => (
        <div key={label}>
          <dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt>
          <dd className="mt-1 text-foreground">{value}</dd>
        </div>
      ))}
    </dl>
  );
}

function DiffValue({ label, value, sensitive }: { label: string; value?: string | number | boolean | null; sensitive?: boolean }) {
  const content = <span>{String(value ?? "-")}</span>;

  return (
    <div className="rounded-md bg-muted p-3">
      <p className="text-xs font-medium uppercase text-muted-foreground">{label}</p>
      <p className="mt-1 break-words text-foreground">
        {sensitive ? (
          <PermissionGate permission="audit.view_sensitive" fallback="redacted">
            {content}
          </PermissionGate>
        ) : (
          content
        )}
      </p>
    </div>
  );
}

function formatDate(value?: string | null) {
  if (!value) {
    return "-";
  }

  return new Intl.DateTimeFormat("id-ID", { dateStyle: "medium", timeStyle: "short" }).format(new Date(value));
}
