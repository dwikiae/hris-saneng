"use client";

import { useMutation } from "@tanstack/react-query";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { FormEvent, useState } from "react";
import { useTranslation } from "react-i18next";
import { AuthField } from "@/components/auth/AuthField";
import { AuthShell } from "@/components/auth/AuthShell";
import { platformToast } from "@/components/platform/ToastProvider";
import { Button } from "@/components/ui/button";
import { authService } from "@/services/auth.service";

export function ResetPasswordForm() {
  const { t } = useTranslation("platform");
  const searchParams = useSearchParams();
  const token = searchParams.get("token") ?? "";
  const initialEmail = searchParams.get("email") ?? "";
  const [email, setEmail] = useState(initialEmail);
  const [password, setPassword] = useState("");
  const [confirmation, setConfirmation] = useState("");
  const [submitted, setSubmitted] = useState(false);
  const passwordMismatch = confirmation.length > 0 && password !== confirmation;
  const mutation = useMutation({
    mutationFn: () =>
      authService.resetPassword({
        email,
        token,
        password,
        password_confirmation: confirmation
      }),
    onSuccess: () => {
      setSubmitted(true);
      platformToast.success(t("auth.reset.success"));
    },
    onError: () => platformToast.error(t("auth.gaps.passwordRecovery"))
  });

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!token || passwordMismatch) {
      return;
    }
    mutation.mutate();
  };

  return (
    <AuthShell title={t("auth.reset.title")} description={t("auth.reset.description")}>
      {submitted ? (
        <div className="space-y-4">
          <div className="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
            {t("auth.reset.submitted")}
          </div>
          <Button asChild className="w-full">
            <Link href="/login">{t("auth.actions.backToLogin")}</Link>
          </Button>
        </div>
      ) : (
        <form className="space-y-4" onSubmit={submit}>
          {!token ? (
            <div className="rounded-lg border border-destructive/30 bg-red-50 p-3 text-xs leading-5 text-destructive">
              {t("auth.reset.missingToken")}
            </div>
          ) : null}
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
            label={t("auth.fields.newPassword")}
            type="password"
            autoComplete="new-password"
            required
            minLength={8}
            value={password}
            onChange={(event) => setPassword(event.target.value)}
          />
          <AuthField
            id="password-confirmation"
            label={t("auth.fields.confirmPassword")}
            type="password"
            autoComplete="new-password"
            required
            minLength={8}
            value={confirmation}
            error={passwordMismatch ? t("auth.validation.passwordMismatch") : undefined}
            onChange={(event) => setConfirmation(event.target.value)}
          />
          <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-900">
            {t("auth.gaps.passwordRecovery")}
          </div>
          <Button type="submit" className="w-full" disabled={!token || passwordMismatch || mutation.isPending}>
            {mutation.isPending ? t("auth.actions.loading") : t("auth.reset.submit")}
          </Button>
        </form>
      )}
    </AuthShell>
  );
}
