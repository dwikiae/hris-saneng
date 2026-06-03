import { Suspense } from "react";
import { UsersAccessPage } from "./UsersAccessPage";

export default function UsersAccessRoute() {
  return (
    <Suspense fallback={null}>
      <UsersAccessPage />
    </Suspense>
  );
}
