"use client";

import { ChevronDown, ChevronRight, Save } from "lucide-react";
import { useState } from "react";
import { useTranslation } from "react-i18next";
import { Button } from "@/components/ui/button";
import type { PermissionModuleNode } from "@/types/access";

interface PermissionMatrixProps {
  modules: PermissionModuleNode[];
  selectedPermissionIds: Array<string | number>;
  isSaving: boolean;
  onSave: (permissionIds: Array<string | number>) => void;
}

export function PermissionMatrix({ modules, selectedPermissionIds, isSaving, onSave }: PermissionMatrixProps) {
  const { t } = useTranslation("platform");
  const [openModules, setOpenModules] = useState<string[]>(() => modules.map((module) => module.id));
  const [selected, setSelected] = useState<string[]>(() => selectedPermissionIds.map(String));

  const toggleModule = (moduleId: string) => {
    setOpenModules((current) => current.includes(moduleId) ? current.filter((item) => item !== moduleId) : [...current, moduleId]);
  };
  const togglePermission = (permissionId: string) => {
    setSelected((current) => current.includes(permissionId) ? current.filter((item) => item !== permissionId) : [...current, permissionId]);
  };
  const toggleAll = (module: PermissionModuleNode) => {
    const ids = module.menus.flatMap((menu) => menu.actions.map((action) => String(action.id)));
    const allSelected = ids.every((id) => selected.includes(id));
    setSelected((current) => allSelected ? current.filter((id) => !ids.includes(id)) : Array.from(new Set([...current, ...ids])));
  };

  return (
    <div className="space-y-4">
      <div className="flex justify-end">
        <Button type="button" disabled={isSaving} onClick={() => onSave(selected)}>
          <Save className="mr-2 h-4 w-4" aria-hidden="true" />
          {t("settingsAccess.actions.savePermissions")}
        </Button>
      </div>
      <div className="divide-y rounded-lg border border-border bg-card">
        {modules.map((module) => {
          const isOpen = openModules.includes(module.id);

          return (
            <section key={module.id} className="p-4">
              <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <button type="button" className="flex items-center gap-2 text-sm font-semibold text-foreground" onClick={() => toggleModule(module.id)}>
                  {isOpen ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                  {module.label}
                </button>
                <Button type="button" variant="outline" size="sm" onClick={() => toggleAll(module)}>
                  {t("settingsAccess.permissions.checkAll")}
                </Button>
              </div>
              {isOpen ? (
                <div className="mt-4 space-y-4">
                  {module.menus.map((menu) => (
                    <div key={menu.id} className="rounded-md bg-slate-50 p-3">
                      <p className="text-sm font-medium text-foreground">{menu.label}</p>
                      <div className="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        {menu.actions.map((action) => (
                          <label key={action.id} className="flex items-center gap-2 text-sm text-muted-foreground">
                            <input
                              type="checkbox"
                              checked={selected.includes(String(action.id))}
                              onChange={() => togglePermission(String(action.id))}
                            />
                            <span>{action.label}</span>
                          </label>
                        ))}
                      </div>
                    </div>
                  ))}
                </div>
              ) : null}
            </section>
          );
        })}
      </div>
    </div>
  );
}
