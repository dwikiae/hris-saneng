import { privateHeaders, privatePath, requestPrivateJson } from "@/services/api-client";
import { useAuthStore } from "@/stores/auth.store";
import type { ApiResponse } from "@/types/api";
import type {
  EmployeeContract,
  EmployeeContractPayload,
  EmployeeDetail,
  EmployeeDocument,
  EmployeeEducation,
  EmployeeEducationPayload,
  EmployeeExperience,
  EmployeeExperiencePayload,
  EmployeeFamily,
  EmployeeFamilyPayload,
  EmployeePayload,
  EmployeeListItem,
  EmployeeListParams,
  EmployeeListResponse,
  EmployeeNote,
  EmployeeOffboarding,
  EmployeeOffboardingPayload,
  EmployeeOffboardingState,
  EmployeePhotoUrls,
  EmployeeUpdatePayload,
  OffboardingChecklistItem,
  OffboardingChecklistPayload,
  MasterDataOption
} from "@/types/employee";
import type { ChatNoteItem, PlatformDocument } from "@/types/platform";

interface LaravelPaginator<T> {
  data: T[];
  current_page?: number;
  per_page?: number;
  total?: number;
}

function queryString(params: EmployeeListParams): string {
  const query = new URLSearchParams();

  Object.entries(params).forEach(([key, value]) => {
    if (value === undefined || value === "") {
      return;
    }

    const apiKey =
      key === "departmentId"
        ? "department_id"
        : key === "contractType"
          ? "contract_type"
          : key === "perPage"
            ? "per_page"
            : key === "sortBy"
              ? "sort_by"
              : key === "sortDir"
                ? "sort_dir"
                : key;

    query.set(apiKey, String(value));
  });

  const serialized = query.toString();
  return serialized ? `?${serialized}` : "";
}

function employeeContextHeaders(explicitCompanyId?: string | number | null): Record<string, string> {
  if (explicitCompanyId !== null && explicitCompanyId !== undefined) {
    return { "X-Company-Id": String(explicitCompanyId) };
  }

  const activeCompanyId = useAuthStore.getState().activeCompanyId;

  return activeCompanyId ? { "X-Company-Id": activeCompanyId } : {};
}

function withEmployeeContext(init: RequestInit = {}, companyId?: string | number | null): RequestInit {
  return {
    ...init,
    headers: {
      ...employeeContextHeaders(companyId),
      ...(init.headers as Record<string, string> | undefined)
    }
  };
}

function requestEmployeeJson<T>(path: string, init?: RequestInit, companyId?: string | number | null): Promise<T> {
  return requestPrivateJson<T>(path, withEmployeeContext(init, companyId));
}

async function requestEmployeeMultipart<T>(path: string, formData: FormData, companyId?: string | number | null): Promise<T> {
  const response = await fetch(privatePath(path), {
    method: "POST",
    headers: {
      Accept: "application/json",
      ...privateHeaders(),
      ...employeeContextHeaders(companyId)
    },
    body: formData
  });
  const payload = (await response.json()) as ApiResponse<T>;

  if (!response.ok || !payload.success || payload.data === undefined) {
    throw new Error(payload.message);
  }

  return payload.data;
}

async function requestEmployeeList(path: string, companyId?: string | number | null): Promise<EmployeeListResponse> {
  const response = await fetch(privatePath(path), {
    headers: {
      Accept: "application/json",
      ...privateHeaders(),
      ...employeeContextHeaders(companyId)
    }
  });
  const payload = (await response.json()) as ApiResponse<LaravelPaginator<EmployeeListItem>>;

  if (!response.ok || !payload.success || !payload.data) {
    throw new Error(payload.message);
  }

  return {
    items: payload.data.data ?? [],
    meta: {
      current_page: payload.data.current_page,
      per_page: payload.data.per_page,
      total: payload.data.total
    }
  };
}

