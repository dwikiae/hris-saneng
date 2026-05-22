import { useMemo } from "react";
import { useTranslation } from "next-i18next/pages";
import type { Question, QuestionType } from "@/types/quiz";

interface QuestionCardProps {
  question: Question;
  index: number;
  total: number;
  answer: string;
  onAnswer: (value: string) => void;
}

function normalizeOptions(
  options: Question["options"] | Question["pilihan"],
): Array<{ key: string; label: string; value: string }> {
  if (Array.isArray(options)) {
    return options.map((label, index) => ({
      key: String(index),
      label,
      value: label,
    }));
  }

  if (options && typeof options === "object") {
    return Object.entries(options).map(([key, label]) => ({
      key,
      label,
      value: key,
    }));
  }

  return [];
}

export default function QuestionCard({
  question,
  index,
  total,
  answer,
  onAnswer,
}: QuestionCardProps) {
  const { t } = useTranslation("quiz");
  const type = (question.type ?? question.tipe) as QuestionType | undefined;
  const text = question.question ?? question.pertanyaan ?? "";
  const options = useMemo(
    () => normalizeOptions(question.options ?? question.pilihan),
    [question.options, question.pilihan],
  );

  return (
    <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
      <div className="mb-4 flex items-start justify-between gap-4">
        <div>
          <p className="text-sm font-medium text-slate-500">
            {t("question.progress", { current: index + 1, total })}
          </p>
          <h1 className="mt-2 text-xl font-semibold leading-7 text-slate-950">
            {text}
          </h1>
        </div>
      </div>

      {type === "isian_singkat" || type === "short_answer" ? (
        <label className="block">
          <span className="sr-only">{t("question.shortAnswer")}</span>
          <textarea
            className="min-h-32 w-full resize-y rounded-md border border-slate-300 px-3 py-3 text-base text-slate-950 outline-none transition focus:border-slate-900 focus:ring-2 focus:ring-slate-200"
            value={answer}
            onChange={(event) => onAnswer(event.target.value)}
            placeholder={t("question.shortAnswerPlaceholder")}
          />
        </label>
      ) : (
        <div className="grid gap-3">
          {options.map((option) => {
            const selected = answer === option.value;

            return (
              <button
                key={option.key}
                type="button"
                className={`rounded-md border px-4 py-3 text-left text-base transition ${
                  selected
                    ? "border-slate-950 bg-slate-950 text-white"
                    : "border-slate-200 bg-white text-slate-900 hover:border-slate-400"
                }`}
                onClick={() => onAnswer(option.value)}
              >
                {option.label}
              </button>
            );
          })}
        </div>
      )}
    </section>
  );
}
