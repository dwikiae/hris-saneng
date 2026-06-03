"use client";

import { CheckCircle2, XCircle } from "lucide-react";
import { useState } from "react";
import { useTranslation } from "react-i18next";
import { ConfirmDialog } from "@/components/platform/ConfirmDialog";
import { PermissionGate } from "@/components/platform/PermissionGate";
import { platformToast } from "@/components/platform/ToastProvider";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { approvalService } from "@/services/platform.service";

interface ApprovalPanelProps {
  module: string;
  status: "pending" | "approved" | "rejected" | string;
  entityName: string;
  approveEndpoint: string;
  rejectEndpoint: string;
  onApproved?: () => void;
  onRejected?: (note: string) => void;
}

export function ApprovalPanel({
  module,
  status,
  entityName,
  approveEndpoint,
  rejectEndpoint,
  onApproved,
  onRejected
}: ApprovalPanelProps) {
  const { t } = useTranslation("platform");
  const [rejectOpen, setRejectOpen] = useState(false);
  const [note, setNote] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);

  if (status !== "pending") {
    return null;
  }

  const approve = async () => {
    setIsSubmitting(true);
    try {
      await approvalService.approve(approveEndpoint);
      onApproved?.();
      platformToast.success(t("platformBehavior.approval.approved"));
    } catch {
      platformToast.error(t("platformBehavior.approval.failed"));
    } finally {
      setIsSubmitting(false);
    }
  };

  const reject = async () => {
    if (!note.trim()) {
      platformToast.warning(t("platformBehavior.approval.noteRequired"));
      return;
    }

    setIsSubmitting(true);
    try {
      await approvalService.reject(rejectEndpoint, note);
      onRejected?.(note);
      setRejectOpen(false);
      setNote("");
      platformToast.success(t("platformBehavior.approval.rejected"));
    } catch {
      platformToast.error(t("platformBehavior.approval.failed"));
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <PermissionGate permission={`${module}.approve`}>
      <div className="rounded-lg border border-amber-200 bg-amber-50 p-4">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h3 className="text-sm font-semibold text-amber-950">
              {t("platformBehavior.approval.title")}
            </h3>
            <p className="mt-1 text-sm text-amber-900">
              {t("platformBehavior.approval.description", { entity: entityName })}
            </p>
          </div>
          <div className="flex gap-2">
            <Button type="button" variant="outline" onClick={() => setRejectOpen(true)}>
              <XCircle className="mr-2 h-4 w-4" aria-hidden="true" />
              {t("platformBehavior.approval.reject")}
            </Button>
            <ConfirmDialog
              title={t("platformBehavior.approval.confirmTitle")}
              description={t("platformBehavior.approval.confirmDescription", { entity: entityName })}
              confirmLabel={t("platformBehavior.approval.approve")}
              confirmVariant="warning"
              onConfirm={() => void approve()}
            >
              <Button type="button" disabled={isSubmitting}>
                <CheckCircle2 className="mr-2 h-4 w-4" aria-hidden="true" />
                {t("platformBehavior.approval.approve")}
              </Button>
            </ConfirmDialog>
          </div>
        </div>
      </div>

      <Dialog open={rejectOpen} onOpenChange={setRejectOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{t("platformBehavior.approval.rejectTitle")}</DialogTitle>
          </DialogHeader>
          <textarea
            className="min-h-28 rounded-md border border-input px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
            value={note}
            placeholder={t("platformBehavior.approval.notePlaceholder")}
            onChange={(event) => setNote(event.target.value)}
          />
          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => setRejectOpen(false)}>
              {t("platformBehavior.confirm.cancel")}
            </Button>
            <Button type="button" variant="destructive" disabled={isSubmitting} onClick={() => void reject()}>
              {t("platformBehavior.approval.reject")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </PermissionGate>
  );
}
