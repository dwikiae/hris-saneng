<?php

use App\Models\Company;
use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\ApplicantBlacklist;
use App\Models\Recruitment\ApplicantDocument;
use App\Models\Recruitment\ApplicantNote;
use App\Models\Recruitment\InterviewSchedule;
use App\Models\Recruitment\JobPosting;
use App\Models\Recruitment\QuizSession;
use App\Models\Recruitment\Test as RecruitmentTest;
use App\Models\Recruitment\TestQuestion;
use App\Models\User;
use App\Repositories\Contracts\Recruitment\ApplicantBlacklistRepositoryInterface;
use App\Repositories\Contracts\Recruitment\ApplicantDocumentRepositoryInterface;
use App\Repositories\Contracts\Recruitment\ApplicantNoteRepositoryInterface;
use App\Repositories\Contracts\Recruitment\ApplicantRepositoryInterface;
use App\Repositories\Contracts\Recruitment\InterviewScheduleRepositoryInterface;
use App\Repositories\Contracts\Recruitment\JobPostingRepositoryInterface;
use App\Repositories\Contracts\Recruitment\QuizSessionRepositoryInterface;
use App\Repositories\Contracts\Recruitment\TestRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function recruitmentRepositoryFixture(): array
{
    $company = Company::query()->create([
        'name' => 'PT Saneng',
        'legal_name' => 'PT Saneng',
    ]);

    config(['app.company_id' => $company->id, 'company.default_id' => $company->id]);

    $user = User::query()->create([
        'company_id' => $company->id,
        'name' => 'HR Admin',
        'email' => 'hr-admin@example.test',
        'password' => 'password',
    ]);

    $test = RecruitmentTest::query()->create([
        'company_id' => $company->id,
        'code' => 'TEST-001',
        'name' => 'General Ability Test',
        'duration_minutes' => 45,
        'passing_grade' => '70.00',
    ]);

    $question = TestQuestion::query()->create([
        'company_id' => $company->id,
        'test_id' => $test->id,
        'type' => 'multiple_choice',
        'question' => 'Question one',
        'pilihan' => ['A', 'B'],
        'answer_key' => 'A',
        'bobot' => '1.00',
    ]);

    $jobPosting = JobPosting::query()->create([
        'company_id' => $company->id,
        'test_id' => $test->id,
        'code' => 'JOB-001',
        'title' => 'Operator Produksi',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $applicant = Applicant::query()->create([
        'company_id' => $company->id,
        'job_posting_id' => $jobPosting->id,
        'application_number' => 'APP-001',
        'name' => 'Applicant One',
        'email' => 'applicant@example.test',
        'phone' => '6281234567890',
        'stage' => 'applied',
        'status' => 'active',
        'consent_at' => now(),
    ]);

    $quizSession = QuizSession::query()->create([
        'company_id' => $company->id,
        'applicant_id' => $applicant->id,
        'test_id' => $test->id,
        'token' => 'quiz-token',
        'status' => 'pending',
        'timer_snapshot' => 45,
        'passing_grade_snapshot' => '70.00',
    ]);

    $interviewSchedule = InterviewSchedule::query()->create([
        'company_id' => $company->id,
        'applicant_id' => $applicant->id,
        'job_posting_id' => $jobPosting->id,
        'interviewer_id' => $user->id,
        'token' => 'interview-token',
        'scheduled_at' => now()->addDay(),
    ]);

    $document = ApplicantDocument::query()->create([
        'company_id' => $company->id,
        'applicant_id' => $applicant->id,
        'document_type' => 'cv',
        'token' => 'document-token',
        'original_filename' => 'cv.pdf',
        'path' => 'recruitment/1/applicants/1/cv.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 1000,
    ]);

    $note = ApplicantNote::query()->create([
        'company_id' => $company->id,
        'applicant_id' => $applicant->id,
        'user_id' => $user->id,
        'stage' => 'screening',
        'note' => 'Initial screening note',
    ]);

    $blacklist = ApplicantBlacklist::query()->create([
        'company_id' => $company->id,
        'applicant_id' => $applicant->id,
        'status' => 'active',
        'reason' => 'Duplicate fraud evidence',
        'blacklisted_by' => $user->id,
        'blacklisted_at' => now(),
    ]);

    return compact(
        'company',
        'user',
        'test',
        'question',
        'jobPosting',
        'applicant',
        'quizSession',
        'interviewSchedule',
        'document',
        'note',
        'blacklist',
    );
}

it('queries job postings through the repository contract', function () {
    $data = recruitmentRepositoryFixture();

    /** @var JobPostingRepositoryInterface $repository */
    $repository = app(JobPostingRepositoryInterface::class);

    expect($repository->findById($data['jobPosting']->id)?->is($data['jobPosting']))->toBeTrue()
        ->and($repository->findByIdWithCriteria($data['jobPosting']->id)?->relationLoaded('criteria'))->toBeTrue()
        ->and($repository->listPublished($data['company']->id))->toHaveCount(1);
});

it('queries applicants and blacklist records through repository contracts', function () {
    $data = recruitmentRepositoryFixture();

    /** @var ApplicantRepositoryInterface $applicants */
    $applicants = app(ApplicantRepositoryInterface::class);

    /** @var ApplicantBlacklistRepositoryInterface $blacklists */
    $blacklists = app(ApplicantBlacklistRepositoryInterface::class);

    $found = $applicants->findByEmailOrWhatsapp('missing@example.test', '6281234567890', $data['company']->id);

    expect($applicants->findById($data['applicant']->id)?->is($data['applicant']))->toBeTrue()
        ->and($found?->is($data['applicant']))->toBeTrue()
        ->and($applicants->findBlacklisted('applicant@example.test', 'missing', $data['company']->id))->toBeInstanceOf(ApplicantBlacklist::class)
        ->and($applicants->listByJobPosting($data['jobPosting']->id, ['stage' => 'applied'])->total())->toBe(1)
        ->and($blacklists->findByEmailOrWhatsapp('missing@example.test', '6281234567890', $data['company']->id))->toBeInstanceOf(ApplicantBlacklist::class);

    $blacklists->delete($data['blacklist']);

    expect($data['blacklist']->refresh()->archived_at)->not->toBeNull();
});

it('queries tests and quiz sessions through repository contracts', function () {
    $data = recruitmentRepositoryFixture();

    /** @var TestRepositoryInterface $tests */
    $tests = app(TestRepositoryInterface::class);

    /** @var QuizSessionRepositoryInterface $sessions */
    $sessions = app(QuizSessionRepositoryInterface::class);

    expect($tests->findById($data['test']->id)?->is($data['test']))->toBeTrue()
        ->and($tests->findWithQuestions($data['test']->id)?->questions)->toHaveCount(1)
        ->and($tests->isLockedByPublishedPosting($data['test']->id, $data['company']->id))->toBeTrue()
        ->and($sessions->findByToken('quiz-token')?->is($data['quizSession']))->toBeTrue()
        ->and($sessions->findActiveByApplicant($data['applicant']->id)?->is($data['quizSession']))->toBeTrue();

    $sessions->markStarted($data['quizSession']);
    $sessions->markCompleted($data['quizSession']->refresh(), 88.5);
    $sessions->saveAnswers($data['quizSession']->refresh(), [[
        'test_question_id' => $data['question']->id,
        'answer' => 'A',
        'is_correct' => true,
        'score' => '1.00',
    ]]);

    expect($data['quizSession']->refresh()->status->value)->toBe('completed')
        ->and($data['quizSession']->answers()->count())->toBe(1);
});

it('queries schedules, documents, and notes through repository contracts', function () {
    $data = recruitmentRepositoryFixture();

    /** @var InterviewScheduleRepositoryInterface $schedules */
    $schedules = app(InterviewScheduleRepositoryInterface::class);

    /** @var ApplicantDocumentRepositoryInterface $documents */
    $documents = app(ApplicantDocumentRepositoryInterface::class);

    /** @var ApplicantNoteRepositoryInterface $notes */
    $notes = app(ApplicantNoteRepositoryInterface::class);

    expect($schedules->findByToken('interview-token')?->is($data['interviewSchedule']))->toBeTrue()
        ->and($schedules->findLatestByApplicant($data['applicant']->id)?->is($data['interviewSchedule']))->toBeTrue()
        ->and($documents->findByToken('document-token')?->is($data['document']))->toBeTrue()
        ->and($documents->listByApplicant($data['applicant']->id))->toHaveCount(1)
        ->and($notes->listByApplicant($data['applicant']->id))->toHaveCount(1);

    $schedules->updateConfirmation($data['interviewSchedule'], 'completed');
    $documents->upsert($data['applicant']->id, 'cv', [
        'company_id' => $data['company']->id,
        'token' => 'document-token-updated',
        'original_filename' => 'cv-updated.pdf',
        'path' => 'recruitment/1/applicants/1/cv-updated.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 2000,
    ]);
    $notes->create([
        'company_id' => $data['company']->id,
        'applicant_id' => $data['applicant']->id,
        'note' => 'Follow-up note',
    ]);

    expect($data['interviewSchedule']->refresh()->status->value)->toBe('completed')
        ->and($documents->listByApplicant($data['applicant']->id))->toHaveCount(1)
        ->and($notes->listByApplicant($data['applicant']->id))->toHaveCount(2);
});
