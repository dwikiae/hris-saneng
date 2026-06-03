import { SettingsAccessGate } from "@/components/settings/SettingsAccessGate";
import { AuditLogPage } from "@/components/settings/platform/AuditLogPage";

export default function SettingsAuditPage() {
  return (
    <SettingsAccessGate>
      <AuditLogPage />
    </SettingsAccessGate>
  );
}
