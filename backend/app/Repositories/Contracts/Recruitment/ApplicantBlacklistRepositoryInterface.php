<?php

namespace App\Repositories\Contracts\Recruitment;

use App\Models\Recruitment\ApplicantBlacklist;
use Illuminate\Pagination\LengthAwarePaginator;

interface ApplicantBlacklistRepositoryInterface
{
    public function findById(int $id): ?ApplicantBlacklist;

    public function findByEmailOrWhatsapp(string $email, string $whatsapp, int $companyId): ?ApplicantBlacklist;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $companyId, array $filters, int $perPage): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ApplicantBlacklist;

    public function delete(ApplicantBlacklist $blacklist): void;

    public function archive(ApplicantBlacklist $blacklist): void;
}
