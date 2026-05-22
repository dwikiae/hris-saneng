<?php

namespace App\Repositories\Eloquent\Recruitment;

use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\ApplicantBlacklist;
use App\Repositories\Contracts\Recruitment\ApplicantRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;

class ApplicantRepository implements ApplicantRepositoryInterface
{
    public function __construct(
        private readonly Applicant $model,
        private readonly ApplicantBlacklist $blacklist
    ) {}

    public function findById(int $id): ?Applicant
    {
        /** @var Applicant|null $applicant */
        $applicant = $this->model->newQuery()
            ->where('company_id', $this->defaultCompanyId())
            ->where('id', $id)
            ->first();

        return $applicant;
    }

    public function findByIdWithPipelineRelations(int $id): ?Applicant
    {
        /** @var Applicant|null $applicant */
        $applicant = $this->model->newQuery()
            ->with(['jobPosting.position', 'jobPosting.test.questions', 'blacklist', 'educations', 'experiences'])
            ->where('company_id', $this->defaultCompanyId())
            ->where('id', $id)
            ->first();

        return $applicant;
    }

    public function findByEmailOrWhatsapp(string $email, string $whatsapp, int $companyId): ?Applicant
    {
        /** @var Applicant|null $applicant */
        $applicant = $this->model->newQuery()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where(function (Builder $query) use ($email, $whatsapp): void {
                $query->where('email', $email)
                    ->orWhere('phone', $whatsapp);
            })
            ->first();

        return $applicant;
    }

    public function findBlacklisted(string $email, string $whatsapp, int $companyId): ?ApplicantBlacklist
    {
        /** @var ApplicantBlacklist|null $blacklist */
        $blacklist = $this->blacklist->newQuery()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->whereHas('applicant', function (Builder $query) use ($email, $whatsapp, $companyId): void {
                $query->withoutGlobalScope('company')
                    ->where('company_id', $companyId)
                    ->where(function (Builder $query) use ($email, $whatsapp): void {
                        $query->where('email', $email)
                            ->orWhere('phone', $whatsapp);
                    });
            })
            ->first();

        return $blacklist;
    }

    public function create(array $data): Applicant
    {
        /** @var Applicant $applicant */
        $applicant = $this->model->newQuery()->create($data);

        return $applicant;
    }

    public function updateStage(Applicant $applicant, string $stage, int $changedBy): void
    {
        $applicant->update([
            'stage' => $stage,
            'updated_by' => $changedBy,
        ]);
    }

    public function update(Applicant $applicant, array $data): Applicant
    {
        $applicant->update($data);

        return $applicant->refresh();
    }

    public function listByJobPosting(int $jobPostingId, array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->where('company_id', $this->defaultCompanyId())
            ->where('job_posting_id', $jobPostingId)
            ->orderByDesc('created_at');

        $this->applyFilters($query, $filters);

        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }

    /**
     * @param  Builder<Applicant>|QueryBuilder  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder|QueryBuilder $query, array $filters): void
    {
        if (array_key_exists('stage', $filters)) {
            $query->where('stage', (string) $filters['stage']);
        }

        if (array_key_exists('status', $filters)) {
            $query->where('status', (string) $filters['status']);
        }

        if (! array_key_exists('search', $filters) || $filters['search'] === null || $filters['search'] === '') {
            return;
        }

        $search = (string) $filters['search'];

        $query->where(function (Builder $query) use ($search): void {
            $query->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('phone', 'like', '%'.$search.'%');
        });
    }

    private function defaultCompanyId(): int
    {
        return (int) config('app.company_id');
    }
}
