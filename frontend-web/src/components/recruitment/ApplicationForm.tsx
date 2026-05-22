import { FormEvent, useEffect, useMemo, useState } from "react";
import { useTranslation } from "next-i18next/pages";
import ApplicationFormFeedback from "@/components/recruitment/ApplicationFormFeedback";
import ApplicationFormSections from "@/components/recruitment/ApplicationFormSections";
import { publicService } from "@/services/public.service";
import type {
  ApplicationFormData,
  EducationFormData,
  JobPosting,
} from "@/types/recruitment";

const maxFileSize = 2 * 1024 * 1024;
const allowedMimeTypes = ["application/pdf", "image/jpeg", "image/png"];

const emptyEducation: EducationFormData = {
  tingkat_pendidikan: "",
  nama_sekolah: "",
  jurusan: "",
  tahun_lulus: "",
  nilai_akhir: "",
};

function initialForm(jobId = ""): ApplicationFormData {
  return {
    job_posting_id: jobId,
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
    educations: [{ ...emptyEducation }],
    experiences: [],
  };
}

function sanitizeText(value: string): string {
  return value.replace(/[<>]/g, "").trim();
}

function normalizeForm(data: ApplicationFormData): ApplicationFormData {
  return {
    ...data,
    full_name: sanitizeText(data.full_name),
    birth_place: sanitizeText(data.birth_place),
    address_ktp: sanitizeText(data.address_ktp),
    address_domisili: sanitizeText(data.address_domisili),
    citizenship: sanitizeText(data.citizenship),
    email: sanitizeText(data.email),
    whatsapp_number: sanitizeText(data.whatsapp_number),
    educations: data.educations.map((education) => ({
      tingkat_pendidikan: sanitizeText(education.tingkat_pendidikan),
      nama_sekolah: sanitizeText(education.nama_sekolah),
      jurusan: sanitizeText(education.jurusan),
      tahun_lulus: sanitizeText(education.tahun_lulus),
      nilai_akhir: sanitizeText(education.nilai_akhir),
    })),
    experiences: data.experiences
      .map((experience) => ({
        nama_perusahaan: sanitizeText(experience.nama_perusahaan),
        posisi: sanitizeText(experience.posisi),
        masa_kerja_dari: sanitizeText(experience.masa_kerja_dari),
        masa_kerja_sampai: sanitizeText(experience.masa_kerja_sampai),
      }))
      .filter((experience) =>
        Object.values(experience).some((value) => value.trim()),
      ),
  };
}

function validateFile(file: File | null, required: boolean): string | null {
  if (!file) {
    return required ? "form.errors.requiredCv" : null;
  }

  if (!allowedMimeTypes.includes(file.type)) {
    return "form.errors.fileType";
  }

  if (file.size > maxFileSize) {
    return "form.errors.fileSize";
  }

  return null;
}

function getJobTitle(job: JobPosting): string {
  return (
    job.nama_posisi ??
    job.position_name ??
    job.title ??
    String(job.id)
  );
}

interface ApplicationFormProps {
  jobs: JobPosting[];
  selectedJobId: number | null;
}

export default function ApplicationForm({
  jobs,
  selectedJobId,
}: ApplicationFormProps) {
  const { t } = useTranslation("recruitment");
  const [form, setForm] = useState<ApplicationFormData>(() =>
    initialForm(selectedJobId ? String(selectedJobId) : ""),
  );
  const [errors, setErrors] = useState<string[]>([]);
  const [apiMessage, setApiMessage] = useState<string | null>(null);
  const [isSuccess, setIsSuccess] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formVersion, setFormVersion] = useState(0);

  useEffect(() => {
    if (selectedJobId) {
      setForm((current) => ({
        ...current,
        job_posting_id: String(selectedJobId),
      }));
    }
  }, [selectedJobId]);

  const positionOptions = useMemo(
    () => jobs.map((job) => ({ id: String(job.id), label: getJobTitle(job) })),
    [jobs],
  );

  const setField = <K extends keyof ApplicationFormData>(
    key: K,
    value: ApplicationFormData[K],
  ) => {
    setForm((current) => ({ ...current, [key]: value }));
  };

  const validate = (data: ApplicationFormData): string[] => {
    const nextErrors: string[] = [];
    const requiredValues = [
      data.job_posting_id,
      data.full_name,
      data.birth_place,
      data.birth_date,
      data.gender,
      data.address_ktp,
      data.address_domisili,
      data.height,
      data.weight,
      data.marital_status,
      data.citizenship,
      data.email,
      data.whatsapp_number,
      data.source,
    ];

    if (requiredValues.some((value) => !value.trim())) {
      nextErrors.push("form.errors.required");
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
      nextErrors.push("form.errors.email");
    }

    const cvError = validateFile(data.cv_path, true);
    const certificateError = validateFile(data.certificate_path, false);

    if (cvError) {
      nextErrors.push(cvError);
    }

    if (certificateError) {
      nextErrors.push(certificateError);
    }

    if (
      data.educations.length < 1 ||
      data.educations.some((education) =>
        Object.values(education).some((value) => !value.trim()),
      )
    ) {
      nextErrors.push("form.errors.education");
    }

    if (
      data.experiences.some((experience) => {
        const values = Object.values(experience);
        return values.some((value) => value.trim()) && values.some((value) => !value.trim());
      })
    ) {
      nextErrors.push("form.errors.experience");
    }

    return Array.from(new Set(nextErrors));
  };

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const normalized = normalizeForm(form);
    const nextErrors = validate(normalized);
    setErrors(nextErrors);
    setApiMessage(null);
    setIsSuccess(false);

    if (nextErrors.length > 0) {
      return;
    }

    setIsSubmitting(true);

    try {
      await publicService.submitApplication(normalized);
      setIsSuccess(true);
      setForm(initialForm());
      setFormVersion((current) => current + 1);
    } catch (error) {
      setApiMessage(error instanceof Error ? error.message : "form.errors.submit");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <section
      id="application-form"
      className="mx-auto w-full max-w-6xl px-4 pb-12 sm:px-6 lg:px-8"
    >
      <div className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div className="mb-6">
          <p className="text-sm font-semibold uppercase tracking-wide text-emerald-700">
            {t("form.eyebrow")}
          </p>
          <h2 className="mt-2 text-2xl font-semibold text-slate-950">
            {t("form.title")}
          </h2>
          <p className="mt-2 text-sm leading-6 text-slate-600">
            {t("form.description")}
          </p>
        </div>

        <form
          key={formVersion}
          className="space-y-6"
          onSubmit={(event) => void submit(event)}
        >
          <input type="hidden" name="job_posting_id" value={form.job_posting_id} />

          <ApplicationFormSections
            form={form}
            setField={setField}
            positionOptions={positionOptions}
          />

          <ApplicationFormFeedback
            errors={errors}
            apiMessage={apiMessage}
            isSuccess={isSuccess}
          />

          <button
            type="submit"
            className="w-full rounded-md bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
            disabled={isSubmitting}
          >
            {isSubmitting ? t("form.actions.submitting") : t("form.actions.submit")}
          </button>
        </form>
      </div>
    </section>
  );
}
