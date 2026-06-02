"use client";

import { useTranslation } from "react-i18next";
import { PlatformLayout } from "@/components/core/platform/PlatformLayout";

interface InfoPanelProps {
  titleKey: string;
  descriptionKey: string;
  bodyKey: string;
}

export function InfoPanel({ titleKey, descriptionKey, bodyKey }: InfoPanelProps) {
  const { t } = useTranslation("platform");

  return (
    <PlatformLayout titleKey={titleKey} descriptionKey={descriptionKey}>
      <article className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <p className="max-w-3xl text-sm leading-7 text-slate-600">{t(bodyKey)}</p>
      </article>
    </PlatformLayout>
  );
}
