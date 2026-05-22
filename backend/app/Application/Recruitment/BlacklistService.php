<?php

namespace App\Application\Recruitment;

use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\ApplicantBlacklist;
use App\Repositories\Contracts\Recruitment\ApplicantBlacklistRepositoryInterface;
use App\Repositories\Contracts\Recruitment\ApplicantRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BlacklistService
{
    public function __construct(
        private readonly ApplicantRepositoryInterface $applicants,
        private readonly ApplicantBlacklistRepositoryInterface $blacklists
    ) {}

    public function blacklist(int $applicantId, int $actorId, ?string $alasan = null): ApplicantBlacklist
    {
        Gate::authorize('recruitment.applicant.blacklist');

        return DB::transaction(function () use ($applicantId, $actorId, $alasan): ApplicantBlacklist {
            $applicant = $this->findApplicant($applicantId);

            $blacklist = $this->blacklists->create([
                'company_id' => (int) config('app.company_id'),
                'applicant_id' => $applicant->getKey(),
                'status' => 'active',
                'reason' => $alasan ?? 'recruitment.blacklist.no_reason',
                'blacklisted_by' => $actorId,
                'blacklisted_at' => now(),
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->applicants->update($applicant, [
                'is_blacklisted' => true,
                'status' => 'blacklisted',
                'updated_by' => $actorId,
            ]);

            return $blacklist;
        });
    }

    public function unblacklist(int $applicantId, int $actorId): void
    {
        Gate::authorize('recruitment.blacklist.manage');

        DB::transaction(function () use ($applicantId, $actorId): void {
            $applicant = $this->findApplicant($applicantId);
            $blacklist = $this->blacklists->findByEmailOrWhatsapp(
                (string) $applicant->getAttribute('email'),
                (string) $applicant->getAttribute('phone'),
                (int) $applicant->getAttribute('company_id')
            );

            if ($blacklist instanceof ApplicantBlacklist) {
                $this->blacklists->archive($blacklist);
            }

            $this->applicants->update($applicant, [
                'is_blacklisted' => false,
                'status' => 'active',
                'updated_by' => $actorId,
            ]);
        });
    }

    public function isBlacklisted(string $email, string $whatsappNumber): bool
    {
        return $this->blacklists->findByEmailOrWhatsapp($email, $whatsappNumber, (int) config('app.company_id')) instanceof ApplicantBlacklist;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function index(array $filters = []): LengthAwarePaginator
    {
        return $this->blacklists->paginate(
            (int) config('app.company_id'),
            $filters,
            (int) ($filters['per_page'] ?? 15)
        );
    }

    private function findApplicant(int $applicantId): Applicant
    {
        $applicant = $this->applicants->findByIdWithPipelineRelations($applicantId);

        if ($applicant === null) {
            throw (new ModelNotFoundException)->setModel(Applicant::class, $applicantId);
        }

        return $applicant;
    }
}
