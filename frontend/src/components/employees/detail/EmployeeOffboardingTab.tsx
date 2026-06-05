"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { CheckCircle2 } from "lucide-react";
import { PermissionGate } from "@/components/platform/PermissionGate";
import { platformToast } from "@/components/platform/ToastProvider";
import { Button } from "@/components/ui/button";
import { employeeService } from "@/services/employee.service";
import type {
  EmployeeOffboarding,
  EmployeeOffboardingPayload,
  OffboardingChecklistItem,
  OffboardingChecklistPayload
} from "@/types/employee";
import { DetailSection, EmptyTab, SelectInput, TabError, TabSkeleton, TextAreaInput, TextInput } from "./DetailBlocks";
import { FormDialog, formString, nullableNumber, nullableString } from "./FormDialog";
import { formatDate } from "./detail-utils";

interface Props {
  employeeId: string;
  enabled: boolean;
  t: (key: string) => string;
}

export function EmployeeOffboardingTab({ employeeId, enabled, t }: Props) {
  const queryClient = useQueryClient();
  const query = useQuery({
    queryKey: ["employees", "detail", employeeId, "offboarding"],
    queryFn: () => employeeService.getOffboarding(employeeId),
    enabled
  });
  const invalidate = async () => {
    await queryClient.invalidateQueries({ queryKey: ["employees", "detail", employeeId, "offboarding"] });
    await queryClient.invalidateQueries({ queryKey: ["employees", "detail", employeeId] });
    await queryClient.invalidateQueries({ queryKey: ["employees", "detail", employeeId, "notes"] });
  };
  const initiate = useMutation({
    mutationFn: (payload: EmployeeOffboardingPayload) => employeeService.createOffboarding(employeeId, payload),
    onSuccess: async () => {
      platformToast.success(t("employeesDetail.toast.saved"));
      await invalidate();
    },
    onError: () => platformToast.error(t("employeesDetail.toast.failed"))
  });
  const complete = useMutation({
    mutationFn: (offboardingId: string | number) => employeeService.completeOffboarding(employeeId, offboardingId),
    onSuccess: async () => {
      platformToast.success(t("employeesDetail.toast.completed"));
      await invalidate();
    },
    onError: () => platformToast.error(t("employeesDetail.toast.failed"))
  });
  const createChecklist = useMutation({
    mutationFn: ({ offboardingId, payload }: { offboardingId: string | number; payload: OffboardingChecklistPayload }) =>
      employeeService.createOffboardingChecklist(employeeId, offboardingId, payload),
    onSuccess: async () => {
      platformToast.success(t("employeesDetail.toast.saved"));
      await invalidate();
    },
    onError: () => platformToast.error(t("employeesDetail.toast.failed"))
  });
  const completeChecklist = useMutation({
    mutationFn: ({ offboardingId, itemId }: { offboardingId: string | number; itemId: string | number }) =>
      employeeService.completeOffboardingChecklist(employeeId, offboardingId, itemId),
    onSuccess: async () => {
      platformToast.success(t("employeesDetail.toast.completed"));
      await invalidate();
    },
    onError: () => platformToast.error(t("employeesDetail.toast.failed"))
  });

  if (query.isLoading) {
    return <TabSkeleton />;
  }
  if (query.isError) {
    return <TabError message={t("employeesDetail.api.offboarding")} onRetry={() => void query.refetch()} />;
  }

  const offboarding = query.data?.offboarding ?? null;

  if (!offboarding) {
    return (
      <DetailSection title={t("employeesDetail.offboarding.title")}>
        <div className="space-y-4">
          <EmptyTab title={t("employeesDetail.offboarding.noActive")} description={t("employeesDetail.offboarding.noActiveDescription")} />
          <PermissionGate permission="employee.archive">
            <FormDialog
              title={t("employeesDetail.offboarding.startTitle")}
              triggerLabel={t("employeesDetail.offboarding.start")}
              onSubmit={(payload) => initiate.mutateAsync(payload)}
              toPayload={offboardingPayload}
            >
              <OffboardingFields t={t} />
            </FormDialog>
          </PermissionGate>
        </div>
      </DetailSection>
    );
  }

  return (
    <DetailSection title={t("employeesDetail.offboarding.title")}>
      <OffboardingDetail
        offboarding={offboarding}
        t={t}
        onComplete={(id) => complete.mutate(id)}
        onChecklistComplete={(offboardingId, itemId) => completeChecklist.mutate({ offboardingId, itemId })}
        onChecklistCreate={(offboardingId, payload) => createChecklist.mutateAsync({ offboardingId, payload })}
      />
    </DetailSection>
  );
}

