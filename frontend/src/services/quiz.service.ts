import { publicPath, requestJson } from "@/services/api-client";
import type { Answer, QuizSession } from "@/types/quiz";

export const quizService = {
  getQuiz: (token: string): Promise<QuizSession> =>
    requestJson<QuizSession>(publicPath(`/quiz/${encodeURIComponent(token)}`)),

  submitQuiz: (token: string, answers: Answer[]): Promise<unknown> =>
    requestJson<unknown>(publicPath(`/quiz/${encodeURIComponent(token)}/submit`), {
      method: "POST",
      body: JSON.stringify({ answers })
    })
};
