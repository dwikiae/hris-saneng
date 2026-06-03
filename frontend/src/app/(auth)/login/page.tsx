"use client";

import { useMutation } from "@tanstack/react-query";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { FormEvent, useState } from "react";
import { useTranslation } from "react-i18next";
import { AuthField } from "@/components/auth/AuthField";
import { AuthShell } from "@/components/auth/AuthShell";
import { platformToast } from "@/components/platform/ToastProvider";
import { Button } from "@/components/ui/button";
import { authService } from "@/services/auth.service";
import { useAuthStore } from "@/stores/auth.store";

export default function LoginPage() {
  const { t } = useTranslation("platform");
  const router = useRouter();
  const setUser = useAuthStore((state) => state.setUser);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const mutation = useMutation({
    mutationFn: () => authService.login({ email, password }),
    onSuccess: (response) => {
      window.localStorage.setItem("dictive_hr_token", response.token);
      setUser(response.user);
      platformToast.success(t("auth.login.success"));
      router.replace(response.user.force_password_reset ? "/set-password" : "/dashboard");
    },
    onError: () => platformToast.error(t("auth.login.failed"))
  });

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    mutation.mutate();
  };

  return (
    <AuthShell title={t("auth.login.title")} description={t("auth.login.description")}>
      <form className="space-y-4" onSubmit={submit}>
        <AuthField
          id="email"
          label={t("auth.fields.email")}
          type="email"
          autoComplete="email"
          required
          value={email}
          onChange={(event) => setEmail(event.target.value)}
        />
        <AuthField
          id="password"
          label={t("auth.fields.password")}
          type="password"
          autoComplete="current-password"
          required
          value={password}
          onChange={(event) => setPassword(event.target.value)}
        />
        <div className="flex justify-end">
          <Link className="text-sm font-medium text-primary hover:text-primary/80" href="/forgot-password">
            {t("auth.login.forgotPassword")}
          </Link>
        </div>
        <Button type="submit" className="w-full" disabled={mutation.isPending}>
          {mutation.isPending ? t("auth.actions.loading") : t("auth.login.submit")}
        </Button>
      </form>
    </AuthShell>
  );
}
