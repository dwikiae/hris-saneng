import { Suspense } from "react";
import { UserDetailPage } from "./UserDetailPage";

export default function UserDetailRoute() {
  return (
    <Suspense fallback={null}>
      <UserDetailPage />
    </Suspense>
  );
}
