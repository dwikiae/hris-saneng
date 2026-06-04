"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Save } from "lucide-react";
import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { platformToast } from "@/components/platform/ToastProvider";
import { EmptyState } from "@/components/shared/EmptyState";
import { LoadingSkeleton } from "@/components/shared/LoadingSkeleton";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { employeeSettingsService } from "@/services/employee-settings.service";
import type { EmployeeModuleSettings } from "@/types/employee-settings";

const defaultSettings: EmployeeModuleSettings = {
  employee_number_format: "EMP-{YYYY}-{SEQ}",
  probation_days: 90,
  contract_expiry_notify_days: 30,
  pkwt_max_months: 24
};

export function EmployeeSettingsForm({ company }: { company: string }) {
  const { t } = useTranslation("platform");
  const queryClient = useQueryClient();
  const query = useQuery({
    queryKey: ["employees", "settings", company, "configuration"],
    queryFn: () => employeeSettingsService.get(company)
  });
  const [state, setState] = useState<EmployeeModuleSettings>(defaultSettings);
  const [isDirty, setIsDirty] = useState(false);

  useEffect(() => {
    if (query.data) {
      setState({
        ...defaultSettings,
        ...query.data,
        pkwt_max_months: Number(query.data.pkwt_max_months ?? defaultSettings.pkwt_max_months)
      });
      setIsDirty(false);
    }
  }, [query.data]);

  const mutation = useMutation({
    mutationFn: () => employeeSettingsService.update(company, state),
    onSuccess: (data) => {
      queryClient.setQueryData(["employees", "settings", company, "configuration"], data);
      setIsDirty(false);
      platformToast.success(t("employeesSettings.toast.settingsSaved"));
    },
    onError: (error) => platformToast.error(error instanceof Error ? error.message : t("employeesSettings.toast.failed"))
  });

  const update = (key: keyof EmployeeModuleSettings, value: string) => {
    setState((current) => ({
      ...current,
      [key]: key === "employee_number_format" ? value : Number(value)
    }));
    setIsDirty(true);
  };

  if (query.isLoading) {
    return <LoadingSkeleton rows={4} itemClassName="h-20" />;
  }

  if (query.isError) {
    return (
      <EmptyState
        title={t("employeesSettings.api.settingsNotReadyTitle")}
        description={t("employeesSettings.api.settingsNotReadyDescription")}
      />
    );
  }

  return (
    <section className="rounded-lg border border-border bg-card p-5 shadow-sm">
      <div className="mb-5">
        <h2 className="text-xl font-semibold text-foreground">{t("employeesSettings.sections.pengaturan")}</h2>
        <p className="mt-1 text-sm leading-6 text-muted-foreground">{t("employeesSettings.settings.description")}</p>
      </div>

      <div className="grid gap-5 lg:grid-cols-2">
        <div className="space-y-2 lg:col-span-2">
          <label className="text-sm font-medium text-foreground" htmlFor="employee-number-format">
            {t("employeesSettings.settings.employeeNumberFormat")}
          </label>
          <Input
            id="employee-number-format"
            value={state.employee_number_format}
            placeholder="EMP-{YYYY}-{SEQ}"
            onChange={(event) => update("employee_number_format", event.target.value)}
          />
          <p className="text-xs text-muted-foreground">{t("employeesSettings.settings.formatHelper")}</p>
          <p className="rounded-md border border-border bg-slate-50 px-3 py-2 text-sm text-foreground">
            {t("employeesSettings.settings.preview")}: {previewEmployeeNumber(state.employee_number_format)}
          </p>
        </div>
        <NumberField
          id="probation-days"
          label={t("employeesSettings.settings.probationDays")}
          suffix={t("employeesSettings.settings.days")}
          value={state.probation_days}
          onChange={(value) => update("probation_days", value)}
        />
        <NumberField
          id="contract-notify-days"
          label={t("employeesSettings.settings.contractExpiryNotifyDays")}
          suffix={t("employeesSettings.settings.daysBefore")}
          value={state.contract_expiry_notify_days}
          onChange={(value) => update("contract_expiry_notify_days", value)}
        />
        <NumberField
          id="pkwt-months"
          label={t("employeesSettings.settings.pkwtMaxMonths")}
          suffix={t("employeesSettings.settings.months")}
          value={state.pkwt_max_months}
          onChange={(value) => update("pkwt_max_months", value)}
        />
      </div>

      <div className="mt-6 flex justify-end border-t border-border pt-4">
        <Button type="button" disabled={!isDirty || mutation.isPending} onClick={() => mutation.mutate()}>
          <Save className="h-4 w-4" />
          {t("employeesSettings.actions.save")}
        </Button>
      </div>
    </section>
  );
}

function NumberField({
  id,
  label,
  suffix,
  value,
  onChange
}: {
  id: string;
  label: string;
  suffix: string;
  value: number;
  onChange: (value: string) => void;
}) {
  return (
    <div className="space-y-2">
      <label className="text-sm font-medium text-foreground" htmlFor={id}>
        {label}
      </label>
      <div className="flex overflow-hidden rounded-md border border-input bg-background">
        <Input id={id} type="number" min={0} value={value} className="border-0 shadow-none" onChange={(event) => onChange(event.target.value)} />
        <span className="flex items-center border-l border-border px-3 text-sm text-muted-foreground">{suffix}</span>
      </div>
    </div>
  );
}

function previewEmployeeNumber(format: string): string {
  const year = String(new Date().getFullYear());

  return format
    .replaceAll("{YYYY}", year)
    .replaceAll("{SEQ4}", "0001")
    .replaceAll("{SEQ}", "001");
}
