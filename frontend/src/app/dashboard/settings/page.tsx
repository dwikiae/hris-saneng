import { InfoPanel } from "@/components/core/platform/InfoPanel";

export default function SettingsPage() {
  return (
    <InfoPanel
      titleKey="settings.title"
      descriptionKey="settings.description"
      bodyKey="settings.body"
    />
  );
}
