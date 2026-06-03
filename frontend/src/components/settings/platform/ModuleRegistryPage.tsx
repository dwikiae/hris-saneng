"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Box, PackageCheck, Puzzle, ShieldAlert } from "lucide-react";
import { useState } from "react";
import { useTranslation } from "react-i18next";
import { ConfirmDialog } from "@/components/platform/ConfirmDialog";
import { platformToast } from "@/components/platform/ToastProvider";
import { SettingsNav } from "@/components/settings/SettingsNav";
import { EmptyState } from "@/components/shared/EmptyState";
import { LoadingSkeleton } from "@/components/shared/LoadingSkeleton";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { moduleRegistryService } from "@/services/module-registry.service";
import type { ModuleRegistryItem } from "@/types/settings-platform";

export function ModuleRegistryPage() {
  const { t } = useTranslation("platform");
  const queryClient = useQueryClient();
  const [installTarget, setInstallTarget] = useState<ModuleRegistryItem | null>(null);
  const [uninstallTarget, setUninstallTarget] = useState<ModuleRegistryItem | null>(null);
  const [finalUninstallTarget, setFinalUninstallTarget] = useState<ModuleRegistryItem | null>(null);
  const [confirmationText, setConfirmationText] = useState("");
  const query = useQuery({ queryKey: ["settings", "modules"], queryFn: moduleRegistryService.list });
  const modules = query.data ?? [];
  const installedCodes = modules.filter((module) => isInstalled(module)).map((module) => module.code);
  const installMutation = useMutation({
    mutationFn: (code: string) => moduleRegistryService.install(code),
    onSuccess: () => {
      platformToast.success(t("settingsPlatform.modules.toast.installed"));
      void queryClient.invalidateQueries({ queryKey: ["settings", "modules"] });
    },
    onError: () => platformToast.error(t("settingsPlatform.modules.api.notReady"))
  });
  const exportMutation = useMutation({
    mutationFn: (module: ModuleRegistryItem) => moduleRegistryService.exportData(module.code),
    onSuccess: (_data, module) => {
      setFinalUninstallTarget(module);
      platformToast.success(t("settingsPlatform.modules.toast.exported"));
    },
    onError: () => platformToast.error(t("settingsPlatform.modules.api.notReady"))
  });
  const uninstallMutation = useMutation({
    mutationFn: (code: string) => moduleRegistryService.uninstall(code),
    onSuccess: () => {
      platformToast.success(t("settingsPlatform.modules.toast.uninstalled"));
      void queryClient.invalidateQueries({ queryKey: ["settings", "modules"] });
    },
    onError: () => platformToast.error(t("settingsPlatform.modules.api.notReady"))
  });

  const beginInstall = (module: ModuleRegistryItem) => {
    const missing = missingDependencies(module, installedCodes);

    if (missing.length > 0) {
      platformToast.warning(t("settingsPlatform.modules.toast.dependenciesMissing", { dependencies: missing.join(", ") }));
      return;
    }

    setInstallTarget(module);
  };

  return (
    <div className="space-y-6">
      <SettingsNav />
      <div>
        <h1 className="text-2xl font-semibold text-foreground">{t("settingsPlatform.modules.title")}</h1>
        <p className="mt-1 max-w-3xl text-sm leading-6 text-muted-foreground">{t("settingsPlatform.modules.description")}</p>
      </div>
      {query.isLoading ? <LoadingSkeleton rows={6} itemClassName="h-40" /> : null}
      {!query.isLoading && query.isError ? (
        <EmptyState title={t("settingsPlatform.modules.api.title")} description={t("settingsPlatform.modules.api.notReady")} />
      ) : null}
      {!query.isLoading && !query.isError && modules.length === 0 ? (
        <EmptyState title={t("settingsPlatform.modules.empty.title")} description={t("settingsPlatform.modules.empty.description")} />
      ) : null}
      {!query.isLoading && !query.isError && modules.length > 0 ? (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          {modules.map((module) => (
            <ModuleCard
              key={module.code}
              module={module}
              installedCodes={installedCodes}
              onInstall={() => beginInstall(module)}
              onUninstall={() => {
                setConfirmationText("");
                setUninstallTarget(module);
              }}
            />
          ))}
        </div>
      ) : null}
      <ConfirmDialog
        open={Boolean(installTarget)}
        onOpenChange={(open) => !open && setInstallTarget(null)}
        title={t("settingsPlatform.modules.install.title")}
        description={t("settingsPlatform.modules.install.description", { module: installTarget?.name ?? "" })}
        confirmLabel={t("settingsPlatform.modules.actions.install")}
        confirmVariant="warning"
        onConfirm={() => {
          if (installTarget) {
            platformToast.info(t("settingsPlatform.modules.toast.installing"));
            installMutation.mutate(installTarget.code);
          }
        }}
      />
      <ConfirmDialog
        open={Boolean(uninstallTarget)}
        onOpenChange={(open) => !open && setUninstallTarget(null)}
        title={t("settingsPlatform.modules.uninstall.title")}
        description={t("settingsPlatform.modules.uninstall.description", { module: uninstallTarget?.name ?? "" })}
        confirmLabel={t("settingsPlatform.modules.actions.exportAndContinue")}
        confirmVariant="danger"
        confirmationLabel={t("settingsPlatform.modules.uninstall.confirmLabel", { module: uninstallTarget?.name ?? "" })}
        confirmationExpected={uninstallTarget?.name}
        confirmationValue={confirmationText}
        onConfirmationChange={setConfirmationText}
        onConfirm={() => {
          if (uninstallTarget) {
            platformToast.info(t("settingsPlatform.modules.toast.exporting"));
            exportMutation.mutate(uninstallTarget);
          }
        }}
      />
      <ConfirmDialog
        open={Boolean(finalUninstallTarget)}
        onOpenChange={(open) => !open && setFinalUninstallTarget(null)}
        title={t("settingsPlatform.modules.uninstall.finalTitle")}
        description={t("settingsPlatform.modules.uninstall.finalDescription", { module: finalUninstallTarget?.name ?? "" })}
        confirmLabel={t("settingsPlatform.modules.actions.uninstall")}
        confirmVariant="danger"
        onConfirm={() => {
          if (finalUninstallTarget) {
            uninstallMutation.mutate(finalUninstallTarget.code);
          }
        }}
      />
    </div>
  );
}

