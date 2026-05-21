<?php

namespace App\Http\Controllers\Api\V1\Audit;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;

class AuditController extends Controller
{
    /**
     * @var array<int, string>
     */
    private array $sensitiveFields = [
        'salary',
        'allowances',
        'deductions',
        'nik',
        'npwp',
        'bank_account_number',
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

        $canViewSensitive = Gate::allows('employee.view_salary');

        $records = $query->paginate(20)->through(
            fn (Activity $activity): array => $this->transformActivity($activity, $canViewSensitive)
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
    private function transformActivity(Activity $activity, bool $canViewSensitive): array
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
            'changes' => $this->redactChanges($activity->properties?->toArray() ?? [], $canViewSensitive),
            'ip_address' => $activity->getExtraProperty('ip_address'),
        ];
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function redactChanges(array $changes, bool $canViewSensitive): array
    {
        if ($canViewSensitive) {
            return $changes;
        }

        foreach (['attributes', 'old'] as $bucket) {
            if (! is_array($changes[$bucket] ?? null)) {
                continue;
            }

            foreach ($this->sensitiveFields as $field) {
                if (array_key_exists($field, $changes[$bucket])) {
                    $changes[$bucket][$field] = '[REDACTED]';
                }
            }
        }

        return $changes;
    }
}
