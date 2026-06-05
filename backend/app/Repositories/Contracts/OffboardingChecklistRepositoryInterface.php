<?php

namespace App\Repositories\Contracts;

use App\Models\EmployeeOffboarding;
use App\Models\OffboardingChecklistItem;
use Illuminate\Database\Eloquent\Collection;

interface OffboardingChecklistRepositoryInterface
{
    /**
     * @return Collection<int, OffboardingChecklistItem>
     */
    public function listForOffboarding(EmployeeOffboarding $offboarding): Collection;

    public function findForOffboarding(EmployeeOffboarding $offboarding, int $itemId): OffboardingChecklistItem;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForOffboarding(EmployeeOffboarding $offboarding, array $data): OffboardingChecklistItem;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(OffboardingChecklistItem $item, array $data): OffboardingChecklistItem;

    /**
     * @return Collection<int, OffboardingChecklistItem>
     */
    public function incompleteForOffboarding(EmployeeOffboarding $offboarding): Collection;

    public function archive(OffboardingChecklistItem $item): void;
}
