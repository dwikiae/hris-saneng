"use client";

import { useEffect, useState } from "react";
import type { ReactNode } from "react";
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
import type {
  EmployeeLevel,
  EmployeeMasterKind,
  EmployeeMasterPayload,
  EmployeeMasterRecord,
  WorkLocation
} from "@/types/employee-settings";

interface EmployeeMasterModalProps {
  open: boolean;
  kind: EmployeeMasterKind;
  entityLabel: string;
  record?: EmployeeMasterRecord | null;
  isSubmitting: boolean;
  onOpenChange: (open: boolean) => void;
  onSubmit: (payload: EmployeeMasterPayload) => void;
}

interface FormState {
  code: string;
  name: string;
  description: string;
  order: string;
  cityId: string;
  address: string;
  isActive: boolean;
}

function stateFromRecord(record?: EmployeeMasterRecord | null): FormState {
  return {
    code: record?.code ?? "",
    name: record?.name ?? "",
    description: isEmployeeLevel(record) ? String(record.description ?? "") : "",
    order: isEmployeeLevel(record) ? String(record.order ?? 0) : "0",
    cityId: isWorkLocation(record) ? String(record.cityId ?? "") : "",
    address: isWorkLocation(record) ? String(record.address ?? "") : "",
    isActive: record?.isActive ?? true
  };
}

function isEmployeeLevel(record?: EmployeeMasterRecord | null): record is EmployeeLevel {
  return Boolean(record && "order" in record);
}

function isWorkLocation(record?: EmployeeMasterRecord | null): record is WorkLocation {
  return Boolean(record && "cityId" in record);
}

export function EmployeeMasterModal({
  open,
  kind,
  entityLabel,
  record,
  isSubmitting,
  onOpenChange,
  onSubmit
}: EmployeeMasterModalProps) {
  const { t } = useTranslation("platform");
  const [state, setState] = useState<FormState>(() => stateFromRecord(record));
  const isEditing = Boolean(record);

  useEffect(() => {
    if (open) {
      setState(stateFromRecord(record));
    }
  }, [open, record]);

  const update = (key: keyof FormState, value: string | boolean) => {
    setState((current) => ({ ...current, [key]: value }));
  };

  const submit = () => {
    if (kind === "employee-levels") {
      onSubmit({
        code: state.code,
        name: state.name,
        description: state.description.trim() || null,
        order: Number(state.order || 0),
        is_active: state.isActive
      });
      return;
    }

    onSubmit({
      code: state.code,
      name: state.name,
      city_id: state.cityId.trim() || null,
      address: state.address.trim() || null,
      is_active: state.isActive
    });
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
            <Input
              id="employee-master-code"
              value={state.code}
              onChange={(event) => update("code", event.target.value)}
            />
          </Field>
          <Field label={t("employeesSettings.fields.name")} htmlFor="employee-master-name" required>
            <Input
              id="employee-master-name"
              value={state.name}
              onChange={(event) => update("name", event.target.value)}
            />
          </Field>
          {kind === "employee-levels" ? (
            <>
              <Field label={t("employeesSettings.fields.order")} htmlFor="employee-master-order">
                <Input
                  id="employee-master-order"
                  type="number"
                  min={0}
                  value={state.order}
                  onChange={(event) => update("order", event.target.value)}
                />
              </Field>
              <Field label={t("employeesSettings.fields.description")} htmlFor="employee-master-description">
                <Input
                  id="employee-master-description"
                  value={state.description}
                  onChange={(event) => update("description", event.target.value)}
                />
              </Field>
            </>
          ) : null}
          {kind === "work-locations" ? (
            <>
              <Field label={t("employeesSettings.fields.city")} htmlFor="employee-master-city">
                <Input
                  id="employee-master-city"
                  value={state.cityId}
                  placeholder={t("employeesSettings.fields.cityPlaceholder")}
                  onChange={(event) => update("cityId", event.target.value)}
                />
              </Field>
              <Field label={t("employeesSettings.fields.address")} htmlFor="employee-master-address">
                <Input
                  id="employee-master-address"
                  value={state.address}
                  onChange={(event) => update("address", event.target.value)}
                />
              </Field>
            </>
          ) : null}
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
          <Button type="button" disabled={isSubmitting || !state.code || !state.name} onClick={submit}>
            {t("employeesSettings.actions.save")}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function Field({
  label,
  htmlFor,
  required = false,
  children
}: {
  label: string;
  htmlFor: string;
  required?: boolean;
  children: ReactNode;
}) {
  return (
    <div className="space-y-2">
      <label className="text-sm font-medium text-foreground" htmlFor={htmlFor}>
        {label}
        {required ? <span className="text-destructive"> *</span> : null}
      </label>
      {children}
    </div>
  );
}
