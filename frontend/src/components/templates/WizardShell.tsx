"use client";

import type { ReactNode } from "react";
import { Check, Loader2 } from "lucide-react";
import { useTranslation } from "react-i18next";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

export interface WizardStep {
  id: string;
  label: string;
  title: string;
  description?: string;
  content: ReactNode;
}

interface WizardShellProps {
  title: string;
  logo?: ReactNode;
  steps: WizardStep[];
  currentStepId: string;
  onBack?: () => void;
  onNext?: () => void;
  canGoBack?: boolean;
  canGoNext?: boolean;
  isSubmitting?: boolean;
  backLabel?: string;
  nextLabel?: string;
  finishLabel?: string;
}

export function WizardShell({
  title,
  logo,
  steps,
  currentStepId,
  onBack,
  onNext,
  canGoBack = true,
  canGoNext = true,
  isSubmitting = false,
  backLabel,
  nextLabel,
  finishLabel
}: WizardShellProps) {
  const { t } = useTranslation("platform");
  const currentIndex = Math.max(
    0,
    steps.findIndex((step) => step.id === currentStepId)
  );
  const currentStep = steps[currentIndex] ?? steps[0];
  const isLastStep = currentIndex === steps.length - 1;

  return (
    <div className="min-h-screen bg-slate-50">
      <div className="mx-auto flex min-h-screen max-w-5xl flex-col px-4 py-6">
        <header className="rounded-lg border border-border bg-card p-5 shadow-sm">
          <div className="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
            <div className="flex items-center gap-3">
              {logo ? <div className="text-primary">{logo}</div> : null}
              <h1 className="text-xl font-semibold text-foreground">{title}</h1>
            </div>
            <ol className="flex min-w-0 flex-wrap items-center gap-3">
              {steps.map((step, index) => {
                const state =
                  index < currentIndex ? "complete" : index === currentIndex ? "current" : "upcoming";

                return (
                  <li key={step.id} className="flex items-center gap-2">
                    <span
                      className={cn(
                        "flex h-8 w-8 items-center justify-center rounded-full border text-xs font-semibold",
                        state === "complete" && "border-primary bg-primary text-primary-foreground",
                        state === "current" && "border-primary bg-blue-50 text-primary",
                        state === "upcoming" && "border-border bg-background text-muted-foreground"
                      )}
                    >
                      {state === "complete" ? <Check className="h-4 w-4" /> : index + 1}
                    </span>
                    <span
                      className={cn(
                        "hidden text-sm font-medium md:inline",
                        state === "current" ? "text-foreground" : "text-muted-foreground"
                      )}
                    >
                      {step.label}
                    </span>
                  </li>
                );
              })}
            </ol>
          </div>
        </header>

        <main className="flex-1 py-8">
          <div className="rounded-lg border border-border bg-card p-6 shadow-sm">
            <p className="text-xs font-medium uppercase text-muted-foreground">
              {t("templates.wizard.stepCounter", {
                current: currentIndex + 1,
                total: steps.length
              })}
            </p>
            <h2 className="mt-2 text-2xl font-semibold text-foreground">{currentStep.title}</h2>
            {currentStep.description ? (
              <p className="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                {currentStep.description}
              </p>
            ) : null}
            <div className="mt-6">{currentStep.content}</div>
          </div>
        </main>

        <footer className="rounded-lg border border-border bg-card p-4 shadow-sm">
          <div className="flex items-center justify-between gap-3">
            <Button
              type="button"
              variant="outline"
              disabled={!canGoBack || isSubmitting || currentIndex === 0}
              onClick={onBack}
            >
              {backLabel ?? t("templates.wizard.back")}
            </Button>
            <Button type="button" disabled={!canGoNext || isSubmitting} onClick={onNext}>
              {isSubmitting ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
              {isLastStep
                ? finishLabel ?? t("templates.wizard.finish")
                : nextLabel ?? t("templates.wizard.next")}
            </Button>
          </div>
        </footer>
      </div>
    </div>
  );
}
