import { AlertCircle } from "lucide-react";
import { Button } from "@/components/ui/button";

interface DashboardErrorStateProps {
  title: string;
  description: string;
  retryLabel?: string;
  onRetry?: () => void;
}

export function DashboardErrorState({
  title,
  description,
  retryLabel,
  onRetry
}: DashboardErrorStateProps) {
  return (
    <div className="rounded-lg border border-red-200 bg-red-50 p-5 text-red-700" role="alert">
      <div className="flex items-start gap-3">
        <AlertCircle className="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
        <div className="min-w-0">
          <h2 className="text-base font-semibold text-red-800">{title}</h2>
          <p className="mt-1 text-sm leading-6">{description}</p>
          {retryLabel && onRetry ? (
            <Button
              type="button"
              variant="outline"
              size="sm"
              className="mt-4 border-red-200 bg-white text-red-700 hover:bg-red-100 hover:text-red-800"
              onClick={onRetry}
            >
              {retryLabel}
            </Button>
          ) : null}
        </div>
      </div>
    </div>
  );
}
