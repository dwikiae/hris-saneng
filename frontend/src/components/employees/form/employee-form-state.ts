import type {
  EmployeeContract,
  EmployeeDetail,
  EmployeePayload,
  EmployeeUpdatePayload,
  MasterDataOption
} from "@/types/employee";
import type { EmployeeModuleSettings } from "@/types/employee-settings";

export type EmployeeFormMode = "new" | "edit";
export type EmployeeFormTab = "profil" | "kepegawaian" | "kontrak" | "data-sensitif";

export interface EmployeeFormState {
  employeeNumber: string;
  photoFile: File | null;
  name: string;
  nickname: string;
  birthPlace: string;
  birthDate: string;
  gender: string;
  phone: string;
  personalEmail: string;
  nik: string;
  passportNumber: string;
  religionId: string;
  maritalStatusId: string;
  bloodTypeId: string;
  nationality: string;
  countryOfBirth: string;
  provinceId: string;
  cityId: string;
  address: string;
  domicileSameAsKtp: boolean;
  domicileProvinceId: string;
  domicileCityId: string;
  domicileAddress: string;
  emergencyContactId: string;
  emergencyName: string;
  emergencyRelationship: string;
  emergencyPhone: string;
  departmentId: string;
  positionId: string;
  employeeLevelId: string;
  supervisorId: string;
  workLocationId: string;
  joinDate: string;
  probationEndDate: string;
  employmentTypeId: string;
  contractId: string;
  contractType: "pkwt" | "pkwtt";
  contractNumber: string;
  contractStartDate: string;
  contractEndDate: string;
  contractNotes: string;
  npwp: string;
  bankName: string;
  bankAccountNumber: string;
  bankAccountOwner: string;
  salary: string;
  allowances: string;
  deductions: string;
}

export type EmployeeFormErrors = Partial<Record<keyof EmployeeFormState, string>>;

export const initialEmployeeFormState: EmployeeFormState = {
  employeeNumber: "",
  photoFile: null,
  name: "",
  nickname: "",
  birthPlace: "",
  birthDate: "",
  gender: "",
  phone: "",
  personalEmail: "",
  nik: "",
  passportNumber: "",
  religionId: "",
  maritalStatusId: "",
  bloodTypeId: "",
  nationality: "WNI",
  countryOfBirth: "ID",
  provinceId: "",
  cityId: "",
  address: "",
  domicileSameAsKtp: true,
  domicileProvinceId: "",
  domicileCityId: "",
  domicileAddress: "",
  emergencyContactId: "",
  emergencyName: "",
  emergencyRelationship: "",
  emergencyPhone: "",
  departmentId: "",
  positionId: "",
  employeeLevelId: "",
  supervisorId: "",
  workLocationId: "",
  joinDate: "",
  probationEndDate: "",
  employmentTypeId: "",
  contractId: "",
  contractType: "pkwt",
  contractNumber: "",
  contractStartDate: "",
  contractEndDate: "",
  contractNotes: "",
  npwp: "",
  bankName: "",
  bankAccountNumber: "",
  bankAccountOwner: "",
  salary: "",
  allowances: "",
  deductions: ""
};

export function stateFromEmployee(
  employee?: EmployeeDetail,
  contract?: EmployeeContract | null,
  emergencyContact?: { id?: string | number; name?: string | null; relationship?: string | null; phone?: string | null } | null
): EmployeeFormState {
  if (!employee) {
    return initialEmployeeFormState;
  }

  return {
    ...initialEmployeeFormState,
    employeeNumber: value(employee.employee_number),
    name: employee.name ?? "",
    nickname: value(employee.nickname),
    birthPlace: value(employee.birth_place),
    birthDate: value(employee.birth_date),
    gender: value(employee.gender),
    phone: value(employee.phone),
    personalEmail: value(employee.email),
    nik: value(employee.nik),
    passportNumber: value(employee.passport_number),
    religionId: value(employee.religion_id),
    maritalStatusId: value(employee.marital_status_id),
    bloodTypeId: value(employee.blood_type_id),
    nationality: value(employee.nationality) || "WNI",
    countryOfBirth: value(employee.country_of_birth) || "ID",
    provinceId: value(employee.province_id),
    cityId: value(employee.city_id),
    address: value(employee.address),
    domicileSameAsKtp: !employee.domicile_address && !employee.domicile_province_id && !employee.domicile_city_id,
    domicileProvinceId: value(employee.domicile_province_id),
    domicileCityId: value(employee.domicile_city_id),
    domicileAddress: value(employee.domicile_address),
    emergencyContactId: value(emergencyContact?.id),
    emergencyName: value(emergencyContact?.name),
    emergencyRelationship: value(emergencyContact?.relationship),
    emergencyPhone: value(emergencyContact?.phone),
    departmentId: value(employee.department_id),
    positionId: value(employee.position_id),
    employeeLevelId: value(employee.employee_level_id),
    supervisorId: value(employee.supervisor_id),
    workLocationId: value(employee.work_location_id),
    joinDate: value(employee.join_date),
    probationEndDate: value(employee.probation_end_date),
    employmentTypeId: value(employee.employment_type_id),
    contractId: value(contract?.id),
    contractType: contract?.contract_type === "pkwtt" ? "pkwtt" : "pkwt",
    contractNumber: value(contract?.contract_number),
    contractStartDate: value(contract?.start_date),
    contractEndDate: value(contract?.end_date),
    contractNotes: value(contract?.notes),
    npwp: value(employee.npwp),
    bankName: value(employee.bank_name),
    bankAccountNumber: value(employee.bank_account_number),
    bankAccountOwner: value(employee.bank_account_holder_name),
    salary: value(employee.salary),
    allowances: value(employee.allowances),
    deductions: value(employee.deductions)
  };
}