function ModuleCard({
  module,
  installedCodes,
  onInstall,
  onUninstall
}: {
  module: ModuleRegistryItem;
  installedCodes: string[];
  onInstall: () => void;
  onUninstall: () => void;
}) {
  const { t } = useTranslation("platform");
  const installed = isInstalled(module);
  const missing = missingDependencies(module, installedCodes);

  return (
    <article className="flex min-h-64 flex-col rounded-lg border border-border bg-card p-5 shadow-sm">
      <div className="flex items-start gap-3">
        <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
          <Puzzle className="h-5 w-5" />
        </div>
        <div className="min-w-0 flex-1">
          <div className="flex flex-wrap items-center gap-2">
            <h2 className="text-base font-semibold text-foreground">{module.name}</h2>
            <StatusBadge
              label={installed ? t("settingsPlatform.modules.status.installed") : t("settingsPlatform.modules.status.notInstalled")}
              tone={installed ? "success" : "neutral"}
            />
          </div>
          <p className="mt-1 text-xs text-muted-foreground">{t("settingsPlatform.modules.version", { version: module.version ?? "-" })}</p>
        </div>
      </div>
      <p className="mt-4 flex-1 text-sm leading-6 text-muted-foreground">{module.description ?? t("settingsPlatform.modules.empty.descriptionMissing")}</p>
      <div className="mt-4 space-y-2">
        <p className="text-xs font-medium uppercase text-muted-foreground">{t("settingsPlatform.modules.dependencies")}</p>
        <div className="flex flex-wrap gap-1">
          {(module.dependencies ?? []).length > 0 ? (
            module.dependencies?.map((dependency) => (
              <Badge key={dependency} variant={missing.includes(dependency) ? "destructive" : "outline"}>
                {dependency}
              </Badge>
            ))
          ) : (
            <Badge variant="outline">{t("settingsPlatform.modules.noDependencies")}</Badge>
          )}
        </div>
      </div>
      <div className="mt-5 flex flex-wrap gap-2">
        {module.isMandatory ? (
          <Button type="button" variant="outline" disabled>
            <ShieldAlert className="mr-2 h-4 w-4" />
            {t("settingsPlatform.modules.actions.mandatory")}
          </Button>
        ) : installed ? (
          <>
            <Button type="button" variant="outline" disabled>
              <PackageCheck className="mr-2 h-4 w-4" />
              {t("settingsPlatform.modules.actions.disable")}
            </Button>
            <Button type="button" variant="destructive" onClick={onUninstall}>
              {t("settingsPlatform.modules.actions.uninstall")}
            </Button>
          </>
        ) : (
          <Button type="button" onClick={onInstall}>
            <Box className="mr-2 h-4 w-4" />
            {t("settingsPlatform.modules.actions.install")}
          </Button>
        )}
      </div>
    </article>
  );
}

function isInstalled(module: ModuleRegistryItem): boolean {
  return module.isInstalled ?? module.status === "installed";
}

function missingDependencies(module: ModuleRegistryItem, installedCodes: string[]): string[] {
  if (module.missingDependencies) {
    return module.missingDependencies;
  }

  return (module.dependencies ?? []).filter((dependency) => !installedCodes.includes(dependency));
}
