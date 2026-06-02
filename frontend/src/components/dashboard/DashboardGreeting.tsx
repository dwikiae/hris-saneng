import { Skeleton } from "@/components/ui/skeleton";

type GreetingPeriod = "morning" | "afternoon" | "evening" | "night";

function currentGreetingPeriod(): GreetingPeriod {
  const hour = new Date().getHours();

  if (hour < 11) {
    return "morning";
  }

  if (hour < 15) {
    return "afternoon";
  }

  if (hour < 18) {
    return "evening";
  }

  return "night";
}

interface DashboardGreetingProps {
  isError: boolean;
  isLoading: boolean;
  titleFor: (period: GreetingPeriod) => string;
  unavailableText: string;
}

export function DashboardGreeting({
  isError,
  isLoading,
  titleFor,
  unavailableText
}: DashboardGreetingProps) {
  if (isLoading) {
    return (
      <div className="mb-6 rounded-lg border border-border bg-card p-5 shadow-sm">
        <Skeleton className="h-7 w-72 max-w-full" />
        <Skeleton className="mt-3 h-4 w-96 max-w-full" />
      </div>
    );
  }

  return (
    <section className="mb-6 rounded-lg border border-border bg-card p-5 shadow-sm">
      {isError ? (
        <p className="text-sm font-medium text-muted-foreground" role="alert">
          {unavailableText}
        </p>
      ) : (
        <h1>{titleFor(currentGreetingPeriod())}</h1>
      )}
    </section>
  );
}
