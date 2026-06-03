"use client";

import { Save } from "lucide-react";
import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { FormPageTemplate, type FormTemplateSection } from "@/components/templates";
import { Input } from "@/components/ui/input";
import type { CompanyModuleSummary, CompanyPayload, PlatformCompany } from "@/types/company";

interface CompanyFormProps {
  mode: "new" | "edit";
  company?: PlatformCompany;
  modules?: CompanyModuleSummary[];
  modulesUnavailable: boolean;
  isSubmitting: boolean;
  onSubmit: (payload: CompanyPayload) => void;
}

type FormState = Record<string, string>;

const emptyState: FormState = {
  name: "",
  legalName: "",
  tagline: "",
  companyType: "",
  industry: "",
  foundedDate: "",
  address: "",
  city: "",
  province: "",
  postalCode: "",
  phone: "",
  email: "",
  website: "",
  hrPicName: "",
  hrPicPhone: "",
  hrPicEmail: "",
  npwp: "",
  nibOrSiup: "",
  bpjsKetenagakerjaan: "",
  bpjsKesehatan: "",
  wlkpNumber: "",
  directorName: "",
  timezone: "Asia/Jakarta",
  dateFormat: "DD/MM/YYYY",
  languageDefault: "id",
  smtpHost: "",
  smtpPort: "",
  smtpUsername: "",
  smtpPasswordMasked: "",
  smtpFromName: "",
  smtpFromEmail: "",
  loginLockoutAttempts: "3",
  loginLockoutMinutes: "15",
  sessionDurationHours: "8"
};

function stateFromCompany(company?: PlatformCompany): FormState {
  if (!company) {
    return emptyState;
  }

  return Object.fromEntries(
    Object.keys(emptyState).map((key) => [key, String(company[key as keyof PlatformCompany] ?? emptyState[key])])
  );
}

function nullable(value: string): string | null {
  return value.trim() === "" ? null : value;
}

function numberOrNull(value: string): number | null {
  return value.trim() === "" ? null : Number(value);
}

