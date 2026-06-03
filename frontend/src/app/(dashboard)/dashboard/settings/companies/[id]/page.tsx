import { Suspense } from "react";
import { CompanyDetailPage } from "./CompanyDetailPage";

export default function CompanyDetailRoute() {
  return (
    <Suspense fallback={null}>
      <CompanyDetailPage />
    </Suspense>
  );
}
