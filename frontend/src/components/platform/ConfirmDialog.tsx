"use client";

import type { ReactNode } from "react";
import { AlertTriangle } from "lucide-react";
import { useTranslation } from "react-i18next";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { cn } from "@/lib/utils";

type ConfirmVariant = "danger" | "warning";

interface ConfirmDialogProps {
  title: string;
  description: string;
  confirmLabel: string;
  confirmVariant: ConfirmVariant;
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
  onConfirm: () => void;
  confirmationLabel?: string;
  confirmationExpected?: string;
  confirmationValue?: string;
  onConfirmationChange?: (value: string) => void;
  children?: ReactNode;
}

export function ConfirmDialog({
  title,
  description,
  confirmLabel,
  confirmVariant,
  open,
  onOpenChange,
  onConfirm,
  confirmationLabel,
  confirmationExpected,
  confirmationValue = "",
  onConfirmationChange,
  children
}: ConfirmDialogProps) {
  const { t } = useTranslation("platform");
  const confirmationMatches =
    !confirmationExpected || confirmationValue.trim() === confirmationExpected.trim();

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      {children ? <DialogTrigger asChild>{children}</DialogTrigger> : null}
      <DialogContent>
        <DialogHeader>
          <div
            className={cn(
              "mb-2 flex h-10 w-10 items-center justify-center rounded-full",
              confirmVariant === "danger" ? "bg-red-100 text-destructive" : "bg-amber-100 text-warning"
            )}
          >
            <AlertTriangle className="h-5 w-5" />
          </div>
          <DialogTitle>{title}</DialogTitle>
          <DialogDescription>{description}</DialogDescription>
        </DialogHeader>
        {confirmationExpected ? (
          <div className="space-y-2">
            <label className="text-sm font-medium text-foreground" htmlFor="confirm-text">
              {confirmationLabel}
            </label>
            <Input
              id="confirm-text"
              value={confirmationValue}
              onChange={(event) => onConfirmationChange?.(event.target.value)}
            />
          </div>
        ) : null}
        <DialogFooter>
          <Button type="button" variant="outline" onClick={() => onOpenChange?.(false)}>
            {t("platformBehavior.confirm.cancel")}
          </Button>
          <Button
            type="button"
            variant={confirmVariant === "danger" ? "destructive" : "default"}
            className={confirmVariant === "warning" ? "bg-warning text-warning-foreground hover:bg-warning/90" : undefined}
            disabled={!confirmationMatches}
            onClick={() => {
              onConfirm();
              onOpenChange?.(false);
            }}
          >
            {confirmLabel}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
