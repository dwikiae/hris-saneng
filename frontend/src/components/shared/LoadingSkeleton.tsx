import { Skeleton } from "@/components/ui/skeleton";
import { cn } from "@/lib/utils";

interface LoadingSkeletonProps {
  rows?: number;
  className?: string;
  itemClassName?: string;
}

export function LoadingSkeleton({ rows = 3, className, itemClassName }: LoadingSkeletonProps) {
  return (
    <div className={cn("space-y-3", className)}>
      {Array.from({ length: rows }).map((_, index) => (
        <Skeleton key={index} className={cn("h-16 w-full rounded-lg", itemClassName)} />
      ))}
    </div>
  );
}
