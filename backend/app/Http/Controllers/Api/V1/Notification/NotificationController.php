<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\ListNotificationsRequest;
use App\Http\Requests\Notification\MarkNotificationReadRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationRepositoryInterface $notifications) {}

    public function index(ListNotificationsRequest $request): JsonResponse
    {
        $user = $this->currentUser($request);
        $records = $this->notifications->listForUser($user, $request->integer('per_page', 20));

        return $this->success($this->transformPaginator($records, $request), 'notification.list');
    }

    public function markRead(MarkNotificationReadRequest $request, int $notification): JsonResponse
    {
        $record = $this->notifications->findForUser($notification, $this->currentUser($request));

        if (! $record instanceof Notification) {
            return response()->json(['success' => false, 'message' => 'notification.not_found'], 404);
        }

        return $this->success(
            NotificationResource::make($this->notifications->markAsRead($record))->resolve($request),
            'notification.marked_read'
        );
    }

    public function markAllRead(MarkNotificationReadRequest $request): JsonResponse
    {
        $updated = $this->notifications->markAllAsReadForUser($this->currentUser($request));

        return $this->success(['updated' => $updated], 'notification.marked_all_read');
    }

    private function currentUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    /**
     * @param  LengthAwarePaginator<int, Notification>  $records
     * @return array<string, mixed>
     */
    private function transformPaginator(LengthAwarePaginator $records, Request $request): array
    {
        return [
            'items' => NotificationResource::collection($records->items())->resolve($request),
            'meta' => [
                'current_page' => $records->currentPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
        ];
    }

    private function success(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data, 'message' => $message], $status);
    }
}
