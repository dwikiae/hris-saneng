"use client";

import { useMutation } from "@tanstack/react-query";
import Link from "next/link";
import { FormEvent, useState } from "react";
import { useTranslation } from "react-i18next";
import { AuthField } from "@/components/auth/AuthField";
import { AuthShell } from "@/components/auth/AuthShell";
import { platformToast } from "@/components/platform/ToastProvider";
import { Button } from "@/components/ui/button";
import { authService } from "@/services/auth.service";

export default function ForgotPasswordPage() {
  const { t } = useTranslation("platform");
  const [email, setEmail] = useState("");
  const [submitted, setSubmitted] = useState(false);
  const mutation = useMutation({
    mutationFn: () => authService.forgotPassword({ email }),
    onSuccess: () => {
      setSubmitted(true);
      platformToast.success(t("auth.forgot.success"));
    },
    onError: () => platformToast.error(t("auth.gaps.passwordRecovery"))
  });

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    mutation.mutate();
  };

  return (
    <AuthShell title={t("auth.forgot.title")} description={t("auth.forgot.description")}>
      {submitted ? (
        <div className="space-y-4">
          <div className="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
            {t("auth.forgot.submitted")}
          </div>
          <Button asChild className="w-full" variant="outline">
            <Link href="/login">{t("auth.actions.backToLogin")}</Link>
          </Button>
        </div>
      ) : (
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
          <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-900">
            {t("auth.gaps.passwordRecovery")}
          </div>
          <Button type="submit" className="w-full" disabled={mutation.isPending}>
            {mutation.isPending ? t("auth.actions.loading") : t("auth.forgot.submit")}
          </Button>
          <Button asChild className="w-full" variant="ghost">
            <Link href="/login">{t("auth.actions.backToLogin")}</Link>
          </Button>
        </form>
      )}
    </AuthShell>
  );
}
