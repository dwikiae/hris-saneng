"use client";

import { cloneElement, isValidElement, type ReactElement, type ReactNode } from "react";
import { useAuthStore } from "@/stores/auth.store";

type PermissionFallback = "hidden" | "disabled" | "redacted";

interface PermissionGateProps {
  permission: string;
  fallback?: PermissionFallback;
  children: ReactNode;
}

export function PermissionGate({
  permission,
  fallback = "hidden",
  children
}: PermissionGateProps) {
  const hasPermission = useAuthStore((state) => state.hasPermission(permission));

  if (hasPermission) {
    return <>{children}</>;
  }

  if (fallback === "redacted") {
    return <span aria-label="redacted">••••••</span>;
  }

  if (fallback === "disabled") {
    if (isValidElement(children)) {
      return cloneElement(children as ReactElement<{ disabled?: boolean; "aria-disabled"?: boolean }>, {
        "aria-disabled": true,
        disabled: true
      });
    }

    return (
      <span aria-disabled="true" className="pointer-events-none opacity-50">
        {children}
      </span>
    );
  }

  return null;
}
