"use client";

import dynamic from "next/dynamic";
import { LoadingSkeleton } from "@/components/shared/LoadingSkeleton";

const EmployeeFormPage = dynamic(
  () => import("@/components/employees/form/EmployeeFormPage").then((module) => module.EmployeeFormPage),
  {
    ssr: false,
    loading: () => <LoadingSkeleton rows={5} itemClassName="h-20" />
  }
);

export default function NewEmployeeRoute() {
  return <EmployeeFormPage mode="new" />;
}
