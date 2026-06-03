"use client";

import { Download } from "lucide-react";
import { useState } from "react";
import { useTranslation } from "react-i18next";
import { platformToast } from "@/components/platform/ToastProvider";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger
} from "@/components/ui/dropdown-menu";
import { exportService, type ExportRequest } from "@/services/platform.service";

type ExportFormat = ExportRequest["format"];

interface ExportButtonProps {
  endpoint: string;
  filters?: ExportRequest["filters"];
  filename: string;
}

const formats: Array<{ format: ExportFormat; key: string }> = [
  { format: "xlsx", key: "excel" },
  { format: "pdf", key: "pdf" },
  { format: "csv", key: "csv" }
];

export function ExportButton({ endpoint, filters, filename }: ExportButtonProps) {
  const { t } = useTranslation("platform");
  const [activeFormat, setActiveFormat] = useState<ExportFormat | null>(null);

  const runExport = async (format: ExportFormat) => {
    setActiveFormat(format);
    try {
      await exportService.exportFile({ endpoint, format, filters, filename });
      platformToast.success(t("platformBehavior.export.success"));
    } catch {
      platformToast.error(t("platformBehavior.export.failed"));
    } finally {
      setActiveFormat(null);
    }
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button type="button" variant="outline" className="gap-2">
          <Download className="h-4 w-4" aria-hidden="true" />
          {activeFormat ? t("platformBehavior.export.progress") : t("platformBehavior.export.label")}
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        {formats.map((item) => (
          <DropdownMenuItem
            key={item.format}
            disabled={Boolean(activeFormat)}
            onClick={() => void runExport(item.format)}
          >
            {t(`platformBehavior.export.${item.key}`)}
          </DropdownMenuItem>
        ))}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
