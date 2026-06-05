"use client";

import { useState } from "react";
import { CheckCircle2, XCircle } from "lucide-react";
import { PermissionGate } from "@/components/platform/PermissionGate";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle
} from "@/components/ui/dialog";

interface EmployeeApprovalPanelProps {
  entityName: string;
  onApprove: () => void;
  onReject: (reason: string) => void;
  t: (key: string, options?: Record<string, string>) => string;
}

export function EmployeeApprovalPanel({ entityName, onApprove, onReject, t }: EmployeeApprovalPanelProps) {
  const [rejectOpen, setRejectOpen] = useState(false);
  const [reason, setReason] = useState("");

  return (
    <PermissionGate permission="employee.approve">
      <div className="rounded-lg border border-amber-200 bg-amber-50 p-4">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h3 className="text-sm font-semibold text-amber-950">{t("employeesDetail.approval.title")}</h3>
            <p className="mt-1 text-sm text-amber-900">
              {t("employeesDetail.approval.description", { name: entityName })}
            </p>
          </div>
          <div className="flex gap-2">
            <Button type="button" variant="outline" onClick={() => setRejectOpen(true)}>
              <XCircle className="mr-2 h-4 w-4" aria-hidden="true" />
              {t("employeesDetail.actions.reject")}
            </Button>
            <Button type="button" onClick={onApprove}>
              <CheckCircle2 className="mr-2 h-4 w-4" aria-hidden="true" />
              {t("employeesDetail.actions.approve")}
            </Button>
          </div>
        </div>
      </div>
      <Dialog open={rejectOpen} onOpenChange={setRejectOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{t("employeesDetail.approval.rejectTitle")}</DialogTitle>
          </DialogHeader>
          <textarea
            value={reason}
            className="min-h-28 rounded-md border border-input px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
            onChange={(event) => setReason(event.target.value)}
          />
          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => setRejectOpen(false)}>
              {t("employeesDetail.actions.cancel")}
            </Button>
            <Button
              type="button"
              variant="destructive"
              onClick={() => {
                onReject(reason);
                setRejectOpen(false);
                setReason("");
              }}
            >
              {t("employeesDetail.actions.reject")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </PermissionGate>
  );
}
