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

interface EditEmployeeRouteProps {
  params: {
    id: string;
  };
}

export default function EditEmployeeRoute({ params }: EditEmployeeRouteProps) {
  return <EmployeeFormPage mode="edit" employeeId={params.id} />;
}
