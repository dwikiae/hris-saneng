import { Suspense } from "react";
import { RoleDetailPage } from "./RoleDetailPage";

export default function RoleDetailRoute() {
  return (
    <Suspense fallback={null}>
      <RoleDetailPage />
    </Suspense>
  );
}
