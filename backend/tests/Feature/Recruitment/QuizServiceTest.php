<?php

use App\Application\Recruitment\QuizService;
use App\Jobs\Recruitment\SendQuizEmailJob;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Position;
use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\JobPosting;
use App\Models\Recruitment\QuizSession;
use App\Models\Recruitment\Test as RecruitmentTest;
use App\Models\Recruitment\TestQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();

    $this->company = Company::query()->create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    config(['app.company_id' => $this->company->id, 'company.default_id' => $this->company->id]);

    $this->user = User::query()->create([
        'company_id' => $this->company->id,
        'name' => 'Quiz Sender',
        'email' => 'quiz-sender@example.test',
        'password' => 'password',
    ]);

    CompanySetting::query()->create([
        'company_id' => $this->company->id,
        'key' => 'recruitment_link_expires_hours',
        'value' => '24',
    ]);

    $this->position = Position::query()->create([
        'company_id' => $this->company->id,
        'code' => 'STAFF',
        'name' => 'Staff',
    ]);

    $this->test = RecruitmentTest::query()->create([
        'company_id' => $this->company->id,
        'code' => 'QUIZ-TEST',
        'name' => 'Quiz Test',
        'duration_minutes' => 30,
        'passing_grade' => '7.00',
    ]);

    $this->questionOne = TestQuestion::query()->create([
        'company_id' => $this->company->id,
        'test_id' => $this->test->id,
        'type' => 'multiple_choice',
        'question' => 'One',
        'pilihan' => ['A', 'B'],
        'answer_key' => 'A',
        'bobot' => '1.00',
    ]);

    $this->questionTwo = TestQuestion::query()->create([
        'company_id' => $this->company->id,
        'test_id' => $this->test->id,
        'type' => 'multiple_choice',
        'question' => 'Two',
        'pilihan' => ['A', 'B'],
        'answer_key' => 'B',
        'bobot' => '1.00',
    ]);

    $this->jobPosting = JobPosting::query()->create([
        'company_id' => $this->company->id,
        'position_id' => $this->position->id,
        'test_id' => $this->test->id,
        'code' => 'JOB-QUIZ',
        'title' => 'Operator',
        'status' => 'published',
    ]);

    $this->applicant = Applicant::query()->create([
        'company_id' => $this->company->id,
        'job_posting_id' => $this->jobPosting->id,
        'application_number' => 'APP-QUIZ',
        'name' => 'Quiz Applicant',
        'email' => 'quiz@example.test',
        'phone' => '628111',
        'stage' => 'tes_tulis',
        'status' => 'active',
        'consent_at' => now(),
    ]);
});

it('sends quiz links with snapshots and invalidates old active sessions', function () {
    $old = QuizSession::query()->create([
        'company_id' => $this->company->id,
        'applicant_id' => $this->applicant->id,
        'test_id' => $this->test->id,
        'token' => 'old-token',
        'status' => 'pending',
        'timer_snapshot' => 10,
        'passing_grade_snapshot' => '5.00',
        'expires_at' => now()->addHour(),
    ]);

    $session = app(QuizService::class)->sendQuizLink($this->applicant->id, $this->user->id);

    expect($old->refresh()->status->value)->toBe('expired')
        ->and($session->token)->not->toBeNull()
        ->and($session->timer_snapshot)->toBe(30)
        ->and($session->passing_grade_snapshot)->toBe('7.00');

    Queue::assertPushed(SendQuizEmailJob::class);
});

it('returns quiz questions without answer keys and starts pending session', function () {
    $session = app(QuizService::class)->sendQuizLink($this->applicant->id, $this->user->id);

    $quiz = app(QuizService::class)->getQuizForCandidate($session->token);

    expect($session->refresh()->status->value)->toBe('in_progress')
        ->and($quiz['questions'])->toHaveCount(2)
        ->and($quiz['questions'][0])->not->toHaveKey('answer_key');
});

it('passes quiz when score is greater than passing grade', function () {
    $session = app(QuizService::class)->sendQuizLink($this->applicant->id, $this->user->id);
    app(QuizService::class)->getQuizForCandidate($session->token);

    $completed = app(QuizService::class)->submitQuiz($session->token, [
        ['test_question_id' => $this->questionOne->id, 'answer' => 'A'],
        ['test_question_id' => $this->questionTwo->id, 'answer' => 'B'],
    ]);

    expect($completed->score)->toBe('10.00')
        ->and($this->applicant->refresh()->stage->value)->toBe('interview')
        ->and($completed->answers()->count())->toBe(2);
});

it('rejects quiz when score is less than or equal to passing grade', function () {
    $session = app(QuizService::class)->sendQuizLink($this->applicant->id, $this->user->id);
    app(QuizService::class)->getQuizForCandidate($session->token);

    $completed = app(QuizService::class)->submitQuiz($session->token, [
        ['test_question_id' => $this->questionOne->id, 'answer' => 'A'],
        ['test_question_id' => $this->questionTwo->id, 'answer' => 'A'],
    ]);

    expect($completed->score)->toBe('5.00')
        ->and($this->applicant->refresh()->stage->value)->toBe('rejected');
});

it('rejects expired tokens and active resend attempts', function () {
    $session = app(QuizService::class)->sendQuizLink($this->applicant->id, $this->user->id);

    expect(fn () => app(QuizService::class)->resendQuizLink($this->applicant->id, $this->user->id))
        ->toThrow(InvalidArgumentException::class, 'recruitment.quiz.link_still_active');

    $session->update(['expires_at' => now()->subMinute()]);

    expect(fn () => app(QuizService::class)->getQuizForCandidate($session->token))
        ->toThrow(InvalidArgumentException::class, 'recruitment.quiz.token_expired');
});
