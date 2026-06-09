"use client";

import { useQuery } from "@tanstack/react-query";
import type { ReactNode } from "react";
import { Input } from "@/components/ui/input";
import { employeeMasterService } from "@/services/employee-master.service";
import type {
  BankMaster,
  ContractTypeMaster,
  DepartmentMaster,
  DocumentTypeMaster,
  EmployeeLevel,
  EmployeeMasterKind,
  EmployeeMasterRecord,
  JobPositionMaster,
  WorkLocation
} from "@/types/employee-settings";

export interface EmployeeMasterFormState {
  code: string;
  name: string;
  description: string;
  order: string;
  cityId: string;
  address: string;
  parentId: string;
  departmentId: string;
  type: "pkwt" | "pkwtt";
  maxDurationMonths: string;
  swift: string;
  isMandatory: boolean;
  isActive: boolean;
}

export function stateFromRecord(record?: EmployeeMasterRecord | null): EmployeeMasterFormState {
  return {
    code: record?.code ?? "",
    name: record?.name ?? "",
    description: isEmployeeLevel(record) ? String(record.description ?? "") : "",
    order: isEmployeeLevel(record) ? String(record.order ?? 0) : "0",
    cityId: isWorkLocation(record) ? String(record.cityId ?? "") : "",
    address: isWorkLocation(record) ? String(record.address ?? "") : "",
    parentId: isDepartment(record) ? String(record.parentId ?? "") : "",
    departmentId: isJobPosition(record) ? String(record.departmentId ?? "") : "",
    type: isContractType(record) && record.type === "pkwtt" ? "pkwtt" : "pkwt",
    maxDurationMonths: isContractType(record) ? String(record.maxDurationMonths ?? "") : "",
    swift: isBank(record) ? String(record.swift ?? "") : "",
    isMandatory: isDocumentType(record) ? record.isMandatory : false,
    isActive: record?.isActive ?? true
  };
}

export function EmployeeMasterSpecificFields({
  company,
  kind,
  record,
  state,
  update,
  t
}: {
  company: string;
  kind: EmployeeMasterKind;
  record?: EmployeeMasterRecord | null;
  state: EmployeeMasterFormState;
  update: (key: keyof EmployeeMasterFormState, value: string | boolean) => void;
  t: (key: string) => string;
}) {
  const departmentsQuery = useQuery({
    queryKey: ["employees", "settings", company, "departments", "modal"],
    queryFn: () => employeeMasterService.list(company, "departments", { isActive: true }),
    enabled: kind === "departments" || kind === "job-positions"
  });

  if (kind === "employee-levels") {
    return (
      <>
        <OrderField state={state} update={update} t={t} />
        <DescriptionField state={state} update={update} t={t} />
      </>
    );
  }

  if (kind === "education-levels") {
    return <OrderField state={state} update={update} t={t} />;
  }

  if (kind === "departments") {
    return (
      <>
        <Field label={t("employeesSettings.fields.parentDepartment")} htmlFor="employee-master-parent">
          <select
            id="employee-master-parent"
            className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
            value={state.parentId}
            onChange={(event) => update("parentId", event.target.value)}
          >
            <option value="">{t("employeesDetail.values.choose")}</option>
            {departmentsQuery.data
              ?.filter((department) => String(department.id) !== String(record?.id ?? ""))
              .map((department) => (
                <option key={department.id} value={department.id}>
                  {department.name}
                </option>
              ))}
          </select>
        </Field>
        <DescriptionField state={state} update={update} t={t} />
      </>
    );
  }

  if (kind === "job-positions") {
    return (
      <>
        <Field label={t("employeesSettings.fields.department")} htmlFor="employee-master-department">
          <select
            id="employee-master-department"
            className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
            value={state.departmentId}
            onChange={(event) => update("departmentId", event.target.value)}
          >
            <option value="">{t("employeesDetail.values.choose")}</option>
            {departmentsQuery.data?.map((department) => (
              <option key={department.id} value={department.id}>
                {department.name}
              </option>
            ))}
          </select>
        </Field>
        <DescriptionField state={state} update={update} t={t} />
      </>
    );
  }

  if (kind === "contract-types") {
    return (
      <>
        <Field label={t("employeesSettings.fields.type")} htmlFor="employee-master-type" required>
          <select
            id="employee-master-type"
            className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
            value={state.type}
            onChange={(event) => update("type", event.target.value === "pkwtt" ? "pkwtt" : "pkwt")}
          >
            <option value="pkwt">PKWT</option>
            <option value="pkwtt">PKWTT</option>
          </select>
        </Field>
        <Field label={t("employeesSettings.fields.maxDurationMonths")} htmlFor="employee-master-max-duration">
          <Input
            id="employee-master-max-duration"
            type="number"
            min={1}
            value={state.maxDurationMonths}
            onChange={(event) => update("maxDurationMonths", event.target.value)}
          />
        </Field>
        <DescriptionField state={state} update={update} t={t} />
      </>
    );
  }

  if (kind === "banks") {
    return (
      <Field label={t("employeesSettings.fields.swift")} htmlFor="employee-master-swift">
        <Input id="employee-master-swift" value={state.swift} onChange={(event) => update("swift", event.target.value)} />
      </Field>
    );
  }

  if (kind === "document-types") {
    return (
      <>
        <DescriptionField state={state} update={update} t={t} />
        <label className="flex items-center gap-2 text-sm font-medium text-foreground">
          <input
            type="checkbox"
            className="h-4 w-4 rounded border-border"
            checked={state.isMandatory}
            onChange={(event) => update("isMandatory", event.target.checked)}
          />
          {t("employeesSettings.fields.isMandatory")}
        </label>
      </>
    );
  }

  if (kind === "work-locations") {
    return (
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
          <Input id="employee-master-address" value={state.address} onChange={(event) => update("address", event.target.value)} />
        </Field>
      </>
    );
  }

  return null;
}

