"use client";

import type { ReactNode } from "react";
import Link from "next/link";
import { useEffect, useState } from "react";
import { AlertCircle, ChevronDown, ChevronRight, Loader2 } from "lucide-react";
import { useTranslation } from "react-i18next";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import type { TemplateAction } from "./types";

export interface FormFieldSlot {
  id: string;
  label: string;
  content: ReactNode;
  required?: boolean;
  error?: string;
  span?: 1 | 2;
}

export interface FormTemplateSection {
  id: string;
  title: string;
  description?: string;
  fields: FormFieldSlot[];
  initiallyOpen?: boolean;
}

interface FormPageTemplateProps {
  title: string;
  description?: string;
  backUrl: string;
  backLabel: string;
  sections: FormTemplateSection[];
  footerActions: TemplateAction[];
  isDirty: boolean;
  isSubmitting: boolean;
}

export function FormPageTemplate({
  title,
  description,
  backUrl,
  backLabel,
  sections,
  footerActions,
  isDirty,
  isSubmitting
}: FormPageTemplateProps) {
  return (
    <section className="pb-24">
      <div className="mb-6 flex flex-col gap-2">
        <Link
          href={backUrl}
          className="text-sm font-medium text-muted-foreground transition hover:text-foreground"
        >
          {backLabel}
        </Link>
        <div>
          <h1 className="text-2xl font-semibold text-foreground">{title}</h1>
          {description ? (
            <p className="mt-1 max-w-3xl text-sm leading-6 text-muted-foreground">{description}</p>
          ) : null}
        </div>
      </div>

      <div className="space-y-4">
        {sections.map((section) => (
          <FormSection key={section.id} section={section} />
        ))}
      </div>

      <StickyFooter actions={footerActions} isDirty={isDirty} isSubmitting={isSubmitting} />
    </section>
  );
}

function FormSection({ section }: { section: FormTemplateSection }) {
  const hasRequiredField = section.fields.some((field) => field.required);
  const hasError = section.fields.some((field) => Boolean(field.error));
  const [isOpen, setIsOpen] = useState(section.initiallyOpen ?? hasRequiredField);

  useEffect(() => {
    if (hasError) {
      setIsOpen(true);
    }
  }, [hasError]);

  return (
    <div className="rounded-lg border border-border bg-card shadow-sm">
      <button
        type="button"
        className={cn(
          "flex w-full items-start justify-between gap-3 px-5 py-4 text-left",
          hasError && "text-destructive"
        )}
        onClick={() => setIsOpen((current) => !current)}
      >
        <span>
          <span className="block text-base font-semibold">{section.title}</span>
          {section.description ? (
            <span className="mt-1 block text-sm text-muted-foreground">{section.description}</span>
          ) : null}
        </span>
        {isOpen ? <ChevronDown className="h-5 w-5" /> : <ChevronRight className="h-5 w-5" />}
      </button>
      {isOpen ? (
        <div className="grid gap-4 border-t border-border p-5 md:grid-cols-2">
          {section.fields.map((field) => (
            <div key={field.id} className={cn(field.span === 2 && "md:col-span-2")}>
              <label className="text-sm font-medium text-foreground" htmlFor={field.id}>
                {field.label}
                {field.required ? <span className="ml-1 text-destructive">*</span> : null}
              </label>
              <div className="mt-2">{field.content}</div>
              {field.error ? (
                <p className="mt-2 flex items-center gap-1 text-xs text-destructive">
                  <AlertCircle className="h-3 w-3" />
                  {field.error}
                </p>
              ) : null}
            </div>
          ))}
        </div>
      ) : null}
    </div>
  );
}

function StickyFooter({
  actions,
  isDirty,
  isSubmitting
}: {
  actions: TemplateAction[];
  isDirty: boolean;
  isSubmitting: boolean;
}) {
  const { t } = useTranslation("platform");

  return (
    <div className="fixed inset-x-0 bottom-0 z-30 border-t border-border bg-background/95 px-4 py-3 shadow-lg backdrop-blur md:left-60">
      <div className="mx-auto flex max-w-7xl flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <p className="text-xs text-muted-foreground">
          {isDirty ? t("templates.form.unsavedChanges") : t("templates.form.noChanges")}
        </p>
        <div className="flex flex-wrap justify-end gap-2">
          {actions.map((action) => (
            <Button
              key={action.id}
              type="button"
              variant={action.variant === "primary" ? "default" : action.variant === "danger" ? "destructive" : "outline"}
              disabled={action.disabled || isSubmitting || (action.variant === "primary" && !isDirty)}
              onClick={action.onClick}
            >
              {isSubmitting && action.variant === "primary" ? (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              ) : action.icon ? (
                <span className="mr-2">{action.icon}</span>
              ) : null}
              {action.label}
            </Button>
          ))}
        </div>
      </div>
    </div>
  );
}
