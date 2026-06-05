"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Archive } from "lucide-react";
import { PermissionGate } from "@/components/platform/PermissionGate";
import { platformToast } from "@/components/platform/ToastProvider";
import { Button } from "@/components/ui/button";
import { employeeLookupService, employeeService } from "@/services/employee.service";
import type {
  EmployeeEducation,
  EmployeeEducationPayload,
  EmployeeExperience,
  EmployeeExperiencePayload
} from "@/types/employee";
import {
  CheckInput,
  DetailSection,
  EmptyTab,
  SelectInput,
  TabError,
  TabSkeleton,
  TextAreaInput,
  TextInput
} from "./DetailBlocks";
import { FormDialog, formString, nullableNumber, nullableString } from "./FormDialog";
import { formatDate } from "./detail-utils";

interface Props {
  employeeId: string;
  enabled: boolean;
  t: (key: string) => string;
}

export function EmployeeEducationExperienceTab({ employeeId, enabled, t }: Props) {
  const queryClient = useQueryClient();
  const educationQuery = useQuery({
    queryKey: ["employees", "detail", employeeId, "education"],
    queryFn: () => employeeService.getEducation(employeeId),
    enabled
  });
  const experienceQuery = useQuery({
    queryKey: ["employees", "detail", employeeId, "experience"],
    queryFn: () => employeeService.getExperience(employeeId),
    enabled
  });
  const levelsQuery = useQuery({
    queryKey: ["employees", "education-levels"],
    queryFn: employeeLookupService.educationLevels,
    enabled
  });
  const invalidate = async () => {
    await queryClient.invalidateQueries({ queryKey: ["employees", "detail", employeeId, "education"] });
    await queryClient.invalidateQueries({ queryKey: ["employees", "detail", employeeId, "experience"] });
  };
  const createEducation = useMutation({
    mutationFn: (payload: EmployeeEducationPayload) => employeeService.createEducation(employeeId, payload),
    onSuccess: async () => {
      platformToast.success(t("employeesDetail.toast.saved"));
      await invalidate();
    },
    onError: () => platformToast.error(t("employeesDetail.toast.failed"))
  });
  const createExperience = useMutation({
    mutationFn: (payload: EmployeeExperiencePayload) => employeeService.createExperience(employeeId, payload),
    onSuccess: async () => {
      platformToast.success(t("employeesDetail.toast.saved"));
      await invalidate();
    },
    onError: () => platformToast.error(t("employeesDetail.toast.failed"))
  });
  const archiveEducation = useMutation({
    mutationFn: (id: string | number) => employeeService.archiveEducation(employeeId, id),
    onSuccess: async () => {
      platformToast.success(t("employeesDetail.toast.archived"));
      await invalidate();
    }
  });
  const archiveExperience = useMutation({
    mutationFn: (id: string | number) => employeeService.archiveExperience(employeeId, id),
    onSuccess: async () => {
      platformToast.success(t("employeesDetail.toast.archived"));
      await invalidate();
    }
  });

  if (educationQuery.isLoading || experienceQuery.isLoading) {
    return <TabSkeleton />;
  }
  if (educationQuery.isError || experienceQuery.isError) {
    return <TabError message={t("employeesDetail.api.educationExperience")} onRetry={() => void invalidate()} />;
  }

  return (
    <div className="space-y-4">
      <DetailSection title={t("employeesDetail.education.title")}>
        <div className="mb-4 flex justify-end">
          <PermissionGate permission="employee.update">
            <FormDialog
              title={t("employeesDetail.education.newTitle")}
              triggerLabel={t("employeesDetail.education.new")}
              onSubmit={(payload) => createEducation.mutateAsync(payload)}
              toPayload={educationPayload}
            >
              <EducationFields levels={levelsQuery.data ?? []} t={t} />
            </FormDialog>
          </PermissionGate>
        </div>
        <Timeline
          emptyTitle={t("employeesDetail.empty.educationTitle")}
          items={(educationQuery.data ?? []).map((education) => ({
            id: education.id,
            title: education.institution_name,
            subtitle: [education.education_level?.name, education.major].filter(Boolean).join(" - "),
            meta: `${education.start_year} - ${education.end_year ?? t("employeesDetail.values.present")}`,
            onArchive: () => archiveEducation.mutate(education.id)
          }))}
        />
      </DetailSection>
      <DetailSection title={t("employeesDetail.experience.title")}>
        <div className="mb-4 flex justify-end">
          <PermissionGate permission="employee.update">
            <FormDialog
              title={t("employeesDetail.experience.newTitle")}
              triggerLabel={t("employeesDetail.experience.new")}
              onSubmit={(payload) => createExperience.mutateAsync(payload)}
              toPayload={experiencePayload}
            >
              <ExperienceFields t={t} />
            </FormDialog>
          </PermissionGate>
        </div>
        <Timeline
          emptyTitle={t("employeesDetail.empty.experienceTitle")}
          items={(experienceQuery.data ?? []).map((experience) => ({
            id: experience.id,
            title: experience.company_name,
            subtitle: experience.position,
            meta: `${formatDate(experience.start_date)} - ${
              experience.is_current ? t("employeesDetail.values.present") : formatDate(experience.end_date)
            }`,
            badge: experience.is_current ? t("employeesDetail.experience.current") : undefined,
            onArchive: () => archiveExperience.mutate(experience.id)
          }))}
        />
      </DetailSection>
    </div>
  );
}

