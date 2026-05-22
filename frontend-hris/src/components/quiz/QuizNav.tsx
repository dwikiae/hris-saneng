import { useTranslation } from "next-i18next/pages";

interface QuizNavProps {
  total: number;
  currentIndex: number;
  answeredIndexes: Set<number>;
  onSelect: (index: number) => void;
}

export default function QuizNav({
  total,
  currentIndex,
  answeredIndexes,
  onSelect,
}: QuizNavProps) {
  const { t } = useTranslation("quiz");

  return (
    <nav
      className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
      aria-label={t("navigation.label")}
    >
      <div className="mb-3 text-sm font-medium text-slate-700">
        {t("navigation.title")}
      </div>
      <div className="grid grid-cols-5 gap-2 sm:grid-cols-8 lg:grid-cols-5">
        {Array.from({ length: total }, (_, index) => {
          const isActive = index === currentIndex;
          const isAnswered = answeredIndexes.has(index);

          return (
            <button
              key={index}
              type="button"
              className={`aspect-square rounded-md border text-sm font-semibold transition ${
                isActive
                  ? "border-slate-950 bg-slate-950 text-white"
                  : isAnswered
                    ? "border-emerald-300 bg-emerald-50 text-emerald-800"
                    : "border-slate-200 bg-white text-slate-600 hover:border-slate-400"
              }`}
              onClick={() => onSelect(index)}
              aria-current={isActive ? "step" : undefined}
            >
              {index + 1}
            </button>
          );
        })}
      </div>
    </nav>
  );
}
