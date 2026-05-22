<?php

namespace App\Repositories\Contracts\Recruitment;

use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\ApplicantBlacklist;
use Illuminate\Pagination\LengthAwarePaginator;

interface ApplicantRepositoryInterface
{
    public function findById(int $id): ?Applicant;

    public function findByIdWithPipelineRelations(int $id): ?Applicant;

    public function findByEmailOrWhatsapp(string $email, string $whatsapp, int $companyId): ?Applicant;

    public function findBlacklisted(string $email, string $whatsapp, int $companyId): ?ApplicantBlacklist;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Applicant;

    public function updateStage(Applicant $applicant, string $stage, int $changedBy): void;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Applicant $applicant, array $data): Applicant;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listByJobPosting(int $jobPostingId, array $filters): LengthAwarePaginator;
}
