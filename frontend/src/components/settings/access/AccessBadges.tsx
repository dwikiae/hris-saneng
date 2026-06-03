"use client";

import { Badge } from "@/components/ui/badge";

interface AccessBadgesProps {
  items: Array<{ id: string | number; name: string }>;
  emptyLabel: string;
}

export function AccessBadges({ items, emptyLabel }: AccessBadgesProps) {
  if (items.length === 0) {
    return <span className="text-sm text-muted-foreground">{emptyLabel}</span>;
  }

  return (
    <div className="flex flex-wrap gap-1">
      {items.map((item) => (
        <Badge key={item.id} variant="outline">
          {item.name}
        </Badge>
      ))}
    </div>
  );
}
