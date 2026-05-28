<?php

namespace App\Http\Controllers\Api\V1\Audit;

use App\Http\Controllers\Controller;
use App\Models\Concerns\InteractsWithLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;

class AuditController extends Controller
{
    private const REDACTED = '[REDACTED]';

    /**
     * @var array<int, string>
     */
    private array $permissionedSensitiveFields = [
        'salary',
        'allowances',
        'deductions',
    ];

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('audit.view');

        $query = Activity::query()->latest('created_at');

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->string('subject_type')->toString());
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->integer('causer_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        $records = $query->paginate(20)->through(
            fn (Activity $activity): array => $this->transformActivity($activity)
        );

        return response()->json([
            'success' => true,
            'data' => $records,
            'message' => 'audit.list',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformActivity(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'who' => [
                'type' => $activity->causer_type,
                'id' => $activity->causer_id,
            ],
            'when' => $activity->created_at?->toISOString(),
            'subject' => [
                'type' => $activity->subject_type,
                'id' => $activity->subject_id,
            ],
            'event' => $activity->event,
            'description' => $activity->description,
            'changes' => $this->redactChanges($activity->properties?->toArray() ?? []),
            'ip_address' => $activity->getExtraProperty('ip_address'),
        ];
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function redactChanges(array $changes): array
    {
        foreach (['attributes', 'old'] as $bucket) {
            if (! is_array($changes[$bucket] ?? null)) {
                continue;
            }

            foreach (InteractsWithLog::sensitiveAuditFields() as $field) {
                if (! array_key_exists($field, $changes[$bucket])) {
                    continue;
                }

                if ($this->canViewSensitiveField($field)) {
                    continue;
                }

                $changes[$bucket][$field] = self::REDACTED;
            }
        }

        return $changes;
    }

    private function canViewSensitiveField(string $field): bool
    {
        if (! in_array($field, $this->permissionedSensitiveFields, true)) {
            return false;
        }

        return Gate::allows('employee.view_salary');
    }
}
