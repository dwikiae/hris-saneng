import { Clock3 } from "lucide-react";
import { DashboardErrorState } from "@/components/dashboard/DashboardErrorState";
import { EmptyState } from "@/components/shared/EmptyState";
import { LoadingSkeleton } from "@/components/shared/LoadingSkeleton";
import type { DashboardActivity } from "@/types/dashboard";

interface RecentActivityListProps {
  emptyDescription: string;
  emptyTitle: string;
  errorDescription: string;
  errorTitle: string;
  isError: boolean;
  isLoading: boolean;
  items?: DashboardActivity[];
  title: string;
}

export function RecentActivityList({
  emptyDescription,
  emptyTitle,
  errorDescription,
  errorTitle,
  isError,
  isLoading,
  items,
  title
}: RecentActivityListProps) {
  if (isLoading) {
    return (
      <section className="rounded-lg border border-border bg-card p-5 shadow-sm">
        <h2>{title}</h2>
        <LoadingSkeleton rows={5} className="mt-4" />
      </section>
    );
  }

  if (isError) {
    return <DashboardErrorState title={errorTitle} description={errorDescription} />;
  }

  return (
    <section className="rounded-lg border border-border bg-card p-5 shadow-sm">
      <h2>{title}</h2>
      {items && items.length > 0 ? (
        <div className="mt-4 divide-y divide-border">
          {items.map((item) => (
            <article key={item.id} className="flex gap-3 py-4 first:pt-0 last:pb-0">
              <div className="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                <Clock3 className="h-4 w-4" aria-hidden="true" />
              </div>
              <div className="min-w-0">
                <h3>{item.title}</h3>
                {item.description ? (
                  <p className="mt-1 text-sm leading-6 text-muted-foreground">
                    {item.description}
                  </p>
                ) : null}
                <div className="mt-2 flex flex-wrap gap-2 text-xs font-medium text-muted-foreground">
                  {item.actor ? <span>{item.actor}</span> : null}
                  {item.module ? <span>{item.module}</span> : null}
                  {item.created_at ? <span>{item.created_at}</span> : null}
                </div>
              </div>
            </article>
          ))}
        </div>
      ) : (
        <EmptyState title={emptyTitle} description={emptyDescription} />
      )}
    </section>
  );
}
