"use client";

import { useMutation } from "@tanstack/react-query";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { FormEvent, useState } from "react";
import { useTranslation } from "react-i18next";
import { AuthField } from "@/components/auth/AuthField";
import { AuthShell } from "@/components/auth/AuthShell";
import { platformToast } from "@/components/platform/ToastProvider";
import { Button } from "@/components/ui/button";
import { authService } from "@/services/auth.service";
import { useAuthStore } from "@/stores/auth.store";

export function SetPasswordForm() {
  const { t } = useTranslation("platform");
  const router = useRouter();
  const searchParams = useSearchParams();
  const token = searchParams.get("token") ?? "";
  const user = useAuthStore((state) => state.user);
  const setUser = useAuthStore((state) => state.setUser);
  const [currentPassword, setCurrentPassword] = useState("");
  const [password, setPassword] = useState("");
  const [confirmation, setConfirmation] = useState("");
  const passwordMismatch = confirmation.length > 0 && password !== confirmation;
  const forcedResetMode = !token && Boolean(user?.force_password_reset);
  const invitationMode = Boolean(token);
  const mutation = useMutation({
    mutationFn: () =>
      invitationMode
        ? authService.setPassword({ token, password, password_confirmation: confirmation })
        : authService.changePassword({
            current_password: currentPassword,
            new_password: password,
            new_password_confirmation: confirmation
          }),
    onSuccess: () => {
      window.localStorage.removeItem("dictive_hr_token");
      setUser(null);
      platformToast.success(t("auth.setPassword.success"));
      router.replace("/login");
    },
    onError: () =>
      platformToast.error(invitationMode ? t("auth.gaps.invitation") : t("auth.setPassword.failed"))
  });

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if ((!invitationMode && !forcedResetMode) || passwordMismatch) {
      return;
    }
    mutation.mutate();
  };

  return (
    <AuthShell title={t("auth.setPassword.title")} description={t("auth.setPassword.description")}>
      <form className="space-y-4" onSubmit={submit}>
        {!invitationMode && !forcedResetMode ? (
          <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-900">
            {t("auth.gaps.invitation")}
          </div>
        ) : null}
        {forcedResetMode ? (
          <AuthField
            id="current-password"
            label={t("auth.fields.currentPassword")}
            type="password"
            autoComplete="current-password"
            required
            value={currentPassword}
            onChange={(event) => setCurrentPassword(event.target.value)}
          />
        ) : null}
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
        {invitationMode ? (
          <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-900">
            {t("auth.gaps.invitation")}
          </div>
        ) : null}
        <Button
          type="submit"
          className="w-full"
          disabled={(!invitationMode && !forcedResetMode) || passwordMismatch || mutation.isPending}
        >
          {mutation.isPending ? t("auth.actions.loading") : t("auth.setPassword.submit")}
        </Button>
        <Button asChild className="w-full" variant="ghost">
          <Link href="/login">{t("auth.actions.backToLogin")}</Link>
        </Button>
      </form>
    </AuthShell>
  );
}
