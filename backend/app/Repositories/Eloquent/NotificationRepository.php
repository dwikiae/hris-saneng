<?php

namespace App\Repositories\Eloquent;

use App\Models\Notification;
use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function __construct(private readonly Notification $model) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForUser(User $user, array $data): Notification
    {
        if ($user->company_id === null) {
            throw new InvalidArgumentException('notification.instance_admin_in_app_not_supported');
        }

        /** @var Notification $notification */
        $notification = $this->model->newQuery()->withoutGlobalScope('company')->create(array_merge($data, [
            'company_id' => (int) $user->company_id,
            'user_id' => (int) $user->getKey(),
        ]));

        return $notification;
    }

    /**
     * @return LengthAwarePaginator<int, Notification>
     */
    public function listForUser(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('company')
            ->where('user_id', $user->getKey())
            ->when($user->company_id !== null, fn ($query) => $query->where('company_id', $user->company_id))
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function findForUser(int $id, User $user): ?Notification
    {
        /** @var Notification|null $notification */
        $notification = $this->model->newQuery()
            ->withoutGlobalScope('company')
            ->where('id', $id)
            ->where('user_id', $user->getKey())
            ->when($user->company_id !== null, fn ($query) => $query->where('company_id', $user->company_id))
            ->first();

        return $notification;
    }

    public function markAsRead(Notification $notification): Notification
    {
        if ($notification->getAttribute('read_at') === null) {
            $notification->update(['read_at' => now()]);
        }

        return $notification->refresh();
    }

    public function markAllAsReadForUser(User $user): int
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('company')
            ->where('user_id', $user->getKey())
            ->when($user->company_id !== null, fn ($query) => $query->where('company_id', $user->company_id))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
