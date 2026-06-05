"use client";

import type { FormEvent, ReactNode } from "react";
import { useState } from "react";
import { Plus } from "lucide-react";
import { useTranslation } from "react-i18next";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger
} from "@/components/ui/dialog";

interface FormDialogProps<TPayload> {
  title: string;
  triggerLabel: string;
  children: ReactNode;
  submitLabel?: string;
  initialOpen?: boolean;
  onSubmit: (payload: TPayload) => Promise<unknown> | void;
  toPayload: (formData: FormData) => TPayload;
}

export function FormDialog<TPayload>({
  title,
  triggerLabel,
  children,
  submitLabel,
  initialOpen = false,
  onSubmit,
  toPayload
}: FormDialogProps<TPayload>) {
  const { t } = useTranslation("platform");
  const [open, setOpen] = useState(initialOpen);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setIsSubmitting(true);
    try {
      await onSubmit(toPayload(new FormData(event.currentTarget)));
      setOpen(false);
      event.currentTarget.reset();
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button type="button">
          <Plus className="mr-2 h-4 w-4" aria-hidden="true" />
          {triggerLabel}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{title}</DialogTitle>
        </DialogHeader>
        <form className="space-y-4" onSubmit={(event) => void submit(event)}>
          {children}
          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => setOpen(false)}>
              {t("employeesDetail.actions.cancel")}
            </Button>
            <Button type="submit" disabled={isSubmitting}>
              {submitLabel ?? t("employeesDetail.actions.save")}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export function formString(formData: FormData, key: string): string {
  return String(formData.get(key) ?? "").trim();
}

export function nullableString(formData: FormData, key: string): string | null {
  const value = formString(formData, key);
  return value === "" ? null : value;
}

export function nullableNumber(formData: FormData, key: string): number | null {
  const value = nullableString(formData, key);
  return value === null ? null : Number(value);
}
