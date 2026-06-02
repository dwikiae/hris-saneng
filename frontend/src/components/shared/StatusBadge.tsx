import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";

type StatusBadgeTone = "success" | "warning" | "danger" | "neutral" | "info";

interface StatusBadgeProps {
  label: string;
  tone?: StatusBadgeTone;
}

const toneClassNames: Record<StatusBadgeTone, string> = {
  success: "border-transparent bg-success text-success-foreground",
  warning: "border-transparent bg-warning text-warning-foreground",
  danger: "border-transparent bg-destructive text-destructive-foreground",
  neutral: "border-border bg-secondary text-secondary-foreground",
  info: "border-transparent bg-blue-100 text-blue-700"
};

export function StatusBadge({ label, tone = "neutral" }: StatusBadgeProps) {
  return (
    <Badge variant="outline" className={cn("font-medium", toneClassNames[tone])}>
      {label}
    </Badge>
  );
}