function OffboardingDetail({
  offboarding,
  t,
  onComplete,
  onChecklistComplete,
  onChecklistCreate
}: {
  offboarding: EmployeeOffboarding;
  t: Props["t"];
  onComplete: (id: string | number) => void;
  onChecklistComplete: (offboardingId: string | number, itemId: string | number) => void;
  onChecklistCreate: (offboardingId: string | number, payload: OffboardingChecklistPayload) => Promise<unknown>;
}) {
  const checklist = offboarding.checklist_items ?? [];
  const completedCount = checklist.filter((item) => item.is_completed).length;
  const progress = checklist.length === 0 ? 0 : Math.round((completedCount / checklist.length) * 100);
  const allDone = checklist.length > 0 && completedCount === checklist.length;

  return (
    <div className="space-y-4">
      <div className="grid gap-4 rounded-lg border border-border bg-background p-4 md:grid-cols-3">
        <Info label={t("employeesDetail.fields.reasonType")} value={t(`employeesDetail.reason.${offboarding.reason_type}`)} />
        <Info label={t("employeesDetail.fields.lastWorkingDate")} value={formatDate(offboarding.last_working_date)} />
        <Info label={t("employeesDetail.fields.status")} value={t(`employeesDetail.offboardingStatus.${offboarding.status}`)} />
        <Info label={t("employeesDetail.fields.reasonDetail")} value={offboarding.reason_detail ?? "-"} />
        <Info label={t("employeesDetail.fields.notes")} value={offboarding.notes ?? "-"} />
      </div>
      <div className="space-y-2">
        <div className="flex items-center justify-between text-sm">
          <span className="font-medium text-foreground">{t("employeesDetail.offboarding.progress")}</span>
          <span className="text-muted-foreground">{progress}%</span>
        </div>
        <div className="h-2 rounded-full bg-slate-100">
          <div className="h-2 rounded-full bg-primary" style={{ width: `${progress}%` }} />
        </div>
      </div>
      <div className="flex justify-end">
        <PermissionGate permission="employee.archive">
          <FormDialog
            title={t("employeesDetail.offboarding.checklistNewTitle")}
            triggerLabel={t("employeesDetail.offboarding.checklistNew")}
            onSubmit={(payload) => onChecklistCreate(offboarding.id, payload)}
            toPayload={checklistPayload}
          >
            <ChecklistFields t={t} />
          </FormDialog>
        </PermissionGate>
      </div>
      <div className="divide-y rounded-lg border border-border">
        {checklist.map((item) => (
          <ChecklistRow
            key={item.id}
            item={item}
            t={t}
            onComplete={() => onChecklistComplete(offboarding.id, item.id)}
          />
        ))}
      </div>
      <PermissionGate permission="employee.archive">
        <Button type="button" disabled={!allDone} onClick={() => onComplete(offboarding.id)}>
          <CheckCircle2 className="mr-2 h-4 w-4" aria-hidden="true" />
          {t("employeesDetail.offboarding.complete")}
        </Button>
      </PermissionGate>
    </div>
  );
}

function ChecklistRow({ item, t, onComplete }: { item: OffboardingChecklistItem; t: Props["t"]; onComplete: () => void }) {
  return (
    <div className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p className="font-medium text-foreground">{item.title}</p>
        <p className="text-sm text-muted-foreground">{item.description ?? "-"}</p>
        <p className="text-xs text-muted-foreground">{formatDate(item.due_date)}</p>
      </div>
      <PermissionGate permission="employee.update">
        <Button type="button" variant={item.is_completed ? "outline" : "default"} disabled={Boolean(item.is_completed)} onClick={onComplete}>
          {item.is_completed ? t("employeesDetail.offboarding.done") : t("employeesDetail.offboarding.markDone")}
        </Button>
      </PermissionGate>
    </div>
  );
}

function Info({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <p className="text-xs font-medium uppercase text-muted-foreground">{label}</p>
      <p className="mt-1 text-sm text-foreground">{value}</p>
    </div>
  );
}

function OffboardingFields({ t }: { t: Props["t"] }) {
  return (
    <>
      <SelectInput label={t("employeesDetail.fields.reasonType")} name="reason_type" required>
        {["resignation", "termination", "contract_end", "retirement", "other"].map((value) => (
          <option key={value} value={value}>{t(`employeesDetail.reason.${value}`)}</option>
        ))}
      </SelectInput>
      <TextInput label={t("employeesDetail.fields.lastWorkingDate")} name="last_working_date" type="date" required />
      <TextAreaInput label={t("employeesDetail.fields.reasonDetail")} name="reason_detail" />
      <TextAreaInput label={t("employeesDetail.fields.notes")} name="notes" />
    </>
  );
}

function ChecklistFields({ t }: { t: Props["t"] }) {
  return (
    <>
      <TextInput label={t("employeesDetail.fields.title")} name="title" required />
      <TextAreaInput label={t("employeesDetail.fields.description")} name="description" />
      <TextInput label={t("employeesDetail.fields.assignedTo")} name="assigned_to" type="number" />
      <TextInput label={t("employeesDetail.fields.dueDate")} name="due_date" type="date" />
      <TextInput label={t("employeesDetail.fields.order")} name="order" type="number" />
    </>
  );
}

function offboardingPayload(formData: FormData): EmployeeOffboardingPayload {
  return {
    reason_type: formString(formData, "reason_type"),
    reason_detail: nullableString(formData, "reason_detail"),
    last_working_date: formString(formData, "last_working_date"),
    notes: nullableString(formData, "notes")
  };
}

function checklistPayload(formData: FormData): OffboardingChecklistPayload {
  return {
    title: formString(formData, "title"),
    description: nullableString(formData, "description"),
    assigned_to: nullableNumber(formData, "assigned_to"),
    due_date: nullableString(formData, "due_date"),
    order: nullableNumber(formData, "order") ?? 0
  };
}
