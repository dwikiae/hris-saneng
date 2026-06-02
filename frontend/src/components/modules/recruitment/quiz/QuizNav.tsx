"use client";

interface QuizNavProps {
  total: number;
  currentIndex: number;
  answeredIndexes: Set<number>;
  onSelect: (index: number) => void;
}

export function QuizNav({ total, currentIndex, answeredIndexes, onSelect }: QuizNavProps) {
  return (
    <aside className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
      <div className="grid grid-cols-5 gap-2 lg:grid-cols-4">
        {Array.from({ length: total }, (_, index) => (
          <button
            key={index}
            type="button"
            className={
              currentIndex === index
                ? "rounded-md bg-slate-950 px-2 py-2 text-sm font-semibold text-white"
                : answeredIndexes.has(index)
                  ? "rounded-md bg-emerald-50 px-2 py-2 text-sm font-semibold text-emerald-800"
                  : "rounded-md border border-slate-200 px-2 py-2 text-sm font-semibold text-slate-600"
            }
            onClick={() => onSelect(index)}
          >
            {index + 1}
          </button>
        ))}
      </div>
    </aside>
  );
}
