import type { ReactNode } from "react";
import { cn } from "@/lib/utils";

interface StatCardProps {
  label: string;
  value: string;
  helper?: string;
  icon?: ReactNode;
  className?: string;
}

export function StatCard({ label, value, helper, icon, className }: StatCardProps) {
  return (
    <article className={cn("rounded-lg border border-border bg-card p-5 shadow-sm", className)}>
      <div className="flex items-start justify-between gap-3">
        <p className="text-sm font-medium text-muted-foreground">{label}</p>
        {icon ? <div className="text-primary">{icon}</div> : null}
      </div>
      <p className="mt-3 text-3xl font-semibold leading-none text-foreground">{value}</p>
      {helper ? <p className="mt-2 text-sm leading-6 text-muted-foreground">{helper}</p> : null}
    </article>
  );
}
