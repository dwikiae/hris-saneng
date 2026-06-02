import { publicPath, requestJson, requestMultipart } from "@/services/api-client";
import type { ApplicationFormData, JobPosting } from "@/types/recruitment";

function appendNestedFields<T extends object>(formData: FormData, prefix: string, rows: T[]): void {
  rows.forEach((row, rowIndex) => {
    Object.entries(row).forEach(([key, value]) => {
      formData.append(`${prefix}[${rowIndex}][${key}]`, String(value));
    });
  });
}

function buildApplicationPayload(data: ApplicationFormData): FormData {
  const formData = new FormData();

  formData.append("job_posting_id", data.job_posting_id);
  formData.append("full_name", data.full_name);
  formData.append("name", data.full_name);
  formData.append("birth_place", data.birth_place);
  formData.append("birth_date", data.birth_date);
  formData.append("gender", data.gender);
  formData.append("address_ktp", data.address_ktp);
  formData.append("address_domisili", data.address_domisili);
  formData.append("height", data.height);
  formData.append("weight", data.weight);
  formData.append("has_glasses", data.has_glasses ? "1" : "0");
  formData.append("is_color_blind", data.is_color_blind ? "1" : "0");
  formData.append("marital_status", data.marital_status);
  formData.append("citizenship", data.citizenship);
  formData.append("email", data.email);
  formData.append("whatsapp_number", data.whatsapp_number);
  formData.append("phone", data.whatsapp_number);
  formData.append("has_welding_skill", data.has_welding_skill ? "1" : "0");
  formData.append("source", data.source);
  appendNestedFields(formData, "educations", data.educations);
  appendNestedFields(formData, "experiences", data.experiences);

  if (data.cv_path) {
    formData.append("cv_path", data.cv_path);
    formData.append("cv", data.cv_path);
  }

  if (data.certificate_path) {
    formData.append("certificate_path", data.certificate_path);
    formData.append("certificate", data.certificate_path);
  }

  return formData;
}

export const publicService = {
  getPublicJobs: async (): Promise<JobPosting[]> => {
    try {
      return await requestJson<JobPosting[]>(publicPath("/jobs"));
    } catch {
      return [];
    }
  },

  submitApplication: (data: ApplicationFormData): Promise<unknown> =>
    requestMultipart<unknown>(publicPath("/applications"), buildApplicationPayload(data))
};