export function applyProbationDefault(state: EmployeeFormState, settings?: EmployeeModuleSettings): EmployeeFormState {
  if (!state.joinDate || state.probationEndDate || !settings?.probation_days) {
    return state;
  }

  const date = new Date(`${state.joinDate}T00:00:00`);
  date.setDate(date.getDate() + settings.probation_days);

  return { ...state, probationEndDate: date.toISOString().slice(0, 10) };
}

export function toEmployeePayload(
  state: EmployeeFormState,
  employmentTypes: MasterDataOption[],
  mode: "new"
): EmployeePayload;
export function toEmployeePayload(
  state: EmployeeFormState,
  employmentTypes: MasterDataOption[],
  mode: "edit"
): EmployeeUpdatePayload;
export function toEmployeePayload(
  state: EmployeeFormState,
  employmentTypes: MasterDataOption[],
  mode: EmployeeFormMode
): EmployeePayload | EmployeeUpdatePayload {
  const employmentTypeId = state.employmentTypeId || resolveEmploymentTypeId(state.contractType, employmentTypes);
  const payload: EmployeePayload = {
    name: state.name.trim(),
    nickname: nullable(state.nickname),
    email: nullable(state.personalEmail),
    phone: state.phone.trim(),
    address: nullable(state.address),
    province_id: nullable(state.provinceId),
    city_id: nullable(state.cityId),
    domicile_address: state.domicileSameAsKtp ? null : nullable(state.domicileAddress),
    domicile_province_id: state.domicileSameAsKtp ? null : nullable(state.domicileProvinceId),
    domicile_city_id: state.domicileSameAsKtp ? null : nullable(state.domicileCityId),
    birth_date: state.birthDate,
    birth_place: state.birthPlace.trim(),
    country_of_birth: nullable(state.countryOfBirth),
    gender: state.gender,
    religion_id: nullable(state.religionId),
    marital_status_id: nullable(state.maritalStatusId),
    blood_type_id: nullable(state.bloodTypeId),
    nationality: nullable(state.nationality),
    passport_number: nullable(state.passportNumber),
    department_id: state.departmentId,
    position_id: state.positionId,
    employment_type_id: employmentTypeId || null,
    contract_type: state.contractType,
    employee_level_id: nullable(state.employeeLevelId),
    work_location_id: nullable(state.workLocationId),
    supervisor_id: nullable(state.supervisorId),
    join_date: state.joinDate,
    probation_end_date: nullable(state.probationEndDate),
    nik: state.nik,
    npwp: nullable(state.npwp),
    bank_name: nullable(state.bankName),
    bank_account_number: nullable(state.bankAccountNumber),
    bank_account_holder_name: nullable(state.bankAccountOwner),
    salary: nullable(state.salary),
    allowances: nullable(state.allowances),
    deductions: nullable(state.deductions)
  };

  if (mode === "new") {
    payload.contract = {
      contract_type: state.contractType,
      contract_number: nullable(state.contractNumber),
      start_date: state.contractStartDate,
      end_date: state.contractType === "pkwt" ? nullable(state.contractEndDate) : null,
      notes: nullable(state.contractNotes)
    };

    if (state.emergencyName.trim()) {
      payload.emergency_contact = {
        name: state.emergencyName.trim(),
        relationship: nullable(state.emergencyRelationship),
        phone: nullable(state.emergencyPhone)
      };
    }
  }

  if (mode === "edit") {
    const editablePayload = { ...payload };
    delete editablePayload.contract;
    delete editablePayload.emergency_contact;
    return stripEmptyUpdate(editablePayload as EmployeePayload);
  }

  return payload;
}

export function validateEmployeeForm(state: EmployeeFormState, t: (key: string) => string): EmployeeFormErrors {
  const errors: EmployeeFormErrors = {};
  const required: Array<keyof EmployeeFormState> = [
    "name",
    "birthPlace",
    "birthDate",
    "gender",
    "phone",
    "nik",
    "departmentId",
    "positionId",
    "joinDate",
    "contractType",
    "contractStartDate"
  ];

  required.forEach((field) => {
    if (!String(state[field] ?? "").trim()) errors[field] = t("employeesForm.validation.required");
  });
  if (state.nik && !/^\d{16}$/.test(state.nik)) errors.nik = t("employeesForm.validation.nik");
  if (state.npwp && !/^\d{2}\.\d{3}\.\d{3}\.\d-\d{3}\.\d{3}$/.test(state.npwp)) {
    errors.npwp = t("employeesForm.validation.npwp");
  }
  if (state.contractType === "pkwt" && !state.contractEndDate) {
    errors.contractEndDate = t("employeesForm.validation.required");
  }
  if (state.contractStartDate && state.contractEndDate && state.contractEndDate <= state.contractStartDate) {
    errors.contractEndDate = t("employeesForm.validation.contractEndDate");
  }
  if (state.joinDate && state.probationEndDate && state.probationEndDate <= state.joinDate) {
    errors.probationEndDate = t("employeesForm.validation.probationEndDate");
  }

  return errors;
}

function value(input: unknown): string {
  return input === null || input === undefined ? "" : String(input);
}

function nullable(input: string): string | null {
  return input.trim() === "" ? null : input.trim();
}

function resolveEmploymentTypeId(contractType: string, options: MasterDataOption[]): string | number {
  const match = options.find((option) =>
    [option.code, option.name].some((item) => String(item ?? "").toLowerCase().includes(contractType))
  );

  return match?.id ?? options[0]?.id ?? "";
}

function stripEmptyUpdate(payload: EmployeePayload): EmployeeUpdatePayload {
  return Object.fromEntries(Object.entries(payload).filter(([, item]) => item !== "")) as EmployeeUpdatePayload;
}
