export type QuestionType =
  | "multiple_choice"
  | "short_answer"
  | "pilihan_ganda"
  | "isian_singkat";

export interface Question {
  id: number;
  question_id?: number;
  test_question_id?: number;
  type?: QuestionType;
  tipe?: QuestionType;
  question?: string;
  pertanyaan?: string;
  options?: string[] | Record<string, string>;
  pilihan?: string[] | Record<string, string>;
  bobot?: number;
}

export interface QuizSession {
  token: string;
  timer: number;
  questions: Question[];
}

export interface Answer {
  question_id: number;
  jawaban: string;
}

export interface ApiResponse<T> {
  success: boolean;
  data?: T;
  message: string;
  errors?: Record<string, string[]>;
}
