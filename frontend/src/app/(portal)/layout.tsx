import type { ReactNode } from "react";

interface PortalGroupLayoutProps {
  children: ReactNode;
}

export default function PortalGroupLayout({ children }: PortalGroupLayoutProps) {
  return <div className="min-h-screen bg-surface">{children}</div>;
}
