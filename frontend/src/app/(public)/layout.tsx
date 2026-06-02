import type { ReactNode } from "react";

interface PublicGroupLayoutProps {
  children: ReactNode;
}

export default function PublicGroupLayout({ children }: PublicGroupLayoutProps) {
  return children;
}
