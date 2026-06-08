import type { MasterDataOption } from "@/types/employee";
import { SelectField, TextField } from "./EmployeeFormControls";
import type { EmployeeFormSectionBuilder } from "./employee-form-section-types";

export function sensitiveSections(banks: MasterDataOption[]): EmployeeFormSectionBuilder {
  return ({ state, errors, t, update }) => [
    {
      id: "finance",
      title: t("employeesForm.sections.finance"),
      fields: [
        { id: "employee-form-npwp", label: t("employeesDetail.fields.npwp"), error: errors.npwp, content: <TextField field="npwp" value={state.npwp} onChange={(value) => update("npwp", value)} /> },
        { id: "employee-form-bankName", label: t("employeesDetail.fields.bankName"), content: <SelectField field="bankName" value={state.bankName} placeholder={t("employeesDetail.values.choose")} options={banks.map((bank) => ({ value: bank.name, label: bank.name }))} onChange={(value) => update("bankName", value)} /> },
        { id: "employee-form-bankAccountNumber", label: t("employeesDetail.fields.bankAccount"), content: <TextField field="bankAccountNumber" value={state.bankAccountNumber} onChange={(value) => update("bankAccountNumber", value)} /> },
        { id: "employee-form-bankAccountOwner", label: t("employeesDetail.fields.bankAccountOwner"), content: <TextField field="bankAccountOwner" value={state.bankAccountOwner} onChange={(value) => update("bankAccountOwner", value)} /> },
        { id: "employee-form-salary", label: t("employeesDetail.fields.salary"), content: <TextField field="salary" type="number" value={state.salary} onChange={(value) => update("salary", value)} /> },
        { id: "employee-form-allowances", label: t("employeesDetail.fields.allowances"), content: <TextField field="allowances" type="number" value={state.allowances} onChange={(value) => update("allowances", value)} /> },
        { id: "employee-form-deductions", label: t("employeesDetail.fields.deductions"), content: <TextField field="deductions" type="number" value={state.deductions} onChange={(value) => update("deductions", value)} /> }
      ]
    }
  ];
}
