import { useTranslation } from "next-i18next/pages";
import type { ReactNode } from "react";
import type {
  ApplicationFormData,
  EducationFormData,
  ExperienceFormData,
} from "@/types/recruitment";

type SetFormField = <K extends keyof ApplicationFormData>(
  key: K,
  value: ApplicationFormData[K],
) => void;

interface FieldProps {
  label: string;
  value: string;
  onChange: (value: string) => void;
  type?: string;
}

export function TextField({
  label,
  value,
  onChange,
  type = "text",
}: FieldProps) {
  return (
    <label className="block">
      <span className="text-sm font-medium text-slate-700">{label}</span>
      <input
        type={type}
        value={value}
        onChange={(event) => onChange(event.target.value)}
        className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-950 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
      />
    </label>
  );
}

export function TextareaField({ label, value, onChange }: FieldProps) {
  return (
    <label className="block">
      <span className="text-sm font-medium text-slate-700">{label}</span>
      <textarea
        value={value}
        onChange={(event) => onChange(event.target.value)}
        rows={4}
        className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-950 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
      />
    </label>
  );
}

interface SelectFieldProps extends FieldProps {
  options: Array<{ id: string; label: string }>;
  emptyLabel: string;
}

export function SelectField({
  label,
  value,
  onChange,
  options,
  emptyLabel,
}: SelectFieldProps) {
  return (
    <label className="block">
      <span className="text-sm font-medium text-slate-700">{label}</span>
      <select
        value={value}
        onChange={(event) => onChange(event.target.value)}
        className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-950 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
      >
        <option value="">{emptyLabel}</option>
        {options.map((option) => (
          <option key={option.id} value={option.id}>
            {option.label}
          </option>
        ))}
      </select>
    </label>
  );
}

interface CheckboxFieldProps {
  label: string;
  checked: boolean;
  onChange: (value: boolean) => void;
}

export function CheckboxField({
  label,
  checked,
  onChange,
}: CheckboxFieldProps) {
  return (
    <label className="flex items-center gap-3 rounded-md border border-slate-200 p-3 text-sm font-medium text-slate-700">
      <input
        type="checkbox"
        checked={checked}
        onChange={(event) => onChange(event.target.checked)}
        className="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
      />
      <span>{label}</span>
    </label>
  );
}

export function FileFields({
  setField,
}: {
  setField: SetFormField;
}) {
  const { t } = useTranslation("recruitment");

  return (
    <div className="grid gap-4 md:grid-cols-2">
      <FileField
        label={t("form.fields.cv")}
        onChange={(file) => setField("cv_path", file)}
      />
      <FileField
        label={t("form.fields.certificate")}
        onChange={(file) => setField("certificate_path", file)}
      />
      <p className="text-xs text-slate-500 md:col-span-2">
        {t("form.help.file")}
      </p>
    </div>
  );
}

function FileField({
  label,
  onChange,
}: {
  label: string;
  onChange: (file: File | null) => void;
}) {
  return (
    <label className="block">
      <span className="text-sm font-medium text-slate-700">{label}</span>
      <input
        type="file"
        accept="application/pdf,image/jpeg,image/png"
        onChange={(event) => onChange(event.target.files?.[0] ?? null)}
        className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-950 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700"
      />
    </label>
  );
}

export function EducationFields({
  form,
  setField,
}: {
  form: ApplicationFormData;
  setField: SetFormField;
}) {
  const { t } = useTranslation("recruitment");

  return (
    <FieldGroup title={t("form.sections.education")}>
      {form.educations.map((education, index) => (
        <div key={index} className="grid gap-4 rounded-md bg-slate-50 p-4 md:grid-cols-5">
          {educationKeys.map((key) => (
            <TextField
              key={key}
              type={key === "tahun_lulus" || key === "nilai_akhir" ? "number" : "text"}
              label={t(`form.fields.${key}`)}
              value={education[key]}
              onChange={(value) =>
                updateEducation(form, setField, index, key, value)
              }
            />
          ))}
        </div>
      ))}
    </FieldGroup>
  );
}

const educationKeys: Array<keyof EducationFormData> = [
  "tingkat_pendidikan",
  "nama_sekolah",
  "jurusan",
  "tahun_lulus",
  "nilai_akhir",
];

function updateEducation(
  form: ApplicationFormData,
  setField: SetFormField,
  index: number,
  key: keyof EducationFormData,
  value: string,
) {
  const educations = form.educations.map((education, rowIndex) =>
    rowIndex === index ? { ...education, [key]: value } : education,
  );
  setField("educations", educations);
}

export function ExperienceFields({
  form,
  setField,
}: {
  form: ApplicationFormData;
  setField: SetFormField;
}) {
  const { t } = useTranslation("recruitment");

  return (
    <FieldGroup title={t("form.sections.experience")}>
      {form.experiences.map((experience, index) => (
        <div key={index} className="grid gap-4 rounded-md bg-slate-50 p-4 md:grid-cols-4">
          {experienceKeys.map((key) => (
            <TextField
              key={key}
              type={key.includes("masa_kerja") ? "date" : "text"}
              label={t(`form.fields.${key}`)}
              value={experience[key]}
              onChange={(value) =>
                updateExperience(form, setField, index, key, value)
              }
            />
          ))}
        </div>
      ))}
      <button
        type="button"
        className="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-500"
        onClick={() =>
          setField("experiences", [
            ...form.experiences,
            {
              nama_perusahaan: "",
              posisi: "",
              masa_kerja_dari: "",
              masa_kerja_sampai: "",
            },
          ])
        }
      >
        {t("form.actions.addExperience")}
      </button>
    </FieldGroup>
  );
}

const experienceKeys: Array<keyof ExperienceFormData> = [
  "nama_perusahaan",
  "posisi",
  "masa_kerja_dari",
  "masa_kerja_sampai",
];

function updateExperience(
  form: ApplicationFormData,
  setField: SetFormField,
  index: number,
  key: keyof ExperienceFormData,
  value: string,
) {
  const experiences = form.experiences.map((experience, rowIndex) =>
    rowIndex === index ? { ...experience, [key]: value } : experience,
  );
  setField("experiences", experiences);
}

function FieldGroup({
  title,
  children,
}: {
  title: string;
  children: ReactNode;
}) {
  return (
    <section className="space-y-3">
      <h3 className="text-base font-semibold text-slate-950">{title}</h3>
      {children}
    </section>
  );
}
