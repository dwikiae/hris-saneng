import type { ReactNode } from "react";
import { PublicLayout } from "@/components/core/public-layout/PublicLayout";

interface CompanyLayoutProps {
  children: ReactNode;
}

export default function CompanyLayout({ children }: CompanyLayoutProps) {
  return <PublicLayout>{children}</PublicLayout>;
}
