"use client";

import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import type { EmployeeMasterKind, EmployeeMasterPayload, EmployeeMasterRecord } from "@/types/employee-settings";
import {
  EmployeeMasterSpecificFields,
  Field,
  stateFromRecord,
  type EmployeeMasterFormState
} from "./EmployeeMasterModalFields";

interface EmployeeMasterModalProps {
  open: boolean;
  company: string;
  kind: EmployeeMasterKind;
  entityLabel: string;
  record?: EmployeeMasterRecord | null;
  isSubmitting: boolean;
  onOpenChange: (open: boolean) => void;
  onSubmit: (payload: EmployeeMasterPayload) => void;
}

export function EmployeeMasterModal({
  open,
  company,
  kind,
  entityLabel,
  record,
  isSubmitting,
  onOpenChange,
  onSubmit
}: EmployeeMasterModalProps) {
  const { t } = useTranslation("platform");
  const [state, setState] = useState<EmployeeMasterFormState>(() => stateFromRecord(record));
  const isEditing = Boolean(record);

  useEffect(() => {
    if (open) {
      setState(stateFromRecord(record));
    }
  }, [open, record]);

  const update = (key: keyof EmployeeMasterFormState, value: string | boolean) => {
    setState((current) => ({ ...current, [key]: value }));
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>
            {isEditing
              ? t("employeesSettings.modal.editTitle", { entity: entityLabel })
              : t("employeesSettings.modal.newTitle", { entity: entityLabel })}
          </DialogTitle>
          <DialogDescription>{t("employeesSettings.modal.description")}</DialogDescription>
        </DialogHeader>

        <div className="grid gap-4">
          <Field label={t("employeesSettings.fields.code")} htmlFor="employee-master-code" required>
            <Input id="employee-master-code" value={state.code} onChange={(event) => update("code", event.target.value)} />
          </Field>
          <Field label={t("employeesSettings.fields.name")} htmlFor="employee-master-name" required>
            <Input id="employee-master-name" value={state.name} onChange={(event) => update("name", event.target.value)} />
          </Field>
          <EmployeeMasterSpecificFields company={company} kind={kind} record={record} state={state} update={update} t={t} />
          <label className="flex items-center gap-2 text-sm font-medium text-foreground">
            <input
              type="checkbox"
              className="h-4 w-4 rounded border-border"
              checked={state.isActive}
              onChange={(event) => update("isActive", event.target.checked)}
            />
            {t("employeesSettings.fields.isActive")}
          </label>
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
            {t("employeesSettings.actions.cancel")}
          </Button>
          <Button type="button" disabled={isSubmitting || !state.code || !state.name} onClick={() => onSubmit(payloadFor(kind, state))}>
            {t("employeesSettings.actions.save")}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function payloadFor(kind: EmployeeMasterKind, state: EmployeeMasterFormState): EmployeeMasterPayload {
  if (kind === "employee-levels" || kind === "education-levels") {
    return {
      code: state.code,
      name: state.name,
      ...(kind === "employee-levels" ? { description: nullable(state.description) } : {}),
      order: Number(state.order || 0),
      is_active: state.isActive
    };
  }

  if (kind === "departments") {
    return {
      code: state.code,
      name: state.name,
      description: nullable(state.description),
      parent_id: state.parentId || null,
      is_active: state.isActive
    };
  }

  if (kind === "job-positions") {
    return {
      code: state.code,
      name: state.name,
      department_id: state.departmentId || null,
      description: nullable(state.description),
      is_active: state.isActive
    };
  }

  if (kind === "contract-types") {
    return {
      code: state.code,
      name: state.name,
      type: state.type,
      description: nullable(state.description),
      max_duration_months: state.maxDurationMonths ? Number(state.maxDurationMonths) : null,
      is_active: state.isActive
    };
  }

  if (kind === "banks") {
    return { code: state.code, name: state.name, swift: nullable(state.swift), is_active: state.isActive };
  }

  if (kind === "document-types") {
    return {
      code: state.code,
      name: state.name,
      is_mandatory: state.isMandatory,
      description: nullable(state.description),
      is_active: state.isActive
    };
  }

  if (kind === "religions") {
    return { code: state.code, name: state.name, is_active: state.isActive };
  }

  return {
    code: state.code,
    name: state.name,
    city_id: nullable(state.cityId),
    address: nullable(state.address),
    is_active: state.isActive
  };
}

function nullable(value: string): string | null {
  return value.trim() === "" ? null : value.trim();
}
