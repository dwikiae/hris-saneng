<?php

namespace App\Http\Controllers\Api\V1\Core;

use App\Application\Instance\InstanceAuditService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instance\ExportInstanceAuditRequest;
use App\Http\Requests\Instance\ListInstanceAuditRequest;
use App\Http\Resources\InstanceAuditResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class InstanceAuditController extends Controller
{
    public function __construct(private readonly InstanceAuditService $audits) {}

    public function index(ListInstanceAuditRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $paginator = $this->audits->list($request->validated(), $request->integer('per_page', 20));
        $canViewSensitive = $this->audits->canViewSensitive($user);
        $meta = [
            'total' => $paginator->total(),
            'today' => $this->audits->list(['date_from' => now()->toDateString(), 'date_to' => now()->toDateString()], 1)->total(),
            'this_week' => $this->audits->list(['date_from' => now()->startOfWeek()->toDateString(), 'date_to' => now()->endOfWeek()->toDateString()], 1)->total(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
        ];

        return $this->success([
            'items' => $paginator->getCollection()
                ->map(fn ($activity): array => InstanceAuditResource::make($activity, $canViewSensitive)->resolve($request))
                ->values()
                ->all(),
            'meta' => $meta,
        ], 'instance.audit.list', 200, $meta);
    }

    public function export(ExportInstanceAuditRequest $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $export = $this->audits->export($request->validated(), $user);

        return response($export['content'], 200, [
            'Content-Type' => $export['content_type'],
            'Content-Disposition' => 'attachment; filename="'.$export['filename'].'"',
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function success(mixed $data, string $message, int $status = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'meta' => $meta,
        ], $status);
    }
}
