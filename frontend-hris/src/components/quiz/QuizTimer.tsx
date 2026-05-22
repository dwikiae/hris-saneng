import { useEffect, useMemo, useState } from "react";
import { useTranslation } from "next-i18next/pages";

interface QuizTimerProps {
  initialMinutes: number;
  isRunning: boolean;
  onTimeout: () => void;
}

export default function QuizTimer({
  initialMinutes,
  isRunning,
  onTimeout,
}: QuizTimerProps) {
  const { t } = useTranslation("quiz");
  const initialSeconds = useMemo(
    () => Math.max(0, Math.floor(initialMinutes * 60)),
    [initialMinutes],
  );
  const [remainingSeconds, setRemainingSeconds] = useState(initialSeconds);

  useEffect(() => {
    setRemainingSeconds(initialSeconds);
  }, [initialSeconds]);

  useEffect(() => {
    if (!isRunning) {
      return;
    }

    if (remainingSeconds <= 0) {
      onTimeout();
      return;
    }

    const timerId = window.setTimeout(() => {
      setRemainingSeconds((current) => Math.max(0, current - 1));
    }, 1000);

    return () => window.clearTimeout(timerId);
  }, [isRunning, onTimeout, remainingSeconds]);

  const minutes = Math.floor(remainingSeconds / 60);
  const seconds = remainingSeconds % 60;
  const isCritical = remainingSeconds < 300;

  return (
    <div
      className={`rounded-md border px-4 py-3 text-right ${
        isCritical
          ? "border-red-300 bg-red-50 text-red-700"
          : "border-slate-200 bg-white text-slate-900"
      }`}
      role="timer"
      aria-live={isCritical ? "assertive" : "polite"}
    >
      <div className="text-xs font-medium uppercase tracking-wide text-slate-500">
        {t("timer.label")}
      </div>
      <div className="font-mono text-2xl font-semibold">
        {String(minutes).padStart(2, "0")}:{String(seconds).padStart(2, "0")}
      </div>
    </div>
  );
}
