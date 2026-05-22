import { useTranslation } from "next-i18next/pages";
import {
  CheckboxField,
  EducationFields,
  ExperienceFields,
  FileFields,
  SelectField,
  TextareaField,
  TextField,
} from "@/components/recruitment/ApplicationFormFields";
import type { ApplicationFormData } from "@/types/recruitment";

type SetFormField = <K extends keyof ApplicationFormData>(
  key: K,
  value: ApplicationFormData[K],
) => void;

interface ApplicationFormSectionsProps {
  form: ApplicationFormData;
  setField: SetFormField;
  positionOptions: Array<{ id: string; label: string }>;
}

export default function ApplicationFormSections({
  form,
  setField,
  positionOptions,
}: ApplicationFormSectionsProps) {
  const { t } = useTranslation("recruitment");

  return (
    <>
      <div className="grid gap-4 md:grid-cols-2">
        <SelectField
          label={t("form.fields.jobPosting")}
          value={form.job_posting_id}
          onChange={(value) => setField("job_posting_id", value)}
          options={positionOptions}
          emptyLabel={t("form.placeholders.selectJob")}
        />
        <TextField
          label={t("form.fields.fullName")}
          value={form.full_name}
          onChange={(value) => setField("full_name", value)}
        />
        <TextField
          label={t("form.fields.birthPlace")}
          value={form.birth_place}
          onChange={(value) => setField("birth_place", value)}
        />
        <TextField
          type="date"
          label={t("form.fields.birthDate")}
          value={form.birth_date}
          onChange={(value) => setField("birth_date", value)}
        />
        <SelectField
          label={t("form.fields.gender")}
          value={form.gender}
          onChange={(value) => setField("gender", value)}
          options={[
            { id: "male", label: t("form.options.gender.male") },
            { id: "female", label: t("form.options.gender.female") },
          ]}
          emptyLabel={t("form.placeholders.select")}
        />
        <SelectField
          label={t("form.fields.maritalStatus")}
          value={form.marital_status}
          onChange={(value) => setField("marital_status", value)}
          options={[
            { id: "single", label: t("form.options.marital.single") },
            { id: "married", label: t("form.options.marital.married") },
            { id: "divorced", label: t("form.options.marital.divorced") },
          ]}
          emptyLabel={t("form.placeholders.select")}
        />
        <TextField
          type="number"
          label={t("form.fields.height")}
          value={form.height}
          onChange={(value) => setField("height", value)}
        />
        <TextField
          type="number"
          label={t("form.fields.weight")}
          value={form.weight}
          onChange={(value) => setField("weight", value)}
        />
        <TextField
          label={t("form.fields.citizenship")}
          value={form.citizenship}
          onChange={(value) => setField("citizenship", value)}
        />
        <TextField
          type="email"
          label={t("form.fields.email")}
          value={form.email}
          onChange={(value) => setField("email", value)}
        />
        <TextField
          label={t("form.fields.whatsapp")}
          value={form.whatsapp_number}
          onChange={(value) => setField("whatsapp_number", value)}
        />
        <SelectField
          label={t("form.fields.source")}
          value={form.source}
          onChange={(value) => setField("source", value)}
          options={[
            { id: "website", label: t("form.options.source.website") },
            { id: "jobstreet", label: t("form.options.source.jobstreet") },
            { id: "linkedin", label: t("form.options.source.linkedin") },
            { id: "referral", label: t("form.options.source.referral") },
            { id: "other", label: t("form.options.source.other") },
          ]}
          emptyLabel={t("form.placeholders.select")}
        />
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <TextareaField
          label={t("form.fields.addressKtp")}
          value={form.address_ktp}
          onChange={(value) => setField("address_ktp", value)}
        />
        <TextareaField
          label={t("form.fields.addressDomicile")}
          value={form.address_domisili}
          onChange={(value) => setField("address_domisili", value)}
        />
      </div>

      <div className="grid gap-3 md:grid-cols-3">
        <CheckboxField
          label={t("form.fields.hasGlasses")}
          checked={form.has_glasses}
          onChange={(value) => setField("has_glasses", value)}
        />
        <CheckboxField
          label={t("form.fields.isColorBlind")}
          checked={form.is_color_blind}
          onChange={(value) => setField("is_color_blind", value)}
        />
        <CheckboxField
          label={t("form.fields.hasWeldingSkill")}
          checked={form.has_welding_skill}
          onChange={(value) => setField("has_welding_skill", value)}
        />
      </div>

      <FileFields setField={setField} />
      <EducationFields form={form} setField={setField} />
      <ExperienceFields form={form} setField={setField} />
    </>
  );
}
