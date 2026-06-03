"use client";

import { useQuery } from "@tanstack/react-query";
import { useEffect } from "react";
import { authService } from "@/services/auth.service";
import { useAuthStore } from "@/stores/auth.store";

export function AuthHydrator() {
  const setUser = useAuthStore((state) => state.setUser);
  const hasToken = typeof window !== "undefined" && Boolean(window.localStorage.getItem("dictive_hr_token"));
  const currentUserQuery = useQuery({
    queryKey: ["auth", "me", "hydrator"],
    queryFn: authService.currentUser,
    enabled: hasToken,
    retry: false
  });

  useEffect(() => {
    if (!hasToken) {
      setUser(null);
    }
  }, [hasToken, setUser]);

  useEffect(() => {
    if (currentUserQuery.data) {
      setUser(currentUserQuery.data);
    }
  }, [currentUserQuery.data, setUser]);

  return null;
}