export function CompanyForm({
  mode,
  company,
  modules = [],
  modulesUnavailable,
  isSubmitting,
  onSubmit
}: CompanyFormProps) {
  const { t } = useTranslation("platform");
  const [state, setState] = useState<FormState>(() => stateFromCompany(company));
  const [activeModuleCodes, setActiveModuleCodes] = useState<string[]>(
    () => company?.activeModules?.filter((module) => module.isActive).map((module) => module.code) ?? []
  );
  const [isDirty, setIsDirty] = useState(mode === "new");

  useEffect(() => {
    setState(stateFromCompany(company));
    setActiveModuleCodes(company?.activeModules?.filter((module) => module.isActive).map((module) => module.code) ?? []);
    setIsDirty(mode === "new");
  }, [company, mode]);

  const update = (key: string, value: string) => {
    setState((current) => ({ ...current, [key]: value }));
    setIsDirty(true);
  };
  const field = (key: string, type = "text") => (
    <Input id={key} type={type} value={state[key]} onChange={(event) => update(key, event.target.value)} />
  );
  const select = (key: string, options: Array<{ value: string; label: string }>) => (
    <select
      id={key}
      value={state[key]}
      className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
      onChange={(event) => update(key, event.target.value)}
    >
      {options.map((option) => (
        <option key={option.value} value={option.value}>
          {option.label}
        </option>
      ))}
    </select>
  );
  const toggleModule = (code: string) => {
    setActiveModuleCodes((current) =>
      current.includes(code) ? current.filter((item) => item !== code) : [...current, code]
    );
    setIsDirty(true);
  };

  const submit = () => {
    onSubmit({
      ...state,
      languageDefault: state.languageDefault as "id" | "en",
      loginLockoutAttempts: numberOrNull(state.loginLockoutAttempts),
      loginLockoutMinutes: numberOrNull(state.loginLockoutMinutes),
      sessionDurationHours: numberOrNull(state.sessionDurationHours),
      smtpPort: nullable(state.smtpPort),
      activeModuleCodes
    });
  };

  const sections: FormTemplateSection[] = [
    {
      id: "identity",
      title: t("settingsCompanies.form.sections.identity"),
      fields: [
        { id: "name", label: t("settingsCompanies.fields.name"), content: field("name"), required: true },
        { id: "legalName", label: t("settingsCompanies.fields.legalName"), content: field("legalName"), required: true },
        { id: "logo", label: t("settingsCompanies.fields.logo"), content: <Input id="logo" type="file" disabled /> },
        { id: "companyType", label: t("settingsCompanies.fields.companyType"), content: field("companyType") },
        { id: "tagline", label: t("settingsCompanies.fields.tagline"), content: field("tagline"), span: 2 },
        { id: "industry", label: t("settingsCompanies.fields.industry"), content: field("industry") },
        { id: "foundedDate", label: t("settingsCompanies.fields.foundedDate"), content: field("foundedDate", "date") }
      ]
    },
    {
      id: "contact",
      title: t("settingsCompanies.form.sections.contact"),
      fields: [
        { id: "address", label: t("settingsCompanies.fields.address"), content: field("address"), required: true, span: 2 },
        { id: "city", label: t("settingsCompanies.fields.city"), content: field("city"), required: true },
        { id: "province", label: t("settingsCompanies.fields.province"), content: field("province"), required: true },
        { id: "postalCode", label: t("settingsCompanies.fields.postalCode"), content: field("postalCode") },
        { id: "phone", label: t("settingsCompanies.fields.phone"), content: field("phone") },
        { id: "email", label: t("settingsCompanies.fields.email"), content: field("email", "email") },
        { id: "website", label: t("settingsCompanies.fields.website"), content: field("website") },
        { id: "hrPicName", label: t("settingsCompanies.fields.hrPicName"), content: field("hrPicName") },
        { id: "hrPicPhone", label: t("settingsCompanies.fields.hrPicPhone"), content: field("hrPicPhone") },
        { id: "hrPicEmail", label: t("settingsCompanies.fields.hrPicEmail"), content: field("hrPicEmail", "email") }
      ]
    },
    {
      id: "legal",
      title: t("settingsCompanies.form.sections.legal"),
      fields: [
        { id: "npwp", label: t("settingsCompanies.fields.npwp"), content: field("npwp") },
        { id: "nibOrSiup", label: t("settingsCompanies.fields.nibOrSiup"), content: field("nibOrSiup") },
        { id: "bpjsKetenagakerjaan", label: t("settingsCompanies.fields.bpjsKetenagakerjaan"), content: field("bpjsKetenagakerjaan") },
        { id: "bpjsKesehatan", label: t("settingsCompanies.fields.bpjsKesehatan"), content: field("bpjsKesehatan") },
        { id: "wlkpNumber", label: t("settingsCompanies.fields.wlkpNumber"), content: field("wlkpNumber") },
        { id: "directorName", label: t("settingsCompanies.fields.directorName"), content: field("directorName") }
      ]
    },
    {
      id: "operations",
      title: t("settingsCompanies.form.sections.operations"),
      fields: [
        { id: "timezone", label: t("settingsCompanies.fields.timezone"), content: field("timezone"), required: true },
        { id: "dateFormat", label: t("settingsCompanies.fields.dateFormat"), content: field("dateFormat") },
        {
          id: "languageDefault",
          label: t("settingsCompanies.fields.languageDefault"),
          content: select("languageDefault", [
            { value: "id", label: "ID" },
            { value: "en", label: "EN" }
          ])
        },
        { id: "sessionDurationHours", label: t("settingsCompanies.fields.sessionDurationHours"), content: field("sessionDurationHours", "number") },
        { id: "smtpHost", label: t("settingsCompanies.fields.smtpHost"), content: field("smtpHost") },
        { id: "smtpPort", label: t("settingsCompanies.fields.smtpPort"), content: field("smtpPort", "number") },
        { id: "smtpUsername", label: t("settingsCompanies.fields.smtpUsername"), content: field("smtpUsername") },
        { id: "smtpPasswordMasked", label: t("settingsCompanies.fields.smtpPassword"), content: field("smtpPasswordMasked", "password") },
        { id: "smtpFromName", label: t("settingsCompanies.fields.smtpFromName"), content: field("smtpFromName") },
        { id: "smtpFromEmail", label: t("settingsCompanies.fields.smtpFromEmail"), content: field("smtpFromEmail", "email") },
        { id: "loginLockoutAttempts", label: t("settingsCompanies.fields.loginLockoutAttempts"), content: field("loginLockoutAttempts", "number") },
        { id: "loginLockoutMinutes", label: t("settingsCompanies.fields.loginLockoutMinutes"), content: field("loginLockoutMinutes", "number") }
      ]
    },
    {
      id: "modules",
      title: t("settingsCompanies.form.sections.modules"),
      description: modulesUnavailable ? t("settingsCompanies.api.modulesNotReady") : undefined,
      fields: [
        {
          id: "activeModules",
          label: t("settingsCompanies.fields.activeModules"),
          span: 2,
          content: (
            <div className="grid gap-2 sm:grid-cols-2">
              {modules.map((module) => (
                <label key={module.code} className="flex items-center gap-2 rounded-md border border-border p-3 text-sm">
                  <input
                    type="checkbox"
                    disabled={modulesUnavailable}
                    checked={activeModuleCodes.includes(module.code)}
                    onChange={() => toggleModule(module.code)}
                  />
                  <span>{module.name}</span>
                </label>
              ))}
              {modules.length === 0 ? <p className="text-sm text-muted-foreground">{t("settingsCompanies.api.modulesNotReady")}</p> : null}
            </div>
          )
        }
      ]
    }
  ];

  return (
    <FormPageTemplate
      title={mode === "new" ? t("settingsCompanies.new.title") : t("settingsCompanies.edit.title")}
      description={mode === "new" ? t("settingsCompanies.new.description") : t("settingsCompanies.edit.description")}
      backUrl={mode === "new" ? "/dashboard/settings/companies" : `/dashboard/settings/companies/${company?.id ?? ""}`}
      backLabel={t("settingsCompanies.actions.back")}
      sections={sections}
      isDirty={isDirty}
      isSubmitting={isSubmitting}
      footerActions={[
        { id: "save", label: t("settingsCompanies.actions.save"), icon: <Save className="h-4 w-4" />, variant: "primary", onClick: submit }
      ]}
    />
  );
}