export const employeeService = {
  list(params: EmployeeListParams = {}): Promise<EmployeeListResponse> {
    return requestEmployeeList(`/employees${queryString(params)}`);
  },
  listArchived(params: EmployeeListParams = {}): Promise<EmployeeListResponse> {
    return requestEmployeeList(`/employees${queryString({ ...params, archived: true })}`);
  },
  create(payload: EmployeePayload): Promise<EmployeeDetail> {
    return requestEmployeeJson<EmployeeDetail>("/employees", {
      method: "POST",
      body: JSON.stringify(payload)
    });
  },
  update(employeeId: string | number, payload: EmployeeUpdatePayload): Promise<EmployeeDetail> {
    return requestEmployeeJson<EmployeeDetail>(`/employees/${employeeId}`, {
      method: "PUT",
      body: JSON.stringify(payload)
    });
  },
  submitForApproval(employeeId: string | number): Promise<EmployeeDetail> {
    return requestEmployeeJson<EmployeeDetail>(`/employees/${employeeId}/approve`, { method: "POST" });
  },
  saveDraft(payload: EmployeePayload): Promise<EmployeeDetail> {
    return requestEmployeeJson<EmployeeDetail>("/employees", {
      method: "POST",
      body: JSON.stringify({ ...payload, status: "draft" })
    });
  },
  archive(employeeId: string | number): Promise<null> {
    return requestEmployeeJson<null>(`/employees/${employeeId}/archive`, { method: "POST" });
  },
  restore(employeeId: string | number): Promise<EmployeeListItem> {
    return requestEmployeeJson<EmployeeListItem>(`/archive/employees/${employeeId}/restore`, { method: "POST" });
  },
  getDetail(employeeId: string | number): Promise<EmployeeDetail> {
    return requestEmployeeJson<EmployeeDetail>(`/employees/${employeeId}`);
  },
  approve(employeeId: string | number): Promise<EmployeeDetail> {
    return requestEmployeeJson<EmployeeDetail>(`/employees/${employeeId}/approve`, { method: "POST" });
  },
  reject(employeeId: string | number, reason: string): Promise<EmployeeDetail> {
    return requestEmployeeJson<EmployeeDetail>(`/employees/${employeeId}/reject`, {
      method: "POST",
      body: JSON.stringify({ reason })
    });
  },
  getPhoto(employeeId: string | number): Promise<EmployeePhotoUrls> {
    return requestEmployeeJson<EmployeePhotoUrls>(`/employees/${employeeId}/photo`);
  },
  uploadPhoto(employeeId: string | number, file: File): Promise<EmployeePhotoUrls> {
    const formData = new FormData();
    formData.append("photo", file);

    return requestEmployeeMultipart<EmployeePhotoUrls>(`/employees/${employeeId}/photo`, formData);
  },
  getContracts(employeeId: string | number): Promise<EmployeeContract[]> {
    return requestEmployeeJson<EmployeeContract[]>(`/employees/${employeeId}/contracts`);
  },
  createContract(employeeId: string | number, payload: EmployeeContractPayload): Promise<EmployeeContract> {
    return requestEmployeeJson<EmployeeContract>(`/employees/${employeeId}/contracts`, {
      method: "POST",
      body: JSON.stringify(payload)
    });
  },
  updateContract(
    employeeId: string | number,
    contractId: string | number,
    payload: EmployeeContractPayload
  ): Promise<EmployeeContract> {
    return requestEmployeeJson<EmployeeContract>(`/employees/${employeeId}/contracts/${contractId}`, {
      method: "PUT",
      body: JSON.stringify(payload)
    });
  },
  approveContract(employeeId: string | number, contractId: string | number): Promise<EmployeeContract> {
    return requestEmployeeJson<EmployeeContract>(`/employees/${employeeId}/contracts/${contractId}/approve`, {
      method: "POST"
    });
  },
  archiveContract(employeeId: string | number, contractId: string | number): Promise<null> {
    return requestEmployeeJson<null>(`/employees/${employeeId}/contracts/${contractId}/archive`, { method: "POST" });
  },
  getFamily(employeeId: string | number): Promise<EmployeeFamily[]> {
    return requestEmployeeJson<EmployeeFamily[]>(`/employees/${employeeId}/family`);
  },
  createFamily(employeeId: string | number, payload: EmployeeFamilyPayload): Promise<EmployeeFamily> {
    return requestEmployeeJson<EmployeeFamily>(`/employees/${employeeId}/family`, {
      method: "POST",
      body: JSON.stringify(payload)
    });
  },
  updateFamily(employeeId: string | number, familyId: string | number, payload: EmployeeFamilyPayload): Promise<EmployeeFamily> {
    return requestEmployeeJson<EmployeeFamily>(`/employees/${employeeId}/family/${familyId}`, {
      method: "PUT",
      body: JSON.stringify(payload)
    });
  },
  archiveFamily(employeeId: string | number, familyId: string | number): Promise<null> {
    return requestEmployeeJson<null>(`/employees/${employeeId}/family/${familyId}/archive`, { method: "POST" });
  },
  getEducation(employeeId: string | number): Promise<EmployeeEducation[]> {
    return requestEmployeeJson<EmployeeEducation[]>(`/employees/${employeeId}/education`);
  },
  createEducation(employeeId: string | number, payload: EmployeeEducationPayload): Promise<EmployeeEducation> {
    return requestEmployeeJson<EmployeeEducation>(`/employees/${employeeId}/education`, {
      method: "POST",
      body: JSON.stringify(payload)
    });
  },
  updateEducation(
    employeeId: string | number,
    educationId: string | number,
    payload: EmployeeEducationPayload
  ): Promise<EmployeeEducation> {
    return requestEmployeeJson<EmployeeEducation>(`/employees/${employeeId}/education/${educationId}`, {
      method: "PUT",
      body: JSON.stringify(payload)
    });
  },
  archiveEducation(employeeId: string | number, educationId: string | number): Promise<null> {
    return requestEmployeeJson<null>(`/employees/${employeeId}/education/${educationId}/archive`, { method: "POST" });
  },
  getExperience(employeeId: string | number): Promise<EmployeeExperience[]> {
    return requestEmployeeJson<EmployeeExperience[]>(`/employees/${employeeId}/experience`);
  },
  createExperience(employeeId: string | number, payload: EmployeeExperiencePayload): Promise<EmployeeExperience> {
    return requestEmployeeJson<EmployeeExperience>(`/employees/${employeeId}/experience`, {
      method: "POST",
      body: JSON.stringify(payload)
    });
  },
  updateExperience(
    employeeId: string | number,
    experienceId: string | number,
    payload: EmployeeExperiencePayload
  ): Promise<EmployeeExperience> {
    return requestEmployeeJson<EmployeeExperience>(`/employees/${employeeId}/experience/${experienceId}`, {
      method: "PUT",
      body: JSON.stringify(payload)
    });
  },
  archiveExperience(employeeId: string | number, experienceId: string | number): Promise<null> {
    return requestEmployeeJson<null>(`/employees/${employeeId}/experience/${experienceId}/archive`, { method: "POST" });
  },
  getNotes(employeeId: string | number): Promise<EmployeeNote[]> {
    return requestEmployeeJson<EmployeeNote[]>(`/employees/${employeeId}/notes`);
  },
  createNote(employeeId: string | number, content: string): Promise<EmployeeNote> {
    return requestEmployeeJson<EmployeeNote>(`/employees/${employeeId}/notes`, {
      method: "POST",
      body: JSON.stringify({ content })
    });
  },
  getOffboarding(employeeId: string | number): Promise<EmployeeOffboardingState> {
    return requestEmployeeJson<EmployeeOffboardingState>(`/employees/${employeeId}/offboarding`);
  },
  createOffboarding(employeeId: string | number, payload: EmployeeOffboardingPayload): Promise<EmployeeOffboarding> {
    return requestEmployeeJson<EmployeeOffboarding>(`/employees/${employeeId}/offboarding`, {
      method: "POST",
      body: JSON.stringify(payload)
    });
  },
  updateOffboarding(
    employeeId: string | number,
    offboardingId: string | number,
    payload: EmployeeOffboardingPayload
  ): Promise<EmployeeOffboarding> {
    return requestEmployeeJson<EmployeeOffboarding>(`/employees/${employeeId}/offboarding/${offboardingId}`, {
      method: "PUT",
      body: JSON.stringify(payload)
    });
  },
  completeOffboarding(employeeId: string | number, offboardingId: string | number): Promise<EmployeeOffboarding> {
    return requestEmployeeJson<EmployeeOffboarding>(`/employees/${employeeId}/offboarding/${offboardingId}/complete`, {
      method: "POST"
    });
  },
  getOffboardingChecklist(employeeId: string | number, offboardingId: string | number): Promise<OffboardingChecklistItem[]> {
    return requestEmployeeJson<OffboardingChecklistItem[]>(`/employees/${employeeId}/offboarding/${offboardingId}/checklist`);
  },
  createOffboardingChecklist(
    employeeId: string | number,
    offboardingId: string | number,
    payload: OffboardingChecklistPayload
  ): Promise<OffboardingChecklistItem> {
    return requestEmployeeJson<OffboardingChecklistItem>(`/employees/${employeeId}/offboarding/${offboardingId}/checklist`, {
      method: "POST",
      body: JSON.stringify(payload)
    });
  },
  updateOffboardingChecklist(
    employeeId: string | number,
    offboardingId: string | number,
    itemId: string | number,
    payload: OffboardingChecklistPayload
  ): Promise<OffboardingChecklistItem> {
    return requestEmployeeJson<OffboardingChecklistItem>(
      `/employees/${employeeId}/offboarding/${offboardingId}/checklist/${itemId}`,
      { method: "PUT", body: JSON.stringify(payload) }
    );
  },
  completeOffboardingChecklist(
    employeeId: string | number,
    offboardingId: string | number,
    itemId: string | number
  ): Promise<OffboardingChecklistItem> {
    return requestEmployeeJson<OffboardingChecklistItem>(
      `/employees/${employeeId}/offboarding/${offboardingId}/checklist/${itemId}/complete`,
      { method: "POST" }
    );
  },
  archiveOffboardingChecklist(employeeId: string | number, offboardingId: string | number, itemId: string | number): Promise<null> {
    return requestEmployeeJson<null>(`/employees/${employeeId}/offboarding/${offboardingId}/checklist/${itemId}/archive`, {
      method: "POST"
    });
  },
  async getDocuments(employeeId: string | number): Promise<PlatformDocument[]> {
    const documents = await requestEmployeeJson<EmployeeDocument[]>(`/employees/${employeeId}/documents`);
    return documents.map(toPlatformDocument);
  },
  async uploadDocument(employeeId: string | number, file: File, documentType = "general"): Promise<PlatformDocument> {
    const formData = new FormData();
    formData.append("doc_type", documentType);
    formData.append("document", file);
    const document = await requestEmployeeMultipart<EmployeeDocument>(`/employees/${employeeId}/documents`, formData);
    return toPlatformDocument(document);
  },
  archiveDocument(employeeId: string | number, documentId: string | number): Promise<null> {
    return requestEmployeeJson<null>(`/employees/${employeeId}/documents/${documentId}`, { method: "DELETE" });
  }
};

