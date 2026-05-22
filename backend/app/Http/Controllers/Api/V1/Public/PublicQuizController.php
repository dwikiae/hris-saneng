<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Application\Recruitment\ApplicantService;
use App\Application\Recruitment\QuizService;
use App\Http\Requests\Recruitment\SubmitQuizRequest;
use App\Http\Resources\Recruitment\QuizSessionResource;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class PublicQuizController extends PublicController
{
    public function __construct(
        private readonly QuizService $quiz,
        private readonly ApplicantService $applicants
    ) {}

    public function show(string $token): JsonResponse
    {
        try {
            return $this->success(QuizSessionResource::make($this->quiz->getQuizForCandidate($token)), 'recruitment.quiz.detail');
        } catch (InvalidArgumentException $exception) {
            return $this->fail($exception);
        }
    }

    public function submit(SubmitQuizRequest $request, string $token): JsonResponse
    {
        $answers = collect($request->validated('answers'))->map(fn (array $answer): array => [
            'test_question_id' => $answer['test_question_id'] ?? $answer['question_id'],
            'answer' => $answer['answer'] ?? $answer['jawaban'],
        ])->all();

        try {
            return $this->success($this->applicants->submitQuizAnswers($token, $answers), 'recruitment.quiz.submitted');
        } catch (InvalidArgumentException $exception) {
            return $this->fail($exception);
        }
    }
}
