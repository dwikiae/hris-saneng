import { SettingsAccessGate } from "@/components/settings/SettingsAccessGate";
import { SettingsNav } from "@/components/settings/SettingsNav";
import { PlatformConfigForm } from "@/components/settings/platform/PlatformConfigForm";

export default function PlatformConfigPage() {
  return (
    <SettingsAccessGate>
      <div className="space-y-6">
        <SettingsNav />
        <PlatformConfigForm />
      </div>
    </SettingsAccessGate>
  );
}