export const employeeLookupService = {
  departments(): Promise<MasterDataOption[]> {
    return requestEmployeeJson<MasterDataOption[]>("/master-data/departments?is_active=1");
  },
  employmentTypes(): Promise<MasterDataOption[]> {
    return requestEmployeeJson<MasterDataOption[]>("/master-data/employment-types?is_active=1");
  },
  educationLevels(): Promise<MasterDataOption[]> {
    return requestEmployeeJson<MasterDataOption[]>("/master-data/education-levels?is_active=1");
  },
  positions(): Promise<MasterDataOption[]> {
    return requestEmployeeJson<MasterDataOption[]>("/master-data/positions?is_active=1");
  },
  religions(): Promise<MasterDataOption[]> {
    return requestEmployeeJson<MasterDataOption[]>("/master-data/religions?is_active=1");
  },
  maritalStatuses(): Promise<MasterDataOption[]> {
    return requestEmployeeJson<MasterDataOption[]>("/master-data/marital-statuses?is_active=1");
  },
  bloodTypes(): Promise<MasterDataOption[]> {
    return requestEmployeeJson<MasterDataOption[]>("/master-data/blood-types?is_active=1");
  },
  banks(): Promise<MasterDataOption[]> {
    return requestEmployeeJson<MasterDataOption[]>("/master-data/banks?is_active=1");
  },
  documentTypes(): Promise<MasterDataOption[]> {
    return requestEmployeeJson<MasterDataOption[]>("/master-data/document-types?is_active=1");
  }
};

export function employeeNoteToChat(note: EmployeeNote): ChatNoteItem {
  return {
    id: note.id,
    actor: note.created_by_user?.name ?? String(note.created_by ?? "-"),
    body: note.content,
    createdAt: note.created_at ?? "-"
  };
}

function toPlatformDocument(document: EmployeeDocument): PlatformDocument {
  return {
    id: document.id,
    name: document.original_filename ?? document.document_type ?? String(document.id),
    mimeType: document.mime_type ?? "application/octet-stream",
    sizeBytes: document.size_bytes ?? 0,
    uploadedBy: document.uploaded_by ? String(document.uploaded_by) : undefined,
    uploadedAt: document.uploaded_at ?? undefined
  };
}
