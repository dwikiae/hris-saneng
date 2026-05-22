<?php

use App\Enums\Recruitment\ApplicantDocumentType;
use App\Enums\Recruitment\ApplicantSource;
use App\Enums\Recruitment\ApplicantStage;
use App\Enums\Recruitment\ApplicantStatus;
use App\Enums\Recruitment\InterviewScheduleStatus;
use App\Enums\Recruitment\JobPostingStatus;
use App\Enums\Recruitment\QuizSessionStatus;
use App\Enums\Recruitment\TestQuestionType;
use App\Models\Company;
use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\ApplicantBlacklist;
use App\Models\Recruitment\ApplicantDocument;
use App\Models\Recruitment\ApplicantEducation;
use App\Models\Recruitment\ApplicantExperience;
use App\Models\Recruitment\ApplicantNote;
use App\Models\Recruitment\ApplicantStageAttachment;
use App\Models\Recruitment\InterviewSchedule;
use App\Models\Recruitment\JobPosting;
use App\Models\Recruitment\JobPostingCriterion;
use App\Models\Recruitment\QuizAnswer;
use App\Models\Recruitment\QuizSession;
use App\Models\Recruitment\Test as RecruitmentTest;
use App\Models\Recruitment\TestQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates one recruitment record for each migration-backed model', function () {
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
        'type' => TestQuestionType::MultipleChoice,
        'question' => 'Question one',
        'pilihan' => ['A', 'B', 'C', 'D'],
        'answer_key' => 'A',
        'bobot' => '1.00',
    ]);

    $jobPosting = JobPosting::query()->create([
        'company_id' => $company->id,
        'test_id' => $test->id,
        'code' => 'JOB-001',
        'title' => 'Operator Produksi',
        'quota' => 2,
        'status' => JobPostingStatus::Draft,
    ]);

    JobPostingCriterion::query()->create([
        'company_id' => $company->id,
        'job_posting_id' => $jobPosting->id,
        'code' => 'EXP',
        'name' => 'Experience',
        'bobot' => '1.00',
    ]);

    $applicant = Applicant::query()->create([
        'company_id' => $company->id,
        'job_posting_id' => $jobPosting->id,
        'application_number' => 'APP-001',
        'name' => 'Applicant One',
        'email' => 'applicant@example.test',
        'nik' => '3200000000000001',
        'source' => ApplicantSource::Website,
        'stage' => ApplicantStage::Applied,
        'status' => ApplicantStatus::Active,
        'consent_at' => now(),
    ]);

    ApplicantEducation::query()->create([
        'company_id' => $company->id,
        'applicant_id' => $applicant->id,
        'institution_name' => 'Universitas Contoh',
        'gpa' => '3.50',
    ]);

    ApplicantExperience::query()->create([
        'company_id' => $company->id,
        'applicant_id' => $applicant->id,
        'company_name' => 'PT Lama',
        'position' => 'Staff',
    ]);

    $quizSession = QuizSession::query()->create([
        'company_id' => $company->id,
        'applicant_id' => $applicant->id,
        'test_id' => $test->id,
        'status' => QuizSessionStatus::Pending,
        'timer_snapshot' => 45,
        'passing_grade_snapshot' => '70.00',
    ]);

    QuizAnswer::query()->create([
        'company_id' => $company->id,
        'quiz_session_id' => $quizSession->id,
        'test_question_id' => $question->id,
        'answer' => 'A',
        'is_correct' => true,
        'score' => '1.00',
    ]);

    InterviewSchedule::query()->create([
        'company_id' => $company->id,
        'applicant_id' => $applicant->id,
        'job_posting_id' => $jobPosting->id,
        'interviewer_id' => $user->id,
        'scheduled_at' => now()->addDay(),
        'status' => InterviewScheduleStatus::Scheduled,
        'timer_snapshot' => 60,
        'passing_grade_snapshot' => '70.00',
    ]);

    ApplicantDocument::query()->create([
        'company_id' => $company->id,
        'applicant_id' => $applicant->id,
        'document_type' => ApplicantDocumentType::Cv,
        'original_filename' => 'cv.pdf',
        'path' => 'recruitment/1/applicants/1/cv.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 1000,
    ]);

    ApplicantNote::query()->create([
        'company_id' => $company->id,
        'applicant_id' => $applicant->id,
        'user_id' => $user->id,
        'stage' => ApplicantStage::Screening,
        'note' => 'Screening note',
    ]);

    ApplicantStageAttachment::query()->create([
        'company_id' => $company->id,
        'applicant_id' => $applicant->id,
        'stage' => ApplicantStage::Screening,
        'original_filename' => 'screening.pdf',
        'path' => 'recruitment/1/applicants/1/screening.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 1000,
    ]);

    ApplicantBlacklist::query()->create([
        'company_id' => $company->id,
        'applicant_id' => $applicant->id,
        'status' => 'active',
        'reason' => 'Duplicate fraud evidence',
        'blacklisted_by' => $user->id,
        'blacklisted_at' => now(),
    ]);

    expect($test->jobPostings()->count())->toBe(1)
        ->and($jobPosting->test()->is($test))->toBeTrue()
        ->and($applicant->educations()->count())->toBe(1)
        ->and($applicant->experiences()->count())->toBe(1)
        ->and($applicant->quizSessions()->count())->toBe(1)
        ->and($applicant->notes()->count())->toBe(1)
        ->and($applicant->stageAttachments()->count())->toBe(1)
        ->and($applicant->blacklist)->toBeInstanceOf(ApplicantBlacklist::class)
        ->and($applicant->interviewSchedules()->count())->toBe(1)
        ->and($applicant->documents()->count())->toBe(1);
});
