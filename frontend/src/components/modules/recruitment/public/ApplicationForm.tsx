"use client";

import type { FormEvent, ReactNode } from "react";
import { useMemo, useState } from "react";
import { useTranslation } from "react-i18next";
import { publicService } from "@/services/public.service";
import type { ApplicationFormData, JobPosting } from "@/types/recruitment";

interface ApplicationFormProps {
  jobs: JobPosting[];
  selectedJobId: number | null;
}

const emptyData: ApplicationFormData = {
  job_posting_id: "",
  full_name: "",
  birth_place: "",
  birth_date: "",
  gender: "",
  address_ktp: "",
  address_domisili: "",
  height: "",
  weight: "",
  has_glasses: false,
  is_color_blind: false,
  marital_status: "",
  citizenship: "",
  email: "",
  whatsapp_number: "",
  has_welding_skill: false,
  source: "",
  cv_path: null,
  certificate_path: null,
  educations: [],
  experiences: []
};

export function ApplicationForm({ jobs, selectedJobId }: ApplicationFormProps) {
  const { t } = useTranslation(["common", "recruitment"]);
  const [data, setData] = useState<ApplicationFormData>(emptyData);
  const [status, setStatus] = useState<"idle" | "submitting" | "success" | "failed">("idle");

  const selectedValue = useMemo(
    () => String(selectedJobId ?? data.job_posting_id),
    [data.job_posting_id, selectedJobId]
  );

  const update = (key: keyof ApplicationFormData, value: string | boolean | File | null) => {
    setData((current) => ({ ...current, [key]: value }));
  };

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setStatus("submitting");

    try {
      await publicService.submitApplication({ ...data, job_posting_id: selectedValue });
      setStatus("success");
      setData(emptyData);
    } catch {
      setStatus("failed");
    }
  };

  return (
    <section id="application-form" className="border-t border-slate-200 bg-white">
      <form onSubmit={submit} className="mx-auto grid w-full max-w-6xl gap-4 px-4 py-10 sm:px-6 md:grid-cols-2 lg:px-8">
        <div className="md:col-span-2">
          <h2 className="text-2xl font-semibold text-slate-950">{t("recruitment:form.title")}</h2>
          <p className="mt-2 text-sm text-slate-600">{t("recruitment:form.description")}</p>
        </div>
        <Field label={t("recruitment:form.job")}>
          <select
            required
            value={selectedValue}
            className="w-full rounded-md border border-slate-300 px-3 py-2"
            onChange={(event) => update("job_posting_id", event.target.value)}
          >
            <option value="">{t("common:form.selectPlaceholder")}</option>
            {jobs.map((job) => (
              <option key={job.id} value={job.id}>
                {job.title ?? job.nama_posisi ?? job.position_name ?? job.code}
              </option>
            ))}
          </select>
        </Field>
        <Input label={t("recruitment:form.fullName")} value={data.full_name} onChange={(value) => update("full_name", value)} />
        <Input label={t("recruitment:form.birthPlace")} value={data.birth_place} onChange={(value) => update("birth_place", value)} />
        <Input label={t("recruitment:form.birthDate")} type="date" value={data.birth_date} onChange={(value) => update("birth_date", value)} />
        <Input label={t("recruitment:form.gender")} value={data.gender} onChange={(value) => update("gender", value)} />
        <Input label={t("recruitment:form.email")} type="email" value={data.email} onChange={(value) => update("email", value)} />
        <Input label={t("recruitment:form.whatsapp")} value={data.whatsapp_number} onChange={(value) => update("whatsapp_number", value)} />
        <Input label={t("recruitment:form.addressKtp")} value={data.address_ktp} onChange={(value) => update("address_ktp", value)} />
        <Input label={t("recruitment:form.addressDomicile")} value={data.address_domisili} onChange={(value) => update("address_domisili", value)} />
        <Input label={t("recruitment:form.height")} value={data.height} onChange={(value) => update("height", value)} />
        <Input label={t("recruitment:form.weight")} value={data.weight} onChange={(value) => update("weight", value)} />
        <Input label={t("recruitment:form.maritalStatus")} value={data.marital_status} onChange={(value) => update("marital_status", value)} />
        <Input label={t("recruitment:form.citizenship")} value={data.citizenship} onChange={(value) => update("citizenship", value)} />
        <Input label={t("recruitment:form.source")} value={data.source} onChange={(value) => update("source", value)} />
        <FileInput label={t("recruitment:form.cv")} onChange={(file) => update("cv_path", file)} />
        <FileInput label={t("recruitment:form.certificate")} onChange={(file) => update("certificate_path", file)} />
        <Check label={t("recruitment:form.hasGlasses")} checked={data.has_glasses} onChange={(value) => update("has_glasses", value)} />
        <Check label={t("recruitment:form.isColorBlind")} checked={data.is_color_blind} onChange={(value) => update("is_color_blind", value)} />
        <Check label={t("recruitment:form.hasWeldingSkill")} checked={data.has_welding_skill} onChange={(value) => update("has_welding_skill", value)} />
        <div className="md:col-span-2">
          <button
            type="submit"
            disabled={status === "submitting"}
            className="rounded-md bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:opacity-60"
          >
            {t("recruitment:form.submit")}
          </button>
          {status === "success" ? <p className="mt-3 text-sm font-medium text-emerald-700">{t("recruitment:form.success")}</p> : null}
          {status === "failed" ? <p className="mt-3 text-sm font-medium text-red-700">{t("recruitment:form.failed")}</p> : null}
        </div>
      </form>
    </section>
  );
}

function Field({ label, children }: { label: string; children: ReactNode }) {
  return (
    <label className="block text-sm font-medium text-slate-700">
      <span>{label}</span>
      <span className="mt-1 block">{children}</span>
    </label>
  );
}

function Input({ label, value, onChange, type = "text" }: { label: string; value: string; type?: string; onChange: (value: string) => void }) {
  return (
    <Field label={label}>
      <input required type={type} value={value} className="w-full rounded-md border border-slate-300 px-3 py-2" onChange={(event) => onChange(event.target.value)} />
    </Field>
  );
}

function FileInput({ label, onChange }: { label: string; onChange: (file: File | null) => void }) {
  return (
    <Field label={label}>
      <input type="file" className="w-full rounded-md border border-slate-300 px-3 py-2" onChange={(event) => onChange(event.target.files?.[0] ?? null)} />
    </Field>
  );
}

function Check({ label, checked, onChange }: { label: string; checked: boolean; onChange: (value: boolean) => void }) {
  return (
    <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
      <input type="checkbox" checked={checked} onChange={(event) => onChange(event.target.checked)} />
      <span>{label}</span>
    </label>
  );
}
