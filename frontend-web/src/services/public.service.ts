import type {
  ApiResponse,
  ApplicationFormData,
  JobPosting,
} from "@/types/recruitment";

const publicApiUrl = process.env.NEXT_PUBLIC_API_URL ?? "";

function publicJobsUrl(): string | null {
  if (!publicApiUrl.trim()) {
    return null;
  }

  const baseUrl = publicApiUrl.replace(/\/$/, "");

  if (baseUrl.endsWith("/api/v1")) {
    return `${baseUrl}/public/jobs`;
  }

  return `${baseUrl}/api/v1/public/jobs`;
}

function unwrapData<T>(payload: ApiResponse<T>): T {
  if (payload.data === undefined) {
    throw new Error("error.empty_response");
  }

  if (
    payload.data &&
    typeof payload.data === "object" &&
    "data" in payload.data
  ) {
    return (payload.data as { data: T }).data;
  }

  return payload.data;
}

async function request<T>(url: string): Promise<T> {
  const response = await fetch(url, {
    headers: {
      Accept: "application/json",
    },
  });
  const payload = (await response.json()) as ApiResponse<T>;

  if (!response.ok || !payload.success) {
    throw new Error(payload.message);
  }

  return unwrapData(payload);
}

async function submitMultipart<T>(url: string, formData: FormData): Promise<T> {
  const response = await fetch(url, {
    method: "POST",
    headers: {
      Accept: "application/json",
    },
    body: formData,
  });
  const payload = (await response.json()) as ApiResponse<T>;

  if (!response.ok || !payload.success) {
    throw new Error(payload.message);
  }

  return unwrapData(payload);
}

function appendNestedFields<T extends object>(
  formData: FormData,
  prefix: string,
  rows: T[],
): void {
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
    const url = publicJobsUrl();

    if (!url) {
      return [];
    }

    return request<JobPosting[]>(url);
  },

  submitApplication: async (data: ApplicationFormData): Promise<unknown> => {
    const url = publicApiUrl
      ? `${publicApiUrl.replace(/\/$/, "")}${
          publicApiUrl.endsWith("/api/v1")
            ? "/public/applications"
            : "/api/v1/public/applications"
        }`
      : null;

    if (!url) {
      throw new Error("form.errors.apiUrl");
    }

    return submitMultipart<unknown>(url, buildApplicationPayload(data));
  },
};
