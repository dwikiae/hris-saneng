import type { EmployeeListItem, MasterDataOption } from "@/types/employee";
import type { EmployeeMasterRecord } from "@/types/employee-settings";
import { optionsFromMaster, SelectField, TextField } from "./EmployeeFormControls";
import type { EmployeeFormSectionBuilder } from "./employee-form-section-types";

interface EmploymentLookups {
  departments: MasterDataOption[];
  positions: MasterDataOption[];
  levels: EmployeeMasterRecord[];
  supervisors: EmployeeListItem[];
  workLocations: EmployeeMasterRecord[];
}

export function employmentSections(lookups: EmploymentLookups): EmployeeFormSectionBuilder {
  return ({ state, errors, t, update }) => [
    {
      id: "employment",
      title: t("employeesForm.sections.employment"),
      fields: [
        { id: "employee-form-employeeNumber", label: t("employeesDetail.fields.employeeNumber"), error: errors.employeeNumber, content: <TextField field="employeeNumber" value={state.employeeNumber} onChange={(value) => update("employeeNumber", value)} /> },
        {
          id: "employee-form-departmentId",
          label: t("employeesDetail.fields.department"),
          required: true,
          error: errors.departmentId,
          content: <SelectField field="departmentId" value={state.departmentId} placeholder={t("employeesDetail.values.choose")} options={optionsFromMaster(lookups.departments)} onChange={(value) => update("departmentId", value)} />
        },
        {
          id: "employee-form-positionId",
          label: t("employeesDetail.fields.position"),
          required: true,
          error: errors.positionId,
          content: <SelectField field="positionId" value={state.positionId} placeholder={t("employeesDetail.values.choose")} options={optionsFromMaster(lookups.positions)} onChange={(value) => update("positionId", value)} />
        },
        {
          id: "employee-form-employeeLevelId",
          label: t("employeesDetail.fields.levelGrade"),
          content: <SelectField field="employeeLevelId" value={state.employeeLevelId} placeholder={t("employeesDetail.values.choose")} options={masterRecords(lookups.levels)} onChange={(value) => update("employeeLevelId", value)} />
        },
        {
          id: "employee-form-supervisorId",
          label: t("employeesDetail.fields.supervisor"),
          content: <SelectField field="supervisorId" value={state.supervisorId} placeholder={t("employeesDetail.values.choose")} options={supervisorOptions(lookups.supervisors)} onChange={(value) => update("supervisorId", value)} />
        },
        {
          id: "employee-form-workLocationId",
          label: t("employeesDetail.fields.workLocation"),
          content: <SelectField field="workLocationId" value={state.workLocationId} placeholder={t("employeesDetail.values.choose")} options={masterRecords(lookups.workLocations)} onChange={(value) => update("workLocationId", value)} />
        },
        { id: "employee-form-joinDate", label: t("employeesDetail.fields.joinDate"), required: true, error: errors.joinDate, content: <TextField field="joinDate" type="date" value={state.joinDate} onChange={(value) => update("joinDate", value)} /> },
        { id: "employee-form-probationEndDate", label: t("employeesDetail.fields.probationEndDate"), error: errors.probationEndDate, content: <TextField field="probationEndDate" type="date" value={state.probationEndDate} onChange={(value) => update("probationEndDate", value)} /> }
      ]
    }
  ];
}

function masterRecords(items: EmployeeMasterRecord[]) {
  return items.map((item) => ({ value: String(item.id), label: item.name }));
}

function supervisorOptions(items: EmployeeListItem[]) {
  return items.map((item) => ({
    value: String(item.id),
    label: [item.name, item.employee_number].filter(Boolean).join(" - ")
  }));
}
