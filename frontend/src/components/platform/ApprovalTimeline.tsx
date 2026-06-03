"use client";

import { Clock } from "lucide-react";
import type { ApprovalTimelineItem } from "@/types/platform";

interface ApprovalTimelineProps {
  items: ApprovalTimelineItem[];
}

export function ApprovalTimeline({ items }: ApprovalTimelineProps) {
  return (
    <ol className="space-y-4">
      {items.map((item) => (
        <li key={item.id} className="flex gap-3">
          <span className="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">
            <Clock className="h-4 w-4" aria-hidden="true" />
          </span>
          <div className="min-w-0">
            <p className="text-sm font-medium text-foreground">
              {item.actor} · {item.action}
            </p>
            <p className="text-xs text-muted-foreground">{item.createdAt}</p>
            {item.note ? <p className="mt-1 text-sm text-muted-foreground">{item.note}</p> : null}
          </div>
        </li>
      ))}
    </ol>
  );
}
