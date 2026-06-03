"use client";

import { Save } from "lucide-react";
import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { FormPageTemplate, type FormTemplateSection } from "@/components/templates";
import { Input } from "@/components/ui/input";
import type { AccessCompanyRef, AccessRoleRef, PlatformUser, UserPayload } from "@/types/access";

interface UserFormProps {
  mode: "new" | "edit";
  user?: PlatformUser;
  companies: AccessCompanyRef[];
  roles: AccessRoleRef[];
  lookupUnavailable: boolean;
  isSubmitting: boolean;
  onSubmit: (payload: UserPayload) => void;
}

export function UserForm({
  mode,
  user,
  companies,
  roles,
  lookupUnavailable,
  isSubmitting,
  onSubmit
}: UserFormProps) {
  const { t } = useTranslation("platform");
  const [name, setName] = useState(user?.name ?? "");
  const [email, setEmail] = useState(user?.email ?? "");
  const [employeeId, setEmployeeId] = useState(user?.employeeId ? String(user.employeeId) : "");
  const [companyIds, setCompanyIds] = useState<string[]>(() => user?.companies?.map((company) => String(company.id)) ?? []);
  const [roleIds, setRoleIds] = useState<string[]>(() => user?.roles?.map((role) => String(role.id)) ?? []);
  const [note, setNote] = useState("");
  const [isDirty, setIsDirty] = useState(mode === "new");
  const emailLocked = mode === "edit" && user?.status === "active";

  useEffect(() => {
    setName(user?.name ?? "");
    setEmail(user?.email ?? "");
    setEmployeeId(user?.employeeId ? String(user.employeeId) : "");
    setCompanyIds(user?.companies?.map((company) => String(company.id)) ?? []);
    setRoleIds(user?.roles?.map((role) => String(role.id)) ?? []);
    setIsDirty(mode === "new");
  }, [mode, user]);

  const markDirty = () => setIsDirty(true);
  const toggle = (value: string, current: string[], setter: (values: string[]) => void) => {
    setter(current.includes(value) ? current.filter((item) => item !== value) : [...current, value]);
    markDirty();
  };
  const multiCheck = (
    options: Array<{ id: string | number; name: string }>,
    selected: string[],
    setter: (values: string[]) => void
  ) => (
    <div className="grid gap-2 sm:grid-cols-2">
      {options.map((option) => {
        const value = String(option.id);
        return (
          <label key={value} className="flex items-center gap-2 rounded-md border border-border p-3 text-sm">
            <input type="checkbox" checked={selected.includes(value)} onChange={() => toggle(value, selected, setter)} />
            <span>{option.name}</span>
          </label>
        );
      })}
      {options.length === 0 ? <p className="text-sm text-muted-foreground">{t("settingsAccess.api.lookupsNotReady")}</p> : null}
    </div>
  );

  const sections: FormTemplateSection[] = [
    {
      id: "account",
      title: t("settingsAccess.userForm.sections.account"),
      fields: [
        {
          id: "name",
          label: t("settingsAccess.fields.name"),
          required: true,
          content: <Input id="name" value={name} onChange={(event) => { setName(event.target.value); markDirty(); }} />
        },
        {
          id: "email",
          label: t("settingsAccess.fields.email"),
          required: true,
          content: <Input id="email" type="email" disabled={emailLocked} value={email} onChange={(event) => { setEmail(event.target.value); markDirty(); }} />
        }
      ]
    },
    {
      id: "employee",
      title: t("settingsAccess.userForm.sections.employee"),
      description: t("settingsAccess.userForm.employeeHint"),
      fields: [
        {
          id: "employeeId",
          label: t("settingsAccess.fields.employee"),
          span: 2,
          content: <Input id="employeeId" value={employeeId} placeholder={t("settingsAccess.userForm.employeePlaceholder")} onChange={(event) => { setEmployeeId(event.target.value); markDirty(); }} />
        }
      ]
    },
    {
      id: "companies",
      title: t("settingsAccess.userForm.sections.companies"),
      description: employeeId ? t("settingsAccess.userForm.companyHidden") : lookupUnavailable ? t("settingsAccess.api.lookupsNotReady") : undefined,
      fields: [
        {
          id: "companyIds",
          label: t("settingsAccess.fields.companies"),
          span: 2,
          content: employeeId ? <p className="text-sm text-muted-foreground">{t("settingsAccess.userForm.companyHidden")}</p> : multiCheck(companies, companyIds, setCompanyIds)
        }
      ]
    },
    {
      id: "roles",
      title: t("settingsAccess.userForm.sections.roles"),
      description: lookupUnavailable ? t("settingsAccess.api.lookupsNotReady") : undefined,
      fields: [
        { id: "roleIds", label: t("settingsAccess.fields.roles"), span: 2, content: multiCheck(roles, roleIds, setRoleIds) }
      ]
    },
    {
      id: "note",
      title: t("settingsAccess.userForm.sections.note"),
      fields: [
        {
          id: "invitationNote",
          label: t("settingsAccess.fields.note"),
          span: 2,
          content: <textarea className="min-h-24 w-full rounded-md border border-input px-3 py-2 text-sm" value={note} onChange={(event) => { setNote(event.target.value); markDirty(); }} />
        }
      ]
    }
  ];

  return (
    <FormPageTemplate
      title={mode === "new" ? t("settingsAccess.users.newTitle") : t("settingsAccess.users.editTitle")}
      description={t("settingsAccess.users.formDescription")}
      backUrl={mode === "new" ? "/dashboard/settings/users" : `/dashboard/settings/users/${user?.id ?? ""}`}
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
          onClick: () => onSubmit({ name, email: emailLocked ? undefined : email, employeeId: employeeId || null, companyIds, roleIds, note })
        }
      ]}
    />
  );
}
