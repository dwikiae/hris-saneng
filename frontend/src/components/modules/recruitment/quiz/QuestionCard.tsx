"use client";

import { useTranslation } from "react-i18next";
import type { Question } from "@/types/quiz";

interface QuestionCardProps {
  question: Question;
  index: number;
  total: number;
  answer: string;
  onAnswer: (value: string) => void;
}

function questionText(question: Question): string {
  return question.question ?? question.pertanyaan ?? "-";
}

function questionOptions(question: Question): string[] {
  const options = question.options ?? question.pilihan;

  if (Array.isArray(options)) {
    return options;
  }

  return options ? Object.values(options) : [];
}

export function QuestionCard({ question, index, total, answer, onAnswer }: QuestionCardProps) {
  const { t } = useTranslation("quiz");
  const options = questionOptions(question);

  return (
    <article className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
      <p className="text-sm font-semibold text-slate-500">
        {t("question.number", { current: index + 1, total })}
      </p>
      <h2 className="mt-3 text-xl font-semibold text-slate-950">{questionText(question)}</h2>
      {options.length > 0 ? (
        <div className="mt-5 space-y-3">
          {options.map((option) => (
            <label key={option} className="flex items-center gap-3 rounded-md border border-slate-200 px-3 py-2">
              <input type="radio" checked={answer === option} onChange={() => onAnswer(option)} />
              <span className="text-sm text-slate-700">{option}</span>
            </label>
          ))}
        </div>
      ) : (
        <label className="mt-5 block text-sm font-medium text-slate-700">
          <span>{t("question.answer")}</span>
          <textarea
            value={answer}
            className="mt-2 min-h-32 w-full rounded-md border border-slate-300 px-3 py-2"
            onChange={(event) => onAnswer(event.target.value)}
          />
        </label>
      )}
    </article>
  );
}
