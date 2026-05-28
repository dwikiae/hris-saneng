<?php

namespace App\Repositories\Contracts;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NotificationRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createForUser(User $user, array $data): Notification;

    /**
     * @return LengthAwarePaginator<int, Notification>
     */
    public function listForUser(User $user, int $perPage = 20): LengthAwarePaginator;

    public function findForUser(int $id, User $user): ?Notification;

    public function markAsRead(Notification $notification): Notification;

    public function markAllAsReadForUser(User $user): int;
}
