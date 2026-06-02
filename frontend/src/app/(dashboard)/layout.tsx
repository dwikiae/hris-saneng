import type { ReactNode } from "react";
import { AppShell } from "@/components/layout/AppShell";

interface DashboardGroupLayoutProps {
  children: ReactNode;
}

export default function DashboardGroupLayout({ children }: DashboardGroupLayoutProps) {
  return <AppShell>{children}</AppShell>;
}
