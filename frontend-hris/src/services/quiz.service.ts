import type { Answer, ApiResponse, QuizSession } from "@/types/quiz";

const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL ?? "/api/v1";

async function request<T>(path: string, init?: RequestInit): Promise<T> {
  const response = await fetch(`${apiBaseUrl}${path}`, {
    ...init,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...init?.headers,
    },
  });

  const payload = (await response.json()) as ApiResponse<T>;

  if (!response.ok || !payload.success) {
    throw new Error(payload.message);
  }

  if (payload.data === undefined) {
    throw new Error("error.empty_response");
  }

  if (
    payload.data &&
    typeof payload.data === "object" &&
    "data" in payload.data
  ) {
    return (payload.data as { data: T }).data;
  }

  return payload.data;
}

export const quizService = {
  getQuiz: (token: string): Promise<QuizSession> =>
    request<QuizSession>(`/public/quiz/${encodeURIComponent(token)}`),

  submitQuiz: (token: string, answers: Answer[]): Promise<unknown> =>
    request<unknown>(`/public/quiz/${encodeURIComponent(token)}/submit`, {
      method: "POST",
      body: JSON.stringify({ answers }),
    }),
};
