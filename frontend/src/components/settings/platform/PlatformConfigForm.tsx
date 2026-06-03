"use client";

import { useMutation, useQuery } from "@tanstack/react-query";
import { Eye, EyeOff, Mail, Save } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { useTranslation } from "react-i18next";
import { platformToast } from "@/components/platform/ToastProvider";
import { EmptyState } from "@/components/shared/EmptyState";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { FormPageTemplate, type FormTemplateSection } from "@/components/templates";
import { platformConfigService } from "@/services/platform-config.service";
import type { PlatformConfig, PlatformConfigPayload } from "@/types/settings-platform";

const fallbackTimezones = ["Asia/Jakarta", "Asia/Makassar", "Asia/Jayapura", "UTC"];

const defaultConfig: PlatformConfig = {
  timezone: "Asia/Jakarta",
  defaultLanguage: "id",
  dateFormat: "DD/MM/YYYY",
  sessionDurationHours: 8,
  autoLogoutExpired: true,
  loginLockoutAttempts: 3,
  loginLockoutMinutes: 15,
  smtpEncryption: "tls"
};

export function PlatformConfigForm() {
  const { t } = useTranslation("platform");
  const [form, setForm] = useState<PlatformConfigPayload>(defaultConfig);
  const [isDirty, setIsDirty] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const timezones = useMemo(() => {
    const intlWithTimezones = Intl as unknown as { supportedValuesOf?: (key: "timeZone") => string[] };

    if (typeof Intl !== "undefined" && intlWithTimezones.supportedValuesOf) {
      return intlWithTimezones.supportedValuesOf("timeZone").slice().sort();
    }

    return fallbackTimezones;
  }, []);
  const configQuery = useQuery({
    queryKey: ["settings", "platform-config"],
    queryFn: platformConfigService.get
  });
  const saveMutation = useMutation({
    mutationFn: platformConfigService.update,
    onSuccess: (data) => {
      setForm({ ...defaultConfig, ...data });
      setIsDirty(false);
      platformToast.success(t("settingsPlatform.config.toast.saved"));
    },
    onError: () => platformToast.error(t("settingsPlatform.config.api.notReady"))
  });
  const testSmtpMutation = useMutation({
    mutationFn: platformConfigService.testSmtp,
    onSuccess: () => platformToast.success(t("settingsPlatform.config.toast.testSent")),
    onError: () => platformToast.error(t("settingsPlatform.config.toast.testFailed"))
  });

  useEffect(() => {
    if (configQuery.data) {
      setForm({ ...defaultConfig, ...configQuery.data });
    }
  }, [configQuery.data]);

  if (configQuery.isError) {
    return (
      <EmptyState
        title={t("settingsPlatform.config.api.title")}
        description={t("settingsPlatform.config.api.notReady")}
      />
    );
  }

  const update = (key: keyof PlatformConfigPayload, value: string | number | boolean) => {
    setForm((current) => ({ ...current, [key]: value }));
    setIsDirty(true);
  };

  const field = (key: keyof PlatformConfigPayload, type = "text") => (
    <Input
      id={key}
      type={type}
      value={String(form[key] ?? "")}
      onChange={(event) => update(key, type === "number" ? Number(event.target.value) : event.target.value)}
    />
  );

  const sections: FormTemplateSection[] = [
    {
      id: "localization",
      title: t("settingsPlatform.config.sections.localization"),
      fields: [
        {
          id: "timezone",
          label: t("settingsPlatform.config.fields.timezone"),
          required: true,
          content: (
            <>
              <Input
                id="timezone"
                list="platform-timezones"
                value={form.timezone ?? ""}
                onChange={(event) => update("timezone", event.target.value)}
              />
              <datalist id="platform-timezones">
                {timezones.map((timezone) => (
                  <option key={timezone} value={timezone} />
                ))}
              </datalist>
            </>
          )
        },
        {
          id: "defaultLanguage",
          label: t("settingsPlatform.config.fields.defaultLanguage"),
          content: (
            <select className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" value={form.defaultLanguage} onChange={(event) => update("defaultLanguage", event.target.value)}>
              <option value="id">{t("settingsPlatform.config.options.id")}</option>
              <option value="en">{t("settingsPlatform.config.options.en")}</option>
            </select>
          )
        },
        {
          id: "dateFormat",
          label: t("settingsPlatform.config.fields.dateFormat"),
          content: (
            <select className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" value={form.dateFormat} onChange={(event) => update("dateFormat", event.target.value)}>
              <option value="DD/MM/YYYY">DD/MM/YYYY</option>
              <option value="MM/DD/YYYY">MM/DD/YYYY</option>
              <option value="YYYY-MM-DD">YYYY-MM-DD</option>
            </select>
          )
        }
      ]
    },
    {
      id: "session",
      title: t("settingsPlatform.config.sections.session"),
      fields: [
        { id: "sessionDurationHours", label: t("settingsPlatform.config.fields.sessionDurationHours"), content: field("sessionDurationHours", "number") },
        {
          id: "autoLogoutExpired",
          label: t("settingsPlatform.config.fields.autoLogoutExpired"),
          content: (
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={Boolean(form.autoLogoutExpired)} onChange={(event) => update("autoLogoutExpired", event.target.checked)} />
              {t("settingsPlatform.config.options.enabled")}
            </label>
          )
        },
        { id: "loginLockoutAttempts", label: t("settingsPlatform.config.fields.loginLockoutAttempts"), content: field("loginLockoutAttempts", "number") },
        { id: "loginLockoutMinutes", label: t("settingsPlatform.config.fields.loginLockoutMinutes"), content: field("loginLockoutMinutes", "number") }
      ]
    },
    {
      id: "smtp",
      title: t("settingsPlatform.config.sections.smtp"),
      fields: [
        { id: "smtpHost", label: t("settingsPlatform.config.fields.smtpHost"), required: true, content: field("smtpHost") },
        { id: "smtpPort", label: t("settingsPlatform.config.fields.smtpPort"), required: true, content: field("smtpPort", "number") },
        {
          id: "smtpEncryption",
          label: t("settingsPlatform.config.fields.smtpEncryption"),
          content: (
            <select className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" value={form.smtpEncryption} onChange={(event) => update("smtpEncryption", event.target.value)}>
              <option value="tls">{t("settingsPlatform.config.options.tls")}</option>
              <option value="ssl">{t("settingsPlatform.config.options.ssl")}</option>
              <option value="none">{t("settingsPlatform.config.options.none")}</option>
            </select>
          )
        },
        { id: "smtpUsername", label: t("settingsPlatform.config.fields.smtpUsername"), required: true, content: field("smtpUsername") },
        {
          id: "smtpPassword",
          label: t("settingsPlatform.config.fields.smtpPassword"),
          content: (
            <div className="flex gap-2">
              <Input id="smtpPassword" type={showPassword ? "text" : "password"} value={form.smtpPassword ?? ""} onChange={(event) => update("smtpPassword", event.target.value)} />
              <Button type="button" variant="outline" size="icon" onClick={() => setShowPassword((current) => !current)} aria-label={t("settingsPlatform.config.actions.togglePassword")}>
                {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
              </Button>
            </div>
          )
        },
        { id: "smtpFromName", label: t("settingsPlatform.config.fields.smtpFromName"), required: true, content: field("smtpFromName") },
        { id: "smtpFromEmail", label: t("settingsPlatform.config.fields.smtpFromEmail"), required: true, content: field("smtpFromEmail", "email") },
        {
          id: "testEmail",
          label: t("settingsPlatform.config.fields.testEmail"),
          span: 2,
          content: (
            <Button type="button" variant="outline" disabled={testSmtpMutation.isPending} onClick={() => testSmtpMutation.mutate()}>
              <Mail className="mr-2 h-4 w-4" />
              {t("settingsPlatform.config.actions.testEmail")}
            </Button>
          )
        }
      ]
    }
  ];

  return (
    <FormPageTemplate
      title={t("settingsPlatform.config.title")}
      description={t("settingsPlatform.config.description")}
      backUrl="/dashboard/settings"
      backLabel={t("settingsPlatform.actions.back")}
      sections={sections}
      isDirty={isDirty}
      isSubmitting={saveMutation.isPending || configQuery.isLoading}
      footerActions={[
        {
          id: "save",
          label: t("settingsPlatform.actions.save"),
          icon: <Save className="h-4 w-4" />,
          variant: "primary",
          onClick: () => saveMutation.mutate(form)
        }
      ]}
    />
  );
}
