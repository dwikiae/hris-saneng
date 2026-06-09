"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { DocumentUpload } from "@/components/platform/DocumentUpload";
import { platformToast } from "@/components/platform/ToastProvider";
import type { EmployeeDetail } from "@/types/employee";
import { employeeService } from "@/services/employee.service";
import { DetailSection, FieldGrid, FieldItem, SensitiveField } from "./DetailBlocks";
import { display, formatDate, lookupName } from "./detail-utils";

interface EmployeeProfileTabProps {
  employee: EmployeeDetail;
  employeeId: string;
  t: (key: string) => string;
}

export function EmployeeProfileTab({ employee, employeeId, t }: EmployeeProfileTabProps) {
  const queryClient = useQueryClient();
  const photoMutation = useMutation({
    mutationFn: (file: File) => employeeService.uploadPhoto(employeeId, file),
    onSuccess: async () => {
      platformToast.success(t("employeesDetail.toast.photoUploaded"));
      await queryClient.invalidateQueries({ queryKey: ["employees", "detail", employeeId, "photo"] });
    },
    onError: () => platformToast.error(t("employeesDetail.toast.failed"))
  });

  return (
    <div className="space-y-4">
      <DetailSection title={t("employeesDetail.profile.personal")}>
        <div className="mb-5">
          <DocumentUpload
            uploadEndpoint={`/employees/${employeeId}/photo`}
            documents={[]}
            fieldName="photo"
            maxSizeMb={2}
            allowedMimeTypes={["image/png", "image/jpeg"]}
            allowedExtensions={[".png", ".jpg", ".jpeg"]}
            uploadFile={async (file) => {
              await photoMutation.mutateAsync(file);
              return {
                id: "photo",
                name: file.name,
                mimeType: file.type,
                sizeBytes: file.size,
                uploadedAt: t("platformBehavior.chat.justNow")
              };
            }}
          />
        </div>
        <FieldGrid>
          <FieldItem label={t("employeesDetail.fields.fullName")} value={employee.name} />
          <FieldItem label={t("employeesDetail.fields.nickname")} value={employee.nickname} />
          <FieldItem label={t("employeesDetail.fields.birthPlace")} value={employee.birth_place} />
          <FieldItem label={t("employeesDetail.fields.birthDate")} value={formatDate(employee.birth_date)} />
          <FieldItem label={t("employeesDetail.fields.gender")} value={employee.gender} />
          <FieldItem label={t("employeesDetail.fields.religion")} value={lookupName(employee.religion)} />
          <FieldItem label={t("employeesDetail.fields.maritalStatus")} value={lookupName(employee.marital_status)} />
          <FieldItem label={t("employeesDetail.fields.bloodType")} value={lookupName(employee.blood_type)} />
          <FieldItem label={t("employeesDetail.fields.nationality")} value={employee.nationality} />
          <FieldItem label={t("employeesDetail.fields.countryOfBirth")} value={employee.country_of_birth} />
        </FieldGrid>
      </DetailSection>

      <DetailSection title={t("employeesDetail.profile.identity")}>
        <FieldGrid>
          <SensitiveField label={t("employeesDetail.fields.nik")} value={employee.nik} />
          <SensitiveField label={t("employeesDetail.fields.ktpNumber")} value={employee.nik} />
          <SensitiveField label={t("employeesDetail.fields.passportNumber")} value={employee.passport_number} />
          <SensitiveField label={t("employeesDetail.fields.npwp")} value={employee.npwp} />
        </FieldGrid>
      </DetailSection>

      <DetailSection title={t("employeesDetail.profile.contact")}>
        <FieldGrid>
          <FieldItem label={t("employeesDetail.fields.phone")} value={employee.phone} />
          <FieldItem label={t("employeesDetail.fields.personalEmail")} value={employee.email} />
        </FieldGrid>
      </DetailSection>

      <DetailSection title={t("employeesDetail.profile.ktpAddress")}>
        <FieldGrid>
          <FieldItem label={t("employeesDetail.fields.province")} value={lookupName(employee.province)} />
          <FieldItem label={t("employeesDetail.fields.city")} value={lookupName(employee.city)} />
          <FieldItem label={t("employeesDetail.fields.address")} value={employee.address} />
        </FieldGrid>
      </DetailSection>

      <DetailSection title={t("employeesDetail.profile.domicileAddress")}>
        <FieldGrid>
          <FieldItem
            label={t("employeesDetail.fields.sameAsKtp")}
            value={employee.domicile_address ? t("employeesDetail.values.no") : t("employeesDetail.values.yes")}
          />
          <FieldItem label={t("employeesDetail.fields.province")} value={lookupName(employee.domicile_province)} />
          <FieldItem label={t("employeesDetail.fields.city")} value={lookupName(employee.domicile_city)} />
          <FieldItem label={t("employeesDetail.fields.address")} value={display(employee.domicile_address)} />
        </FieldGrid>
      </DetailSection>

      <DetailSection title={t("employeesDetail.profile.finance")}>
        <FieldGrid>
          <SensitiveField label={t("employeesDetail.fields.bankName")} value={employee.bank_name} />
          <SensitiveField label={t("employeesDetail.fields.bankAccount")} value={employee.bank_account_number} />
          <SensitiveField label={t("employeesDetail.fields.bankAccountOwner")} value={employee.bank_account_holder_name} />
          <SensitiveField label={t("employeesDetail.fields.salary")} value={employee.salary} />
          <SensitiveField label={t("employeesDetail.fields.allowances")} value={employee.allowances} />
          <SensitiveField label={t("employeesDetail.fields.deductions")} value={employee.deductions} />
        </FieldGrid>
      </DetailSection>
    </div>
  );
}
