import { SelectField, TextAreaField, TextField } from "./EmployeeFormControls";
import type { EmployeeFormSectionBuilder } from "./employee-form-section-types";

export const contractSections: EmployeeFormSectionBuilder = ({ state, errors, t, update }) => [
  {
    id: "contract",
    title: t("employeesForm.sections.contract"),
    fields: [
      {
        id: "employee-form-contractType",
        label: t("employeesDetail.fields.contractType"),
        required: true,
        error: errors.contractType,
        content: (
          <SelectField
            field="contractType"
            value={state.contractType}
            placeholder={t("employeesDetail.values.choose")}
            options={[
              { value: "pkwt", label: t("employeesList.contract.pkwt") },
              { value: "pkwtt", label: t("employeesList.contract.pkwtt") }
            ]}
            onChange={(value) => update("contractType", value === "pkwtt" ? "pkwtt" : "pkwt")}
          />
        )
      },
      { id: "employee-form-contractNumber", label: t("employeesDetail.fields.contractNumber"), content: <TextField field="contractNumber" value={state.contractNumber} onChange={(value) => update("contractNumber", value)} /> },
      { id: "employee-form-contractStartDate", label: t("employeesDetail.fields.startDate"), required: true, error: errors.contractStartDate, content: <TextField field="contractStartDate" type="date" value={state.contractStartDate} onChange={(value) => update("contractStartDate", value)} /> },
      {
        id: "employee-form-contractEndDate",
        label: t("employeesDetail.fields.endDate"),
        required: state.contractType === "pkwt",
        error: errors.contractEndDate,
        content: <TextField field="contractEndDate" type="date" value={state.contractEndDate} disabled={state.contractType === "pkwtt"} onChange={(value) => update("contractEndDate", value)} />
      },
      { id: "employee-form-contractNotes", label: t("employeesDetail.fields.notes"), span: 2, content: <TextAreaField field="contractNotes" value={state.contractNotes} onChange={(value) => update("contractNotes", value)} /> }
    ]
  }
];
