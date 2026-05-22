import Head from "next/head";
import type { GetServerSideProps } from "next";
import { useRouter } from "next/router";
import { serverSideTranslations } from "next-i18next/pages/serverSideTranslations";
import { useTranslation } from "next-i18next/pages";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import QuestionCard from "@/components/quiz/QuestionCard";
import QuizNav from "@/components/quiz/QuizNav";
import QuizTimer from "@/components/quiz/QuizTimer";
import { quizService } from "@/services/quiz.service";
import type { Answer, QuizSession } from "@/types/quiz";

type PageState = "loading" | "error" | "active" | "submitted" | "timeout";

function getQuestionId(question: QuizSession["questions"][number]): number {
  return question.test_question_id ?? question.question_id ?? question.id;
}

export default function QuizPage() {
  const { t } = useTranslation("quiz");
  const router = useRouter();
  const token = typeof router.query.token === "string" ? router.query.token : "";
  const [pageState, setPageState] = useState<PageState>("loading");
  const [quiz, setQuiz] = useState<QuizSession | null>(null);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [answers, setAnswers] = useState<Record<number, string>>({});
  const [showConfirm, setShowConfirm] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const submittedRef = useRef(false);

  useEffect(() => {
    if (!router.isReady || !token) {
      return;
    }

    let isMounted = true;

    quizService
      .getQuiz(token)
      .then((data) => {
        if (!isMounted) {
          return;
        }

        setQuiz(data);
        setPageState("active");
      })
      .catch(() => {
        if (!isMounted) {
          return;
        }

        setPageState("error");
      });

    return () => {
      isMounted = false;
    };
  }, [router.isReady, token]);

  const answeredIndexes = useMemo(() => {
    if (!quiz) {
      return new Set<number>();
    }

    return new Set(
      quiz.questions
        .map((question, index) => {
          const answer = answers[getQuestionId(question)];
          return answer?.trim() ? index : null;
        })
        .filter((index): index is number => index !== null),
    );
  }, [answers, quiz]);

  const normalizedAnswers = useCallback((): Answer[] => {
    if (!quiz) {
      return [];
    }

    return quiz.questions.map((question) => {
      const questionId = getQuestionId(question);

      return {
        question_id: questionId,
        jawaban: answers[questionId] ?? "",
      };
    });
  }, [answers, quiz]);

  const submitQuiz = useCallback(
    async (submitState: PageState = "submitted") => {
      if (!quiz || !token || submittedRef.current) {
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
        setShowConfirm(false);
      }
    },
    [normalizedAnswers, quiz, token],
  );

  const handleSubmit = () => {
    if (!quiz) {
      return;
    }

    if (answeredIndexes.size < quiz.questions.length) {
      setShowConfirm(true);
      return;
    }

    void submitQuiz();
  };

  const handleTimeout = useCallback(() => {
    void submitQuiz("timeout");
  }, [submitQuiz]);

  const currentQuestion = quiz?.questions[currentIndex] ?? null;
  const currentQuestionId = currentQuestion ? getQuestionId(currentQuestion) : 0;

  return (
    <>
      <Head>
        <title>{t("meta.title")}</title>
      </Head>
      <main className="min-h-screen bg-slate-50 text-slate-950">
        <div className="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-4 py-6 sm:px-6 lg:px-8">
          <header className="mb-6 flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p className="text-sm font-medium uppercase tracking-wide text-slate-500">
                {t("header.eyebrow")}
              </p>
              <h1 className="mt-1 text-2xl font-semibold text-slate-950 sm:text-3xl">
                {t("header.title")}
              </h1>
            </div>
            {quiz && pageState === "active" ? (
              <QuizTimer
                initialMinutes={quiz.timer}
                isRunning={!isSubmitting}
                onTimeout={handleTimeout}
              />
            ) : null}
          </header>

          {pageState === "loading" ? (
            <StatePanel title={t("loading.title")} body={t("loading.body")} />
          ) : null}

          {pageState === "error" ? (
            <StatePanel title={t("error.title")} body={t("error.body")} />
          ) : null}

          {pageState === "submitted" ? (
            <StatePanel
              title={t("submitted.title")}
              body={t("submitted.body")}
            />
          ) : null}

          {pageState === "timeout" ? (
            <StatePanel title={t("timeout.title")} body={t("timeout.body")} />
          ) : null}

          {pageState === "active" && quiz && currentQuestion ? (
            <div className="grid flex-1 gap-5 lg:grid-cols-[1fr_280px]">
              <div className="space-y-4">
                <QuestionCard
                  question={currentQuestion}
                  index={currentIndex}
                  total={quiz.questions.length}
                  answer={answers[currentQuestionId] ?? ""}
                  onAnswer={(value) =>
                    setAnswers((current) => ({
                      ...current,
                      [currentQuestionId]: value,
                    }))
                  }
                />

                <div className="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                  <button
                    type="button"
                    className="rounded-md border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-500 disabled:cursor-not-allowed disabled:opacity-50"
                    disabled={currentIndex === 0}
                    onClick={() =>
                      setCurrentIndex((index) => Math.max(0, index - 1))
                    }
                  >
                    {t("actions.previous")}
                  </button>
                  <div className="flex gap-3">
                    <button
                      type="button"
                      className="rounded-md border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-500 disabled:cursor-not-allowed disabled:opacity-50"
                      disabled={currentIndex === quiz.questions.length - 1}
                      onClick={() =>
                        setCurrentIndex((index) =>
                          Math.min(quiz.questions.length - 1, index + 1),
                        )
                      }
                    >
                      {t("actions.next")}
                    </button>
                    <button
                      type="button"
                      className="rounded-md bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                      disabled={isSubmitting}
                      onClick={handleSubmit}
                    >
                      {isSubmitting
                        ? t("actions.submitting")
                        : t("actions.submit")}
                    </button>
                  </div>
                </div>
              </div>

              <aside className="space-y-4">
                <QuizNav
                  total={quiz.questions.length}
                  currentIndex={currentIndex}
                  answeredIndexes={answeredIndexes}
                  onSelect={setCurrentIndex}
                />
                <div className="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
                  {t("status.answered", {
                    answered: answeredIndexes.size,
                    total: quiz.questions.length,
                  })}
                </div>
              </aside>
            </div>
          ) : null}
        </div>

        {showConfirm ? (
          <div
            className="fixed inset-0 z-50 flex items-end bg-slate-950/50 p-4 sm:items-center sm:justify-center"
            role="dialog"
            aria-modal="true"
            aria-labelledby="quiz-confirm-title"
          >
            <div className="w-full max-w-md rounded-lg bg-white p-5 shadow-xl">
              <h2
                id="quiz-confirm-title"
                className="text-lg font-semibold text-slate-950"
              >
                {t("confirm.title")}
              </h2>
              <p className="mt-2 text-sm leading-6 text-slate-600">
                {t("confirm.body")}
              </p>
              <div className="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button
                  type="button"
                  className="rounded-md border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700"
                  onClick={() => setShowConfirm(false)}
                >
                  {t("confirm.cancel")}
                </button>
                <button
                  type="button"
                  className="rounded-md bg-slate-950 px-4 py-3 text-sm font-semibold text-white"
                  onClick={() => void submitQuiz()}
                >
                  {t("confirm.submit")}
                </button>
              </div>
            </div>
          </div>
        ) : null}
      </main>
    </>
  );
}

interface StatePanelProps {
  title: string;
  body: string;
}

function StatePanel({ title, body }: StatePanelProps) {
  return (
    <section className="mx-auto mt-12 w-full max-w-xl rounded-lg border border-slate-200 bg-white p-6 text-center shadow-sm">
      <h2 className="text-xl font-semibold text-slate-950">{title}</h2>
      <p className="mt-3 text-sm leading-6 text-slate-600">{body}</p>
    </section>
  );
}

export const getServerSideProps: GetServerSideProps = async ({ locale }) => ({
  props: {
    ...(await serverSideTranslations(locale ?? "id", ["common", "quiz"])),
  },
});
