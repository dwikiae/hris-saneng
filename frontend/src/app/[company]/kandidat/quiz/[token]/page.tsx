import { QuizPage } from "@/components/modules/recruitment/quiz/QuizPage";

interface QuizRouteProps {
  params: {
    token: string;
  };
}

export default function QuizRoutePage({ params }: QuizRouteProps) {
  return <QuizPage token={params.token} />;
}
