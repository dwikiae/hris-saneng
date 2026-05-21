<?php

namespace App\Http\Controllers\Api\V1\Archive;

use App\Contracts\Archivable;
use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\BloodType;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\EducationLevel;
use App\Models\EmploymentType;
use App\Models\MaritalStatus;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Religion;
use App\Models\Role;
use App\Models\User;
use App\Services\ArchiveService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class ArchiveController extends Controller
{
    /**
     * @var array<string, class-string<Model&Archivable>>
     */
    private array $models = [
        'users' => User::class,
        'roles' => Role::class,
        'permissions' => Permission::class,
        'departments' => Department::class,
        'positions' => Position::class,
        'employment-types' => EmploymentType::class,
        'banks' => Bank::class,
        'blood-types' => BloodType::class,
        'document-types' => DocumentType::class,
        'education-levels' => EducationLevel::class,
        'marital-statuses' => MaritalStatus::class,
        'religions' => Religion::class,
    ];

    public function __construct(private readonly ArchiveService $service) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('archive.manage');

        $filters = $this->filters($request);

        try {
            $model = $request->filled('model') ? $this->resolveModel($request->string('model')->toString()) : null;
        } catch (InvalidArgumentException) {
            return response()->json(['success' => false, 'message' => 'archive.model_not_archivable'], 422);
        }

        $records = $model === null
            ? $this->allArchivedRecords($filters, $request)
            : $this->transformPaginator($this->service->getArchivedRecords($model, 20, $filters));

        return response()->json([
            'success' => true,
            'data' => $records,
            'message' => 'archive.list',
        ]);
    }

    public function restore(string $model, int $id): JsonResponse
    {
        Gate::authorize('archive.manage');

        try {
            $modelClass = $this->resolveModel($model);
        } catch (InvalidArgumentException) {
            return response()->json(['success' => false, 'message' => 'archive.model_not_archivable'], 422);
        }

        $record = (new $modelClass)->newQuery()
            ->withoutGlobalScope('not_archived')
            ->whereNotNull('archived_at')
            ->where((new $modelClass)->getKeyName(), $id)
            ->first();

        if (! $record instanceof Model) {
            return response()->json(['success' => false, 'message' => 'archive.not_found'], 404);
        }

        $this->service->restore($record);

        return response()->json([
            'success' => true,
            'data' => $this->transformRecord($record->refresh(), $model),
            'message' => 'archive.restored',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        $filters = [];

        if ($request->filled('archived_by')) {
            $filters['archived_by'] = $request->integer('archived_by');
        }

        if ($request->filled('date_from')) {
            $filters['date_from'] = $request->date('date_from');
        }

        if ($request->filled('date_to')) {
            $filters['date_to'] = $request->date('date_to');
        }

        return $filters;
    }

    private function aliasFor(string $modelClass): string
    {
        return array_search($modelClass, $this->models, true) ?: $modelClass;
    }

    /**
     * @return class-string<Model&Archivable>
     */
    private function resolveModel(string $model): string
    {
        $modelClass = $this->models[$model] ?? null;

        if ($modelClass === null) {
            throw new InvalidArgumentException('archive.model_not_archivable');
        }

        return $modelClass;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function allArchivedRecords(array $filters, Request $request): LengthAwarePaginator
    {
        $items = collect($this->models)
            ->flatMap(function (string $modelClass) use ($filters): Collection {
                return $this->service->getArchivedRecords($modelClass, 1000, $filters)
                    ->collect()
                    ->map(fn (Model $record): array => $this->transformRecord($record, $this->aliasFor($modelClass)));
            })
            ->sortByDesc('archived_at')
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $pageItems = $items->slice(($page - 1) * 20, 20)->values();

        return new LengthAwarePaginator($pageItems, $items->count(), 20, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
    }

    private function transformPaginator(LengthAwarePaginator $records): LengthAwarePaginator
    {
        $items = collect($records->items())
            ->map(fn (Model $record): array => $this->transformRecord($record, $this->aliasFor($record::class)))
            ->values();

        return new LengthAwarePaginator($items, $records->total(), $records->perPage(), $records->currentPage(), [
            'path' => $records->path(),
            'query' => request()->query(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformRecord(Model $record, string $model): array
    {
        return [
            'model' => $model,
            'model_class' => $record::class,
            'id' => $record->getKey(),
            'label' => $record->getAttribute('name') ?? $record->getAttribute('code') ?? $record->getAttribute('email'),
            'archived_at' => $record->getAttribute('archived_at'),
            'archived_by' => $record->getAttribute('archived_by'),
        ];
    }
}
