"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { authService } from "@/services/auth.service";
import { useAuthStore } from "@/stores/auth.store";

export function useLogout() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const setUser = useAuthStore((state) => state.setUser);

  const clearSession = () => {
    window.localStorage.removeItem("dictive_hr_token");
    setUser(null);
    queryClient.removeQueries({ queryKey: ["auth"] });
    router.replace("/login");
  };

  const mutation = useMutation({
    mutationFn: authService.logout,
    onSettled: clearSession
  });

  return {
    logout: () => mutation.mutate(),
    isLoggingOut: mutation.isPending
  };
}
