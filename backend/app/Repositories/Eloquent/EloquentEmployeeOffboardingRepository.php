<?php

namespace App\Repositories\Eloquent;

use App\Models\EmployeeOffboarding;
use App\Models\EmployeeOffboardingItem;
use App\Models\OffboardingTemplate;
use App\Repositories\Contracts\EmployeeOffboardingRepositoryInterface;

class EloquentEmployeeOffboardingRepository implements EmployeeOffboardingRepositoryInterface
{
    public function findTemplateByReason(int $companyId, int $terminationReasonId): ?OffboardingTemplate
    {
        /** @var OffboardingTemplate|null $template */
        $template = OffboardingTemplate::query()
            ->withoutGlobalScope('company')
            ->with(['items' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->where('company_id', $companyId)
            ->where('termination_reason_id', $terminationReasonId)
            ->where('is_active', true)
            ->first();

        return $template;
    }

    public function create(array $data): EmployeeOffboarding
    {
        /** @var EmployeeOffboarding $offboarding */
        $offboarding = EmployeeOffboarding::query()->create($data);

        return $offboarding->refresh()->load('items');
    }

    public function createItem(EmployeeOffboarding $offboarding, array $data): EmployeeOffboardingItem
    {
        /** @var EmployeeOffboardingItem $item */
        $item = $offboarding->items()->create($data);

        return $item;
    }

    public function findById(int $id): EmployeeOffboarding
    {
        /** @var EmployeeOffboarding $offboarding */
        $offboarding = EmployeeOffboarding::query()->with('items')->findOrFail($id);

        return $offboarding;
    }

    public function findItem(int $offboardingId, int $itemId): EmployeeOffboardingItem
    {
        /** @var EmployeeOffboardingItem $item */
        $item = EmployeeOffboardingItem::query()
            ->where('employee_offboarding_id', $offboardingId)
            ->findOrFail($itemId);

        return $item;
    }

    public function updateItem(EmployeeOffboardingItem $item, array $data): EmployeeOffboardingItem
    {
        $item->update($data);

        return $item->refresh();
    }

    public function hasIncompleteMandatoryItems(EmployeeOffboarding $offboarding): bool
    {
        return $offboarding->items()
            ->where('is_mandatory', true)
            ->where('is_done', false)
            ->exists();
    }

    public function update(EmployeeOffboarding $offboarding, array $data): EmployeeOffboarding
    {
        $offboarding->update($data);

        return $offboarding->refresh()->load('items');
    }
}
