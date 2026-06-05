<?php

namespace App\Repositories\Eloquent;

use App\Models\EmployeeOffboarding;
use App\Models\OffboardingChecklistItem;
use App\Repositories\Contracts\OffboardingChecklistRepositoryInterface;
use App\Services\ArchiveService;
use Illuminate\Database\Eloquent\Collection;

class OffboardingChecklistRepository implements OffboardingChecklistRepositoryInterface
{
    public function __construct(private readonly ArchiveService $archiveService) {}

    /**
     * @return Collection<int, OffboardingChecklistItem>
     */
    public function listForOffboarding(EmployeeOffboarding $offboarding): Collection
    {
        return $offboarding->checklistItems()
            ->with(['assignee', 'completer'])
            ->orderBy('order')
            ->orderBy('title')
            ->get();
    }

    public function findForOffboarding(EmployeeOffboarding $offboarding, int $itemId): OffboardingChecklistItem
    {
        /** @var OffboardingChecklistItem $item */
        $item = $offboarding->checklistItems()
            ->with(['assignee', 'completer'])
            ->findOrFail($itemId);

        return $item;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForOffboarding(EmployeeOffboarding $offboarding, array $data): OffboardingChecklistItem
    {
        /** @var OffboardingChecklistItem $item */
        $item = $offboarding->checklistItems()->create(array_merge($data, [
            'company_id' => $offboarding->getAttribute('company_id'),
        ]));

        return $item->load(['assignee', 'completer']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(OffboardingChecklistItem $item, array $data): OffboardingChecklistItem
    {
        $item->update($data);

        return $item->refresh()->load(['assignee', 'completer']);
    }

    /**
     * @return Collection<int, OffboardingChecklistItem>
     */
    public function incompleteForOffboarding(EmployeeOffboarding $offboarding): Collection
    {
        return $offboarding->checklistItems()
            ->where('is_completed', false)
            ->orderBy('order')
            ->orderBy('title')
            ->get();
    }

    public function archive(OffboardingChecklistItem $item): void
    {
        $this->archiveService->archive($item);
    }
}
