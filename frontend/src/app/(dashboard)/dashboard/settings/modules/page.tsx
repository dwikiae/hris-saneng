import { SettingsAccessGate } from "@/components/settings/SettingsAccessGate";
import { ModuleRegistryPage } from "@/components/settings/platform/ModuleRegistryPage";

export default function SettingsModulesPage() {
  return (
    <SettingsAccessGate>
      <ModuleRegistryPage />
    </SettingsAccessGate>
  );
}