function Timeline({
  emptyTitle,
  items
}: {
  emptyTitle: string;
  items: Array<{ id: string | number; title: string; subtitle: string; meta: string; badge?: string; onArchive: () => void }>;
}) {
  if (items.length === 0) {
    return <EmptyTab title={emptyTitle} />;
  }

  return (
    <div className="space-y-3">
      {items.map((item) => (
        <div key={item.id} className="flex gap-3 rounded-lg border border-border bg-background p-4">
          <div className="mt-1 h-3 w-3 rounded-full bg-primary" />
          <div className="min-w-0 flex-1">
            <div className="flex flex-wrap items-center gap-2">
              <p className="font-semibold text-foreground">{item.title}</p>
              {item.badge ? <span className="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">{item.badge}</span> : null}
            </div>
            <p className="text-sm text-muted-foreground">{item.subtitle}</p>
            <p className="text-xs text-muted-foreground">{item.meta}</p>
          </div>
          <PermissionGate permission="employee.archive">
            <Button type="button" variant="ghost" size="icon" onClick={item.onArchive}>
              <Archive className="h-4 w-4" aria-hidden="true" />
            </Button>
          </PermissionGate>
        </div>
      ))}
    </div>
  );
}

function EducationFields({ levels, t }: { levels: Array<{ id: string | number; name: string }>; t: Props["t"] }) {
  return (
    <>
      <TextInput label={t("employeesDetail.fields.institution")} name="institution_name" required />
      <SelectInput label={t("employeesDetail.fields.educationLevel")} name="education_level_id" required>
        <option value="">{t("employeesDetail.values.choose")}</option>
        {levels.map((level) => (
          <option key={level.id} value={level.id}>{level.name}</option>
        ))}
      </SelectInput>
      <TextInput label={t("employeesDetail.fields.major")} name="major" />
      <TextInput label={t("employeesDetail.fields.startYear")} name="start_year" type="number" required />
      <TextInput label={t("employeesDetail.fields.endYear")} name="end_year" type="number" />
      <TextInput label={t("employeesDetail.fields.gpa")} name="gpa" type="number" />
      <TextInput label={t("employeesDetail.fields.certificateNumber")} name="certificate_number" />
    </>
  );
}

function ExperienceFields({ t }: { t: Props["t"] }) {
  return (
    <>
      <TextInput label={t("employeesDetail.fields.companyName")} name="company_name" required />
      <TextInput label={t("employeesDetail.fields.position")} name="position" required />
      <TextInput label={t("employeesDetail.fields.startDate")} name="start_date" type="date" required />
      <TextInput label={t("employeesDetail.fields.endDate")} name="end_date" type="date" />
      <CheckInput label={t("employeesDetail.fields.isCurrent")} name="is_current" />
      <TextAreaInput label={t("employeesDetail.fields.responsibilities")} name="responsibilities" />
      <TextInput label={t("employeesDetail.fields.reasonLeaving")} name="reason_leaving" />
    </>
  );
}

function educationPayload(formData: FormData): EmployeeEducationPayload {
  return {
    institution_name: formString(formData, "institution_name"),
    education_level_id: formString(formData, "education_level_id"),
    major: nullableString(formData, "major"),
    start_year: Number(formString(formData, "start_year")),
    end_year: nullableNumber(formData, "end_year"),
    gpa: nullableString(formData, "gpa"),
    certificate_number: nullableString(formData, "certificate_number")
  };
}

function experiencePayload(formData: FormData): EmployeeExperiencePayload {
  return {
    company_name: formString(formData, "company_name"),
    position: formString(formData, "position"),
    start_date: formString(formData, "start_date"),
    end_date: nullableString(formData, "end_date"),
    is_current: formData.get("is_current") === "on",
    responsibilities: nullableString(formData, "responsibilities"),
    reason_leaving: nullableString(formData, "reason_leaving")
  };
}
