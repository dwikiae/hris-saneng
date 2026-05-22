<?php

namespace App\Repositories\Eloquent\Recruitment;

use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\ApplicantBlacklist;
use App\Repositories\Contracts\Recruitment\ApplicantBlacklistRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ApplicantBlacklistRepository implements ApplicantBlacklistRepositoryInterface
{
    public function __construct(private readonly ApplicantBlacklist $model) {}

    public function findById(int $id): ?ApplicantBlacklist
    {
        /** @var ApplicantBlacklist|null $blacklist */
        $blacklist = $this->model->newQuery()
            ->where('company_id', (int) config('app.company_id'))
            ->where('id', $id)
            ->first();

        return $blacklist;
    }

    public function findByEmailOrWhatsapp(string $email, string $whatsapp, int $companyId): ?ApplicantBlacklist
    {
        /** @var ApplicantBlacklist|null $blacklist */
        $blacklist = $this->model->newQuery()
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

    public function paginate(int $companyId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = $this->query()
            ->with('applicant')
            ->where('company_id', $companyId)
            ->orderByDesc('blacklisted_at');

        if (array_key_exists('status', $filters)) {
            $query->where('status', (string) $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->whereIn(
                'applicant_id',
                Applicant::query()
                    ->withoutGlobalScope('company')
                    ->select('id')
                    ->where('company_id', $companyId)
                    ->where(function (Builder $query) use ($search): void {
                        $query->where('email', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%')
                            ->orWhere('name', 'like', '%'.$search.'%');
                    })
            );
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): ApplicantBlacklist
    {
        /** @var ApplicantBlacklist $blacklist */
        $blacklist = $this->model->newQuery()->create($data);

        return $blacklist;
    }

    public function delete(ApplicantBlacklist $blacklist): void
    {
        $this->archive($blacklist);
    }

    public function archive(ApplicantBlacklist $blacklist): void
    {
        $blacklist->update([
            'archived_at' => now(),
            'archived_by' => Auth::id(),
        ]);
    }

    /**
     * @return Builder<ApplicantBlacklist>
     */
    private function query(): Builder
    {
        return $this->model->newQuery();
    }
}
