import type { MasterDataOption } from "@/types/employee";
import type { WilayahCity, WilayahCountry, WilayahProvince } from "@/types/employee-settings";
import {
  CheckboxField,
  FileField,
  optionsFromCities,
  optionsFromCountries,
  optionsFromMaster,
  optionsFromProvinces,
  SelectField,
  TextAreaField,
  TextField
} from "./EmployeeFormControls";
import type { EmployeeFormSectionBuilder } from "./employee-form-section-types";

interface ProfileLookups {
  religions: MasterDataOption[];
  maritalStatuses: MasterDataOption[];
  bloodTypes: MasterDataOption[];
  provinces: WilayahProvince[];
  ktpCities: WilayahCity[];
  domicileCities: WilayahCity[];
  countries: WilayahCountry[];
}

export function profileSections(lookups: ProfileLookups): EmployeeFormSectionBuilder {
  return ({ state, errors, t, update }) => [
    {
      id: "personal",
      title: t("employeesForm.sections.personal"),
      fields: [
        { id: "employee-form-photoFile", label: t("employeesForm.fields.photo"), content: <FileField field="photoFile" onChange={(file) => update("photoFile", file)} /> },
        { id: "employee-form-name", label: t("employeesDetail.fields.fullName"), required: true, error: errors.name, content: <TextField field="name" value={state.name} onChange={(value) => update("name", value)} /> },
        { id: "employee-form-nickname", label: t("employeesDetail.fields.nickname"), content: <TextField field="nickname" value={state.nickname} onChange={(value) => update("nickname", value)} /> },
        { id: "employee-form-birthPlace", label: t("employeesDetail.fields.birthPlace"), required: true, error: errors.birthPlace, content: <TextField field="birthPlace" value={state.birthPlace} onChange={(value) => update("birthPlace", value)} /> },
        { id: "employee-form-birthDate", label: t("employeesDetail.fields.birthDate"), required: true, error: errors.birthDate, content: <TextField field="birthDate" type="date" value={state.birthDate} onChange={(value) => update("birthDate", value)} /> },
        {
          id: "employee-form-gender",
          label: t("employeesDetail.fields.gender"),
          required: true,
          error: errors.gender,
          content: <SelectField field="gender" value={state.gender} placeholder={t("employeesDetail.values.choose")} options={genderOptions(t)} onChange={(value) => update("gender", value)} />
        },
        { id: "employee-form-phone", label: t("employeesDetail.fields.phone"), required: true, error: errors.phone, content: <TextField field="phone" value={state.phone} onChange={(value) => update("phone", value)} /> },
        { id: "employee-form-personalEmail", label: t("employeesDetail.fields.personalEmail"), error: errors.personalEmail, content: <TextField field="personalEmail" type="email" value={state.personalEmail} onChange={(value) => update("personalEmail", value)} /> }
      ]
    },
    {
      id: "identity",
      title: t("employeesForm.sections.identity"),
      fields: [
        { id: "employee-form-nik", label: t("employeesDetail.fields.nik"), required: true, error: errors.nik, content: <TextField field="nik" value={state.nik} onChange={(value) => update("nik", value)} /> },
        { id: "employee-form-passportNumber", label: t("employeesDetail.fields.passportNumber"), error: errors.passportNumber, content: <TextField field="passportNumber" value={state.passportNumber} onChange={(value) => update("passportNumber", value)} /> }
      ]
    },
    {
      id: "additional",
      title: t("employeesForm.sections.additional"),
      initiallyOpen: false,
      fields: [
        { id: "employee-form-religionId", label: t("employeesDetail.fields.religion"), content: <SelectField field="religionId" value={state.religionId} placeholder={t("employeesDetail.values.choose")} options={optionsFromMaster(lookups.religions)} onChange={(value) => update("religionId", value)} /> },
        { id: "employee-form-maritalStatusId", label: t("employeesDetail.fields.maritalStatus"), content: <SelectField field="maritalStatusId" value={state.maritalStatusId} placeholder={t("employeesDetail.values.choose")} options={optionsFromMaster(lookups.maritalStatuses)} onChange={(value) => update("maritalStatusId", value)} /> },
        { id: "employee-form-bloodTypeId", label: t("employeesDetail.fields.bloodType"), content: <SelectField field="bloodTypeId" value={state.bloodTypeId} placeholder={t("employeesDetail.values.choose")} options={optionsFromMaster(lookups.bloodTypes)} onChange={(value) => update("bloodTypeId", value)} /> },
        { id: "employee-form-nationality", label: t("employeesDetail.fields.nationality"), content: <TextField field="nationality" value={state.nationality} onChange={(value) => update("nationality", value)} /> },
        { id: "employee-form-countryOfBirth", label: t("employeesDetail.fields.countryOfBirth"), content: <SelectField field="countryOfBirth" value={state.countryOfBirth} placeholder={t("employeesDetail.values.choose")} options={optionsFromCountries(lookups.countries)} onChange={(value) => update("countryOfBirth", value)} /> }
      ]
    },
    {
      id: "ktp-address",
      title: t("employeesForm.sections.ktpAddress"),
      initiallyOpen: false,
      fields: [
        { id: "employee-form-provinceId", label: t("employeesDetail.fields.province"), content: <SelectField field="provinceId" value={state.provinceId} placeholder={t("employeesDetail.values.choose")} options={optionsFromProvinces(lookups.provinces)} onChange={(value) => update("provinceId", value)} /> },
        { id: "employee-form-cityId", label: t("employeesDetail.fields.city"), content: <SelectField field="cityId" value={state.cityId} placeholder={t("employeesDetail.values.choose")} options={optionsFromCities(lookups.ktpCities)} onChange={(value) => update("cityId", value)} /> },
        { id: "employee-form-address", label: t("employeesDetail.fields.address"), span: 2, content: <TextAreaField field="address" value={state.address} onChange={(value) => update("address", value)} /> }
      ]
    },
    {
      id: "domicile-address",
      title: t("employeesForm.sections.domicileAddress"),
      initiallyOpen: false,
      fields: [
        { id: "employee-form-domicileSameAsKtp", label: t("employeesDetail.fields.sameAsKtp"), span: 2, content: <CheckboxField field="domicileSameAsKtp" checked={state.domicileSameAsKtp} label={t("employeesDetail.fields.sameAsKtp")} onChange={(checked) => update("domicileSameAsKtp", checked)} /> },
        { id: "employee-form-domicileProvinceId", label: t("employeesDetail.fields.province"), content: <SelectField field="domicileProvinceId" value={state.domicileProvinceId} placeholder={t("employeesDetail.values.choose")} options={optionsFromProvinces(lookups.provinces)} disabled={state.domicileSameAsKtp} onChange={(value) => update("domicileProvinceId", value)} /> },
        { id: "employee-form-domicileCityId", label: t("employeesDetail.fields.city"), content: <SelectField field="domicileCityId" value={state.domicileCityId} placeholder={t("employeesDetail.values.choose")} options={optionsFromCities(lookups.domicileCities)} disabled={state.domicileSameAsKtp} onChange={(value) => update("domicileCityId", value)} /> },
        { id: "employee-form-domicileAddress", label: t("employeesDetail.fields.address"), span: 2, content: <TextAreaField field="domicileAddress" value={state.domicileAddress} disabled={state.domicileSameAsKtp} onChange={(value) => update("domicileAddress", value)} /> }
      ]
    },
    {
      id: "emergency",
      title: t("employeesForm.sections.emergency"),
      initiallyOpen: false,
      fields: [
        { id: "employee-form-emergencyName", label: t("employeesForm.fields.emergencyName"), content: <TextField field="emergencyName" value={state.emergencyName} onChange={(value) => update("emergencyName", value)} /> },
        { id: "employee-form-emergencyRelationship", label: t("employeesForm.fields.emergencyRelationship"), content: <TextField field="emergencyRelationship" value={state.emergencyRelationship} onChange={(value) => update("emergencyRelationship", value)} /> },
        { id: "employee-form-emergencyPhone", label: t("employeesForm.fields.emergencyPhone"), content: <TextField field="emergencyPhone" value={state.emergencyPhone} onChange={(value) => update("emergencyPhone", value)} /> }
      ]
    }
  ];
}

function genderOptions(t: (key: string) => string) {
  return [
    { value: "male", label: t("employeesForm.options.male") },
    { value: "female", label: t("employeesForm.options.female") }
  ];
}
