import { Suspense } from "react";
import { EmployeeFormPage } from "@/components/employees/form/EmployeeFormPage";

interface EditEmployeeRouteProps {
  params: {
    id: string;
  };
}

export default function EditEmployeeRoute({ params }: EditEmployeeRouteProps) {
  return (
    <Suspense fallback={null}>
      <EmployeeFormPage mode="edit" employeeId={params.id} />
    </Suspense>
  );
}
