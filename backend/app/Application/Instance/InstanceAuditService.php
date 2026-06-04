<?php

namespace App\Application\Instance;

use App\Models\Concerns\InteractsWithLog;
use App\Models\User;
use App\Repositories\Contracts\InstanceAuditRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class InstanceAuditService
{
    private const REDACTED = '[REDACTED]';

    /**
     * @var array<int, string>
     */
    private array $filterKeys = [
        'date_from',
        'date_to',
        'causer_id',
        'log_name',
        'event',
        'actor',
        'module',
        'action',
        'search',
    ];

    public function __construct(
        private readonly InstanceAuditRepositoryInterface $audits,
        private readonly InstanceAuditExportWriter $writer
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->audits->paginate($this->normalizeFilters($filters), $perPage);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{filename: string, content_type: string, content: string, record_count: int}
     */
    public function export(array $payload, User $actor): array
    {
        $format = (string) $payload['format'];
        $filters = $this->normalizeExportFilters($payload);
        $records = $this->audits->allForExport($filters);
        $rows = $this->exportRows($records, $actor);
        $filename = $this->filename((string) ($payload['filename'] ?? 'audit-log'), $format);
        $content = $format === 'xlsx' ? $this->writer->xlsx($rows) : $this->writer->csv($rows);

        activity('instance')
            ->causedBy($actor)
            ->event('exported')
            ->withProperties([
                'format' => $format,
                'filters' => $filters,
                'record_count' => $records->count(),
                'exported_at' => now()->toISOString(),
            ])
            ->log('instance.audit.exported');

        return [
            'filename' => $filename,
            'content_type' => $format === 'xlsx'
                ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                : 'text/csv; charset=UTF-8',
            'content' => $content,
            'record_count' => $records->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function transform(mixed $resource, bool $canViewSensitive): array
    {
        /** @var Activity $activity */
        $activity = $resource;

        return [
            'id' => $activity->id,
            'createdAt' => $activity->created_at?->toISOString(),
            'actor' => $this->actorName($activity),
            'action' => (string) ($activity->event ?? $this->actionFromDescription((string) $activity->description)),
            'module' => $activity->log_name,
            'entity' => $this->subjectName($activity),
            'detail' => $activity->description,
            'diffs' => $this->diffs($activity->properties?->toArray() ?? [], $canViewSensitive),
        ];
    }

    public function canViewSensitive(User $user): bool
    {
        return $user->permissions()
            ->contains(fn ($permission): bool => $permission->code === 'audit.view_sensitive');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function normalizeFilters(array $filters): array
    {
        $normalized = [];

        foreach ($this->filterKeys as $key) {
            if (($filters[$key] ?? '') !== '') {
                $normalized[$key] = $filters[$key];
            }
        }

        if (($normalized['module'] ?? '') !== '' && ($normalized['log_name'] ?? '') === '') {
            $normalized['log_name'] = $normalized['module'];
        }

        if (($normalized['action'] ?? '') !== '' && ($normalized['event'] ?? '') === '') {
            $normalized['event'] = $normalized['action'];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeExportFilters(array $payload): array
    {
        $filters = is_array($payload['filters'] ?? null) ? $payload['filters'] : $payload;

        return $this->normalizeFilters($filters);
    }

    private function actorName(Activity $activity): ?string
    {
        $causer = $activity->causer;

        if ($causer instanceof User) {
            return $causer->name.' <'.$causer->email.'>';
        }

        return $activity->causer_type !== null && $activity->causer_id !== null
            ? class_basename((string) $activity->causer_type).' #'.$activity->causer_id
            : null;
    }

    private function subjectName(Activity $activity): ?string
    {
        $subject = $activity->subject;

        if (is_object($subject)) {
            foreach (['name', 'title', 'email', 'code'] as $field) {
                if (isset($subject->{$field}) && (string) $subject->{$field} !== '') {
                    return class_basename($subject).' - '.(string) $subject->{$field};
                }
            }
        }

        return $activity->subject_type !== null && $activity->subject_id !== null
            ? class_basename((string) $activity->subject_type).' #'.$activity->subject_id
            : null;
    }

    private function actionFromDescription(string $description): string
    {
        $parts = explode('.', $description);

        return (string) end($parts);
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<int, array<string, mixed>>
     */
    private function diffs(array $properties, bool $canViewSensitive): array
    {
        $new = $this->propertyBucket($properties, ['attributes', 'new']);
        $old = $this->propertyBucket($properties, ['old']);
        $fields = array_values(array_unique(array_merge(array_keys($old), array_keys($new))));

        return collect($fields)
            ->map(fn (string $field): array => [
                'field' => $field,
                'oldValue' => $this->redactValue($field, $old[$field] ?? null, $canViewSensitive),
                'newValue' => $this->redactValue($field, $new[$field] ?? null, $canViewSensitive),
                'isSensitive' => $this->isSensitive($field),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $properties
     * @param  array<int, string>  $candidates
     * @return array<string, mixed>
     */
    private function propertyBucket(array $properties, array $candidates): array
    {
        foreach ($candidates as $candidate) {
            if (is_array($properties[$candidate] ?? null)) {
                return $properties[$candidate];
            }
        }

        return [];
    }

    private function redactValue(string $field, mixed $value, bool $canViewSensitive): mixed
    {
        if (! $this->isSensitive($field)) {
            return $this->stringableValue($value);
        }

        return $canViewSensitive ? $this->stringableValue($value) : self::REDACTED;
    }

    private function isSensitive(string $field): bool
    {
        $segments = explode('.', $field);
        $lastSegment = (string) end($segments);

        return in_array($field, InteractsWithLog::sensitiveAuditFields(), true)
            || in_array($lastSegment, InteractsWithLog::sensitiveAuditFields(), true);
    }

    private function stringableValue(mixed $value): mixed
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $value;
    }

    /**
     * @param  Collection<int, Activity>  $records
     * @return array<int, array<int, string>>
     */
    private function exportRows(Collection $records, User $actor): array
    {
        $canViewSensitive = $this->canViewSensitive($actor);
        $rows = [[
            'ID',
            'Created At',
            'Actor',
            'Action',
            'Module',
            'Entity',
            'Detail',
            'Diffs',
        ]];

        foreach ($records as $record) {
            $item = $this->transform($record, $canViewSensitive);
            $rows[] = [
                (string) $item['id'],
                (string) $item['createdAt'],
                (string) ($item['actor'] ?? ''),
                (string) $item['action'],
                (string) ($item['module'] ?? ''),
                (string) ($item['entity'] ?? ''),
                (string) ($item['detail'] ?? ''),
                json_encode($item['diffs'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
            ];
        }

        return $rows;
    }

    private function filename(string $filename, string $format): string
    {
        $slug = Str::slug($filename) ?: 'audit-log';

        return $slug.'.'.$format;
    }
}
