"use client";

import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";

interface QuizTimerProps {
  initialMinutes: number;
  isRunning: boolean;
  onTimeout: () => void;
}

export function QuizTimer({ initialMinutes, isRunning, onTimeout }: QuizTimerProps) {
  const { t } = useTranslation("quiz");
  const [remainingSeconds, setRemainingSeconds] = useState(initialMinutes * 60);

  useEffect(() => {
    if (!isRunning) {
      return undefined;
    }

    const timer = window.setInterval(() => {
      setRemainingSeconds((current) => {
        if (current <= 1) {
          window.clearInterval(timer);
          onTimeout();
          return 0;
        }

        return current - 1;
      });
    }, 1000);

    return () => window.clearInterval(timer);
  }, [isRunning, onTimeout]);

  const minutes = Math.floor(remainingSeconds / 60);
  const seconds = String(remainingSeconds % 60).padStart(2, "0");

  return (
    <div className="rounded-md border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800">
      {t("timer.label")}: {minutes}:{seconds}
    </div>
  );
}
