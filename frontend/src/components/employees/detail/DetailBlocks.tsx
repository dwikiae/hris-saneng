"use client";

import type { ReactNode } from "react";
import { AlertCircle, RefreshCw } from "lucide-react";
import { useTranslation } from "react-i18next";
import { PermissionGate } from "@/components/platform/PermissionGate";
import { EmptyState } from "@/components/shared/EmptyState";
import { LoadingSkeleton } from "@/components/shared/LoadingSkeleton";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { display } from "./detail-utils";

interface DetailSectionProps {
  title: string;
  children: ReactNode;
  className?: string;
}

export function DetailSection({ title, children, className }: DetailSectionProps) {
  return (
    <section className={cn("rounded-lg border border-border bg-card p-4 shadow-sm", className)}>
      <h2 className="text-base font-semibold text-foreground">{title}</h2>
      <div className="mt-4">{children}</div>
    </section>
  );
}

export function FieldGrid({ children }: { children: ReactNode }) {
  return <dl className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">{children}</dl>;
}

export function FieldItem({ label, value }: { label: string; value: ReactNode }) {
  return (
    <div className="min-w-0">
      <dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt>
      <dd className="mt-1 break-words text-sm text-foreground">{display(value)}</dd>
    </div>
  );
}

export function SensitiveField({ label, value }: { label: string; value: ReactNode }) {
  return (
    <FieldItem
      label={label}
      value={
        <PermissionGate permission="employee.view_sensitive" fallback="redacted">
          <span>{display(value)}</span>
        </PermissionGate>
      }
    />
  );
}

export function TabSkeleton() {
  return <LoadingSkeleton rows={4} itemClassName="h-24" />;
}

export function TabError({ message, onRetry }: { message: string; onRetry: () => void }) {
  const { t } = useTranslation("platform");

  return (
    <div className="rounded-lg border border-red-200 bg-red-50 p-4">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex gap-3 text-red-800">
          <AlertCircle className="mt-0.5 h-4 w-4" aria-hidden="true" />
          <p className="text-sm">{message}</p>
        </div>
        <Button type="button" variant="outline" onClick={onRetry}>
          <RefreshCw className="mr-2 h-4 w-4" aria-hidden="true" />
          {t("employeesDetail.actions.retry")}
        </Button>
      </div>
    </div>
  );
}

export function EmptyTab({ title, description }: { title: string; description?: string }) {
  return <EmptyState title={title} description={description} />;
}

export function TextInput(props: {
  label: string;
  name: string;
  type?: string;
  defaultValue?: string | number | null;
  required?: boolean;
}) {
  return (
    <label className="space-y-1 text-sm font-medium text-foreground">
      <span>{props.label}</span>
      <input
        name={props.name}
        type={props.type ?? "text"}
        defaultValue={props.defaultValue ?? ""}
        required={props.required}
        className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus:ring-2 focus:ring-ring"
      />
    </label>
  );
}

export function TextAreaInput(props: { label: string; name: string; defaultValue?: string | null }) {
  return (
    <label className="space-y-1 text-sm font-medium text-foreground">
      <span>{props.label}</span>
      <textarea
        name={props.name}
        defaultValue={props.defaultValue ?? ""}
        className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
      />
    </label>
  );
}

export function SelectInput(props: {
  label: string;
  name: string;
  defaultValue?: string | number | null;
  required?: boolean;
  children: ReactNode;
}) {
  return (
    <label className="space-y-1 text-sm font-medium text-foreground">
      <span>{props.label}</span>
      <select
        name={props.name}
        defaultValue={props.defaultValue ?? ""}
        required={props.required}
        className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus:ring-2 focus:ring-ring"
      >
        {props.children}
      </select>
    </label>
  );
}

export function CheckInput(props: { label: string; name: string; defaultChecked?: boolean }) {
  return (
    <label className="flex items-center gap-2 text-sm font-medium text-foreground">
      <input
        name={props.name}
        type="checkbox"
        defaultChecked={props.defaultChecked}
        className="h-4 w-4 rounded border-border"
      />
      <span>{props.label}</span>
    </label>
  );
}
