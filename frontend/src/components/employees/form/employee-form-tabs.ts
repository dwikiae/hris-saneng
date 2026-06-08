import type { EmployeeFormErrors, EmployeeFormState, EmployeeFormTab } from "./employee-form-state";

export const employeeFormTabs: EmployeeFormTab[] = ["profil", "kepegawaian", "kontrak", "data-sensitif"];

const fieldsByTab: Record<EmployeeFormTab, Array<keyof EmployeeFormState>> = {
  profil: [
    "name",
    "birthPlace",
    "birthDate",
    "gender",
    "phone",
    "personalEmail",
    "nik",
    "passportNumber",
    "religionId",
    "maritalStatusId",
    "bloodTypeId",
    "nationality",
    "countryOfBirth",
    "provinceId",
    "cityId",
    "address",
    "domicileProvinceId",
    "domicileCityId",
    "domicileAddress",
    "emergencyName",
    "emergencyRelationship",
    "emergencyPhone"
  ],
  kepegawaian: [
    "employeeNumber",
    "departmentId",
    "positionId",
    "employeeLevelId",
    "supervisorId",
    "workLocationId",
    "joinDate",
    "probationEndDate"
  ],
  kontrak: ["contractType", "contractNumber", "contractStartDate", "contractEndDate", "contractNotes"],
  "data-sensitif": ["npwp", "bankName", "bankAccountNumber", "bankAccountOwner", "salary", "allowances", "deductions"]
};

export function tabHasError(tab: EmployeeFormTab, errors: EmployeeFormErrors): boolean {
  return fieldsByTab[tab].some((field) => Boolean(errors[field]));
}

export function firstErrorField(errors: EmployeeFormErrors): string | null {
  const first = Object.keys(errors)[0];
  return first ? fieldId(first) : null;
}

export function fieldId(field: string): string {
  return `employee-form-${field}`;
}
