import { CalendarX, Plane, UserCheck, Users } from "lucide-react";
import { DashboardErrorState } from "@/components/dashboard/DashboardErrorState";
import { LoadingSkeleton } from "@/components/shared/LoadingSkeleton";
import { StatCard } from "@/components/shared/StatCard";
import type { DashboardStats } from "@/types/dashboard";

interface DashboardStatsRowProps {
  data?: DashboardStats;
  errorDescription: string;
  errorTitle: string;
  isError: boolean;
  isLoading: boolean;
  labels: {
    totalEmployees: string;
    presentToday: string;
    absentToday: string;
    leaveToday: string;
  };
  retryLabel: string;
  onRetry: () => void;
}

export function DashboardStatsRow({
  data,
  errorDescription,
  errorTitle,
  isError,
  isLoading,
  labels,
  retryLabel,
  onRetry
}: DashboardStatsRowProps) {
  if (isLoading) {
    return (
      <LoadingSkeleton
        rows={4}
        className="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
        itemClassName="h-32"
      />
    );
  }

  if (isError || !data) {
    return (
      <DashboardErrorState
        title={errorTitle}
        description={errorDescription}
        retryLabel={retryLabel}
        onRetry={onRetry}
      />
    );
  }

  return (
    <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <StatCard
        label={labels.totalEmployees}
        value={String(data.total_employees)}
        icon={<Users className="h-5 w-5" aria-hidden="true" />}
      />
      <StatCard
        label={labels.presentToday}
        value={String(data.present_today)}
        icon={<UserCheck className="h-5 w-5" aria-hidden="true" />}
      />
      <StatCard
        label={labels.absentToday}
        value={String(data.absent_today)}
        icon={<CalendarX className="h-5 w-5" aria-hidden="true" />}
      />
      <StatCard
        label={labels.leaveToday}
        value={String(data.leave_today)}
        icon={<Plane className="h-5 w-5" aria-hidden="true" />}
      />
    </section>
  );
}
