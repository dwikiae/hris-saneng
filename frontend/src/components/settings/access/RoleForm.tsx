"use client";

import { Save } from "lucide-react";
import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { FormPageTemplate, type FormTemplateSection } from "@/components/templates";
import { Input } from "@/components/ui/input";
import type { AccessCompanyRef, PlatformRole, RolePayload } from "@/types/access";

interface RoleFormProps {
  role?: PlatformRole;
  companies: AccessCompanyRef[];
  roles: PlatformRole[];
  lookupUnavailable: boolean;
  isSubmitting: boolean;
  onSubmit: (payload: RolePayload) => void;
}

export function RoleForm({ role, companies, roles, lookupUnavailable, isSubmitting, onSubmit }: RoleFormProps) {
  const { t } = useTranslation("platform");
  const [name, setName] = useState(role?.name ?? "");
  const [description, setDescription] = useState(role?.description ?? "");
  const [companyId, setCompanyId] = useState(role?.company ? String(role.company.id) : "");
  const [copyFromRoleId, setCopyFromRoleId] = useState("");
  const [copyFromJobPositionId, setCopyFromJobPositionId] = useState("");
  const [isDirty, setIsDirty] = useState(!role);

  useEffect(() => {
    setName(role?.name ?? "");
    setDescription(role?.description ?? "");
    setCompanyId(role?.company ? String(role.company.id) : "");
    setIsDirty(!role);
  }, [role]);

  const dirty = () => setIsDirty(true);
  const selectClass = "h-9 w-full rounded-md border border-input bg-background px-3 text-sm";
  const sections: FormTemplateSection[] = [
    {
      id: "role",
      title: t("settingsAccess.roleForm.sections.identity"),
      fields: [
        {
          id: "roleName",
          label: t("settingsAccess.fields.roleName"),
          required: true,
          content: <Input id="roleName" value={name} onChange={(event) => { setName(event.target.value); dirty(); }} />
        },
        {
          id: "company",
          label: t("settingsAccess.fields.company"),
          required: true,
          content: (
            <select id="company" className={selectClass} value={companyId} onChange={(event) => { setCompanyId(event.target.value); dirty(); }}>
              <option value="">{t("settingsAccess.filters.companyAll")}</option>
              {companies.map((company) => <option key={company.id} value={company.id}>{company.name}</option>)}
            </select>
          )
        },
        {
          id: "description",
          label: t("settingsAccess.fields.description"),
          span: 2,
          content: <textarea className="min-h-24 w-full rounded-md border border-input px-3 py-2 text-sm" value={description ?? ""} onChange={(event) => { setDescription(event.target.value); dirty(); }} />
        }
      ]
    },
    {
      id: "copy",
      title: t("settingsAccess.roleForm.sections.copyFrom"),
      description: lookupUnavailable ? t("settingsAccess.api.lookupsNotReady") : undefined,
      fields: [
        {
          id: "copyRole",
          label: t("settingsAccess.fields.copyFromRole"),
          content: (
            <select id="copyRole" className={selectClass} value={copyFromRoleId} onChange={(event) => { setCopyFromRoleId(event.target.value); dirty(); }}>
              <option value="">{t("settingsAccess.roleForm.noTemplate")}</option>
              {roles.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
            </select>
          )
        },
        {
          id: "copyPosition",
          label: t("settingsAccess.fields.copyFromJobPosition"),
          content: <Input id="copyPosition" value={copyFromJobPositionId} placeholder={t("settingsAccess.roleForm.jobPositionPlaceholder")} onChange={(event) => { setCopyFromJobPositionId(event.target.value); dirty(); }} />
        }
      ]
    }
  ];

  return (
    <FormPageTemplate
      title={role ? t("settingsAccess.roles.editTitle") : t("settingsAccess.roles.newTitle")}
      description={t("settingsAccess.roles.formDescription")}
      backUrl={role ? `/dashboard/settings/roles/${role.id}` : "/dashboard/settings/users?tab=roles"}
      backLabel={t("settingsAccess.actions.back")}
      sections={sections}
      isDirty={isDirty}
      isSubmitting={isSubmitting}
      footerActions={[
        {
          id: "save",
          label: t("settingsAccess.actions.save"),
          icon: <Save className="h-4 w-4" />,
          variant: "primary",
          onClick: () => onSubmit({ name, description, companyId: companyId || null, copyFromRoleId: copyFromRoleId || null, copyFromJobPositionId: copyFromJobPositionId || null })
        }
      ]}
    />
  );
}
