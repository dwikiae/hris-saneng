import { UserCircle } from "lucide-react";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";

interface AvatarWithInfoProps {
  name: string;
  subtitle?: string | null;
  imageUrl?: string | null;
}

export function AvatarWithInfo({ name, subtitle, imageUrl }: AvatarWithInfoProps) {
  const fallback = initials(name);

  return (
    <div className="flex min-w-0 items-center gap-3">
      <Avatar className="h-10 w-10">
        {imageUrl ? <AvatarImage src={imageUrl} alt={name} /> : null}
        <AvatarFallback className="bg-blue-100 text-blue-700">
          {fallback || <UserCircle className="h-4 w-4" aria-hidden="true" />}
        </AvatarFallback>
      </Avatar>
      <div className="min-w-0">
        <p className="truncate font-medium text-foreground">{name}</p>
        {subtitle ? <p className="truncate text-sm text-muted-foreground">{subtitle}</p> : null}
      </div>
    </div>
  );
}

function initials(name: string): string {
  return name
    .split(" ")
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join("");
}
