<?php

use App\Models\Company;
use App\Models\Position;
use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\JobPosting;
use App\Models\Recruitment\QuizSession;
use App\Models\Recruitment\Test as RecruitmentTest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 422 for expired public quiz token', function () {
    $company = Company::query()->create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    config(['app.company_id' => $company->id, 'company.default_id' => $company->id]);

    $position = Position::query()->create([
        'company_id' => $company->id,
        'code' => 'STAFF',
        'name' => 'Staff',
    ]);
    $test = RecruitmentTest::query()->create([
        'company_id' => $company->id,
        'code' => 'QUIZ-PUBLIC',
        'name' => 'Quiz Public',
        'duration_minutes' => 30,
        'passing_grade' => '7.00',
    ]);
    $jobPosting = JobPosting::query()->create([
        'company_id' => $company->id,
        'position_id' => $position->id,
        'test_id' => $test->id,
        'code' => 'JOB-PUBLIC',
        'title' => 'Staff',
        'status' => 'published',
    ]);
    $applicant = Applicant::query()->create([
        'company_id' => $company->id,
        'job_posting_id' => $jobPosting->id,
        'application_number' => 'APP-PUBLIC',
        'name' => 'Public Applicant',
        'email' => 'public@example.test',
        'phone' => '628123',
        'stage' => 'tes_tulis',
        'status' => 'active',
        'consent_at' => now(),
    ]);

    QuizSession::query()->create([
        'company_id' => $company->id,
        'applicant_id' => $applicant->id,
        'test_id' => $test->id,
        'token' => 'expired-public-token',
        'status' => 'pending',
        'timer_snapshot' => 30,
        'passing_grade_snapshot' => '7.00',
        'expires_at' => now()->subMinute(),
    ]);

    $this->getJson('/api/v1/public/quiz/expired-public-token')
        ->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'recruitment.quiz.token_expired',
        ]);
});
