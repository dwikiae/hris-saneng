"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useTranslation } from "react-i18next";
import { QuestionCard } from "@/components/modules/recruitment/quiz/QuestionCard";
import { QuizNav } from "@/components/modules/recruitment/quiz/QuizNav";
import { QuizTimer } from "@/components/modules/recruitment/quiz/QuizTimer";
import { quizService } from "@/services/quiz.service";
import type { Answer, QuizSession } from "@/types/quiz";

type PageState = "loading" | "error" | "active" | "submitted" | "timeout";

interface QuizPageProps {
  token: string;
}

function getQuestionId(question: QuizSession["questions"][number]): number {
  return question.test_question_id ?? question.question_id ?? question.id;
}

export function QuizPage({ token }: QuizPageProps) {
  const { t } = useTranslation("quiz");
  const [pageState, setPageState] = useState<PageState>("loading");
  const [quiz, setQuiz] = useState<QuizSession | null>(null);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [answers, setAnswers] = useState<Record<number, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const submittedRef = useRef(false);

  useEffect(() => {
    quizService.getQuiz(token).then((data) => {
      setQuiz(data);
      setPageState("active");
    }).catch(() => setPageState("error"));
  }, [token]);

  const answeredIndexes = useMemo(() => {
    if (!quiz) {
      return new Set<number>();
    }

    return new Set(
      quiz.questions
        .map((question, index) => (answers[getQuestionId(question)]?.trim() ? index : null))
        .filter((index): index is number => index !== null)
    );
  }, [answers, quiz]);

  const normalizedAnswers = useCallback((): Answer[] => {
    if (!quiz) {
      return [];
    }

    return quiz.questions.map((question) => {
      const questionId = getQuestionId(question);
      return { question_id: questionId, jawaban: answers[questionId] ?? "" };
    });
  }, [answers, quiz]);

  const submitQuiz = useCallback(async (submitState: PageState = "submitted") => {
    if (!quiz || submittedRef.current) {
      return;
    }

    submittedRef.current = true;
    setIsSubmitting(true);

    try {
      await quizService.submitQuiz(token, normalizedAnswers());
      setPageState(submitState);
    } catch {
      submittedRef.current = false;
      setPageState("error");
    } finally {
      setIsSubmitting(false);
    }
  }, [normalizedAnswers, quiz, token]);

  const currentQuestion = quiz?.questions[currentIndex] ?? null;
  const currentQuestionId = currentQuestion ? getQuestionId(currentQuestion) : 0;

  return (
    <main className="min-h-screen bg-slate-50 text-slate-950">
      <div className="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-4 py-6 sm:px-6 lg:px-8">
        <header className="mb-6 flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p className="text-sm font-medium uppercase tracking-wide text-slate-500">{t("header.eyebrow")}</p>
            <h1 className="mt-1 text-2xl font-semibold text-slate-950 sm:text-3xl">{t("header.title")}</h1>
          </div>
          {quiz && pageState === "active" ? (
            <QuizTimer initialMinutes={quiz.timer} isRunning={!isSubmitting} onTimeout={() => void submitQuiz("timeout")} />
          ) : null}
        </header>
        {pageState !== "active" ? <StatePanel title={t(`${pageState}.title`)} body={t(`${pageState}.body`)} /> : null}
        {pageState === "active" && quiz && currentQuestion ? (
          <div className="grid flex-1 gap-5 lg:grid-cols-[1fr_280px]">
            <div className="space-y-4">
              <QuestionCard
                question={currentQuestion}
                index={currentIndex}
                total={quiz.questions.length}
                answer={answers[currentQuestionId] ?? ""}
                onAnswer={(value) => setAnswers((current) => ({ ...current, [currentQuestionId]: value }))}
              />
              <div className="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                <button className="rounded-md border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 disabled:opacity-50" disabled={currentIndex === 0} onClick={() => setCurrentIndex((index) => Math.max(0, index - 1))}>
                  {t("actions.previous")}
                </button>
                <div className="flex gap-3">
                  <button className="rounded-md border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 disabled:opacity-50" disabled={currentIndex === quiz.questions.length - 1} onClick={() => setCurrentIndex((index) => Math.min(quiz.questions.length - 1, index + 1))}>
                    {t("actions.next")}
                  </button>
                  <button className="rounded-md bg-slate-950 px-4 py-3 text-sm font-semibold text-white disabled:opacity-60" disabled={isSubmitting} onClick={() => void submitQuiz()}>
                    {t("actions.submit")}
                  </button>
                </div>
              </div>
            </div>
            <QuizNav total={quiz.questions.length} currentIndex={currentIndex} answeredIndexes={answeredIndexes} onSelect={setCurrentIndex} />
          </div>
        ) : null}
      </div>
    </main>
  );
}

function StatePanel({ title, body }: { title: string; body: string }) {
  return (
    <section className="rounded-lg border border-slate-200 bg-white p-8 text-center shadow-sm">
      <h2 className="text-xl font-semibold text-slate-950">{title}</h2>
      <p className="mt-2 text-sm leading-6 text-slate-600">{body}</p>
    </section>
  );
}
