<?php

namespace App\Application\Employee;

use App\Models\EmployeeOffboardingItem;
use App\Repositories\Contracts\EmployeeOffboardingRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class UpdateOffboardingItemService
{
    public function __construct(private readonly EmployeeOffboardingRepositoryInterface $offboarding) {}

    public function execute(int $offboardingId, int $itemId, bool $isDone, ?string $notes = null): EmployeeOffboardingItem
    {
        Gate::authorize('offboarding.manage');

        return $this->offboarding->updateItem(
            $this->offboarding->findItem($offboardingId, $itemId),
            [
                'is_done' => $isDone,
                'done_by' => $isDone ? Auth::id() : null,
                'done_at' => $isDone ? now() : null,
                'notes' => $notes,
            ]
        );
    }
}
