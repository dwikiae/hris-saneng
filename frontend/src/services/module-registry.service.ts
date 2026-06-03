import { requestPrivateJson } from "@/services/api-client";
import type { ModuleRegistryItem } from "@/types/settings-platform";

export const moduleRegistryService = {
  list(): Promise<ModuleRegistryItem[]> {
    return requestPrivateJson<ModuleRegistryItem[]>("/instance/modules");
  },
  install(code: string): Promise<ModuleRegistryItem> {
    return requestPrivateJson<ModuleRegistryItem>(`/instance/modules/${code}/install`, { method: "POST" });
  },
  exportData(code: string): Promise<{ export_id?: string | number } | null> {
    return requestPrivateJson<{ export_id?: string | number } | null>(`/instance/modules/${code}/export-data`, {
      method: "POST"
    });
  },
  uninstall(code: string): Promise<null> {
    return requestPrivateJson<null>(`/instance/modules/${code}/uninstall`, { method: "POST" });
  }
};
