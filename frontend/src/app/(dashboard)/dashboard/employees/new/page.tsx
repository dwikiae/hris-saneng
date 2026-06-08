import { Suspense } from "react";
import { EmployeeFormPage } from "@/components/employees/form/EmployeeFormPage";

export default function NewEmployeeRoute() {
  return (
    <Suspense fallback={null}>
      <EmployeeFormPage mode="new" />
    </Suspense>
  );
}
