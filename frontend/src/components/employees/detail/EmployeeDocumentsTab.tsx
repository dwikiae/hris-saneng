"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { DocumentUpload } from "@/components/platform/DocumentUpload";
import { platformToast } from "@/components/platform/ToastProvider";
import { employeeLookupService, employeeService } from "@/services/employee.service";
import type { PlatformDocument } from "@/types/platform";
import { DetailSection, TabError, TabSkeleton } from "./DetailBlocks";

interface EmployeeDocumentsTabProps {
  employeeId: string;
  enabled: boolean;
  t: (key: string) => string;
}

export function EmployeeDocumentsTab({ employeeId, enabled, t }: EmployeeDocumentsTabProps) {
  const queryClient = useQueryClient();
  const [documentType, setDocumentType] = useState("general");
  const documentsQuery = useQuery({
    queryKey: ["employees", "detail", employeeId, "documents"],
    queryFn: () => employeeService.getDocuments(employeeId),
    enabled
  });
  const typesQuery = useQuery({
    queryKey: ["employees", "document-types"],
    queryFn: employeeLookupService.documentTypes,
    enabled
  });
  const invalidate = () => queryClient.invalidateQueries({ queryKey: ["employees", "detail", employeeId, "documents"] });
  const uploadMutation = useMutation({
    mutationFn: (file: File) => employeeService.uploadDocument(employeeId, file, documentType),
    onSuccess: async () => {
      platformToast.success(t("employeesDetail.toast.documentUploaded"));
      await invalidate();
    },
    onError: () => platformToast.error(t("employeesDetail.toast.failed"))
  });
  const archiveMutation = useMutation({
    mutationFn: (document: PlatformDocument) => employeeService.archiveDocument(employeeId, document.id),
    onSuccess: async () => {
      platformToast.success(t("employeesDetail.toast.archived"));
      await invalidate();
    },
    onError: () => platformToast.error(t("employeesDetail.toast.failed"))
  });

  if (documentsQuery.isLoading) {
    return <TabSkeleton />;
  }
  if (documentsQuery.isError) {
    return <TabError message={t("employeesDetail.api.documents")} onRetry={() => void documentsQuery.refetch()} />;
  }

  return (
    <DetailSection title={t("employeesDetail.documents.title")}>
      <div className="mb-4 max-w-xs">
        <label className="space-y-1 text-sm font-medium text-foreground">
          <span>{t("employeesDetail.documents.filterType")}</span>
          <select
            value={documentType}
            className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
            onChange={(event) => setDocumentType(event.target.value)}
          >
            <option value="general">{t("employeesDetail.documents.general")}</option>
            {(typesQuery.data ?? []).map((type) => (
              <option key={type.id} value={type.code ?? type.id}>{type.name}</option>
            ))}
          </select>
        </label>
      </div>
      <DocumentUpload
        uploadEndpoint={`/employees/${employeeId}/documents`}
        documents={documentsQuery.data ?? []}
        maxSizeMb={10}
        uploadFile={(file) => uploadMutation.mutateAsync(file)}
        archiveFile={(document) => archiveMutation.mutateAsync(document)}
      />
    </DetailSection>
  );
}
