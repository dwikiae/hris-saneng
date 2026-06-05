import { Suspense } from "react";
import { EmployeeDetailPage } from "@/components/employees/detail/EmployeeDetailPage";

export default function EmployeeDetailRoute() {
  return (
    <Suspense fallback={null}>
      <EmployeeDetailPage />
    </Suspense>
  );
}
