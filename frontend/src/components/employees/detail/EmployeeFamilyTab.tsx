"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Archive } from "lucide-react";
import { PermissionGate } from "@/components/platform/PermissionGate";
import { platformToast } from "@/components/platform/ToastProvider";
import { Button } from "@/components/ui/button";
import { employeeService } from "@/services/employee.service";
import type { EmployeeFamily, EmployeeFamilyPayload } from "@/types/employee";
import {
  CheckInput,
  DetailSection,
  EmptyTab,
  SelectInput,
  TabError,
  TabSkeleton,
  TextInput
} from "./DetailBlocks";
import { FormDialog, formString, nullableString } from "./FormDialog";
import { formatDate } from "./detail-utils";

interface EmployeeFamilyTabProps {
  employeeId: string;
  enabled: boolean;
  t: (key: string) => string;
}

export function EmployeeFamilyTab({ employeeId, enabled, t }: EmployeeFamilyTabProps) {
  const queryClient = useQueryClient();
  const query = useQuery({
    queryKey: ["employees", "detail", employeeId, "family"],
    queryFn: () => employeeService.getFamily(employeeId),
    enabled
  });
  const invalidate = () => queryClient.invalidateQueries({ queryKey: ["employees", "detail", employeeId, "family"] });
  const createMutation = useMutation({
    mutationFn: (payload: EmployeeFamilyPayload) => employeeService.createFamily(employeeId, payload),
    onSuccess: async () => {
      platformToast.success(t("employeesDetail.toast.saved"));
      await invalidate();
    },
    onError: () => platformToast.error(t("employeesDetail.toast.failed"))
  });
  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: string | number; payload: EmployeeFamilyPayload }) =>
      employeeService.updateFamily(employeeId, id, payload),
    onSuccess: async () => {
      platformToast.success(t("employeesDetail.toast.saved"));
      await invalidate();
    },
    onError: () => platformToast.error(t("employeesDetail.toast.failed"))
  });
  const archiveMutation = useMutation({
    mutationFn: (id: string | number) => employeeService.archiveFamily(employeeId, id),
    onSuccess: async () => {
      platformToast.success(t("employeesDetail.toast.archived"));
      await invalidate();
    },
    onError: () => platformToast.error(t("employeesDetail.toast.failed"))
  });

  if (query.isLoading) {
    return <TabSkeleton />;
  }
  if (query.isError) {
    return <TabError message={t("employeesDetail.api.family")} onRetry={() => void query.refetch()} />;
  }

  const family = query.data ?? [];

  return (
    <DetailSection title={t("employeesDetail.family.title")}>
      <div className="mb-4 flex justify-end">
        <PermissionGate permission="employee.update">
          <FormDialog
            title={t("employeesDetail.family.newTitle")}
            triggerLabel={t("employeesDetail.family.new")}
            onSubmit={(payload) => createMutation.mutateAsync(payload)}
            toPayload={familyPayload}
          >
            <FamilyFields t={t} />
          </FormDialog>
        </PermissionGate>
      </div>
      {family.length === 0 ? (
        <EmptyTab title={t("employeesDetail.empty.familyTitle")} description={t("employeesDetail.empty.familyDescription")} />
      ) : (
        <div className="overflow-hidden rounded-lg border border-border">
          {family.map((member) => (
            <div key={member.id} className="grid gap-3 border-b border-border p-4 last:border-b-0 md:grid-cols-[1.5fr_1fr_1fr_1fr_auto] md:items-center">
              <div>
                <p className="font-medium text-foreground">{member.name}</p>
                <p className="text-sm text-muted-foreground">{member.occupation ?? "-"}</p>
              </div>
              <p className="text-sm text-muted-foreground">{t(`employeesDetail.relationship.${member.relationship}`)}</p>
              <p className="text-sm text-muted-foreground">{formatDate(member.birth_date)}</p>
              <p className="text-sm text-muted-foreground">
                {member.is_dependent ? t("employeesDetail.values.yes") : t("employeesDetail.values.no")}
              </p>
              <div className="flex gap-2">
                <PermissionGate permission="employee.update">
                  <FormDialog
                    title={t("employeesDetail.family.editTitle")}
                    triggerLabel={t("employeesDetail.actions.edit")}
                    onSubmit={(payload) => updateMutation.mutateAsync({ id: member.id, payload })}
                    toPayload={familyPayload}
                  >
                    <FamilyFields member={member} t={t} />
                  </FormDialog>
                </PermissionGate>
                <PermissionGate permission="employee.archive">
                  <Button type="button" variant="outline" onClick={() => archiveMutation.mutate(member.id)}>
                    <Archive className="h-4 w-4" aria-hidden="true" />
                  </Button>
                </PermissionGate>
              </div>
            </div>
          ))}
        </div>
      )}
    </DetailSection>
  );
}

function FamilyFields({ member, t }: { member?: EmployeeFamily; t: EmployeeFamilyTabProps["t"] }) {
  return (
    <>
      <TextInput label={t("employeesDetail.fields.name")} name="name" defaultValue={member?.name} required />
      <SelectInput label={t("employeesDetail.fields.relationship")} name="relationship" defaultValue={member?.relationship} required>
        {["spouse", "child", "parent", "sibling", "other"].map((value) => (
          <option key={value} value={value}>
            {t(`employeesDetail.relationship.${value}`)}
          </option>
        ))}
      </SelectInput>
      <TextInput label={t("employeesDetail.fields.birthDate")} name="birth_date" type="date" defaultValue={member?.birth_date} />
      <TextInput label={t("employeesDetail.fields.gender")} name="gender" defaultValue={member?.gender} required />
      <TextInput label={t("employeesDetail.fields.occupation")} name="occupation" defaultValue={member?.occupation} />
      <TextInput label={t("employeesDetail.fields.phone")} name="phone" defaultValue={member?.phone} />
      <CheckInput label={t("employeesDetail.fields.isDependent")} name="is_dependent" defaultChecked={Boolean(member?.is_dependent)} />
    </>
  );
}

function familyPayload(formData: FormData): EmployeeFamilyPayload {
  return {
    name: formString(formData, "name"),
    relationship: formString(formData, "relationship"),
    birth_date: nullableString(formData, "birth_date"),
    gender: formString(formData, "gender"),
    occupation: nullableString(formData, "occupation"),
    phone: nullableString(formData, "phone"),
    is_dependent: formData.get("is_dependent") === "on"
  };
}