export function Field({
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

function OrderField({
  state,
  update,
  t
}: {
  state: EmployeeMasterFormState;
  update: (key: keyof EmployeeMasterFormState, value: string | boolean) => void;
  t: (key: string) => string;
}) {
  return (
    <Field label={t("employeesSettings.fields.order")} htmlFor="employee-master-order">
      <Input
        id="employee-master-order"
        type="number"
        min={0}
        value={state.order}
        onChange={(event) => update("order", event.target.value)}
      />
    </Field>
  );
}

function DescriptionField({
  state,
  update,
  t
}: {
  state: EmployeeMasterFormState;
  update: (key: keyof EmployeeMasterFormState, value: string | boolean) => void;
  t: (key: string) => string;
}) {
  return (
    <Field label={t("employeesSettings.fields.description")} htmlFor="employee-master-description">
      <Input
        id="employee-master-description"
        value={state.description}
        onChange={(event) => update("description", event.target.value)}
      />
    </Field>
  );
}

function isEmployeeLevel(record?: EmployeeMasterRecord | null): record is EmployeeLevel {
  return Boolean(record && "order" in record);
}

function isWorkLocation(record?: EmployeeMasterRecord | null): record is WorkLocation {
  return Boolean(record && "cityId" in record);
}

function isDepartment(record?: EmployeeMasterRecord | null): record is DepartmentMaster {
  return Boolean(record && "totalEmployees" in record);
}

function isJobPosition(record?: EmployeeMasterRecord | null): record is JobPositionMaster {
  return Boolean(record && "departmentId" in record);
}

function isContractType(record?: EmployeeMasterRecord | null): record is ContractTypeMaster {
  return Boolean(record && "type" in record);
}

function isBank(record?: EmployeeMasterRecord | null): record is BankMaster {
  return Boolean(record && "swift" in record);
}

function isDocumentType(record?: EmployeeMasterRecord | null): record is DocumentTypeMaster {
  return Boolean(record && "isMandatory" in record);
}
