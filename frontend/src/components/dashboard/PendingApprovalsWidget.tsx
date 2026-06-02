import { CheckCircle2 } from "lucide-react";
import { DashboardErrorState } from "@/components/dashboard/DashboardErrorState";
import { Button } from "@/components/ui/button";
import { LoadingSkeleton } from "@/components/shared/LoadingSkeleton";
import type { DashboardApprovalItem } from "@/types/dashboard";

interface PendingApprovalsWidgetProps {
  approveLabel: string;
  emptyText: string;
  errorDescription: string;
  errorTitle: string;
  isApproving: boolean;
  isError: boolean;
  isLoading: boolean;
  items?: DashboardApprovalItem[];
  onApproveEmployee: (employeeId: number) => void;
  title: string;
}

function employeeApprovalId(item: DashboardApprovalItem): number | null {
  return item.type === "employee" && typeof item.resource_id === "number" ? item.resource_id : null;
}

export function PendingApprovalsWidget({
  approveLabel,
  emptyText,
  errorDescription,
  errorTitle,
  isApproving,
  isError,
  isLoading,
  items,
  onApproveEmployee,
  title
}: PendingApprovalsWidgetProps) {
  if (isLoading) {
    return (
      <section className="rounded-lg border border-border bg-card p-5 shadow-sm">
        <h2>{title}</h2>
        <LoadingSkeleton rows={3} className="mt-4" />
      </section>
    );
  }

  if (isError) {
    return <DashboardErrorState title={errorTitle} description={errorDescription} />;
  }

  return (
    <section className="rounded-lg border border-border bg-card p-5 shadow-sm">
      <div className="flex items-center justify-between gap-3">
        <h2>{title}</h2>
        <CheckCircle2 className="h-5 w-5 text-success" aria-hidden="true" />
      </div>
      {items && items.length > 0 ? (
        <div className="mt-4 space-y-3">
          {items.map((item) => {
            const employeeId = employeeApprovalId(item);

            return (
              <article key={item.id} className="rounded-md border border-border p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                  <div>
                    <h3>{item.title}</h3>
                    {item.description ? (
                      <p className="mt-1 text-sm leading-6 text-muted-foreground">
                        {item.description}
                      </p>
                    ) : null}
                    {item.requested_by ? (
                      <p className="mt-2 text-xs font-medium text-muted-foreground">
                        {item.requested_by}
                      </p>
                    ) : null}
                  </div>
                  {employeeId ? (
                    <Button
                      type="button"
                      size="sm"
                      disabled={isApproving}
                      onClick={() => onApproveEmployee(employeeId)}
                    >
                      {approveLabel}
                    </Button>
                  ) : null}
                </div>
              </article>
            );
          })}
        </div>
      ) : (
        <p className="mt-4 text-sm leading-6 text-muted-foreground">{emptyText}</p>
      )}
    </section>
  );
}
