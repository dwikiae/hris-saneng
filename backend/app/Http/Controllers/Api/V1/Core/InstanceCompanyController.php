<?php

namespace App\Http\Controllers\Api\V1\Core;

use App\Application\Instance\InstanceCompanyService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instance\ArchiveInstanceCompanyRequest;
use App\Http\Requests\Instance\ListInstanceCompaniesRequest;
use App\Http\Requests\Instance\ShowInstanceCompanyRequest;
use App\Http\Requests\Instance\StoreInstanceCompanyRequest;
use App\Http\Requests\Instance\UpdateInstanceCompanyRequest;
use App\Http\Resources\InstanceCompanyResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

class InstanceCompanyController extends Controller
{
    public function __construct(private readonly InstanceCompanyService $companies) {}

    public function index(ListInstanceCompaniesRequest $request): JsonResponse
    {
        $paginator = $this->companies->list(
            $request->validated(),
            $request->integer('per_page', 20)
        );
        $meta = [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];

        return $this->success([
            'items' => InstanceCompanyResource::collection($paginator->getCollection())->resolve($request),
            'meta' => $meta,
        ], 'instance.company.list', 200, $meta);
    }

    public function store(StoreInstanceCompanyRequest $request): JsonResponse
    {
        $company = $this->companies->create($request->validated());

        return $this->success(
            InstanceCompanyResource::make($company)->resolve($request),
            'instance.company.created',
            201
        );
    }

    public function show(ShowInstanceCompanyRequest $request, int $company): JsonResponse
    {
        $record = $this->companies->find($company);

        if (! $record instanceof Company) {
            return $this->notFound();
        }

        return $this->success(
            InstanceCompanyResource::make($record)->resolve($request),
            'instance.company.detail'
        );
    }

    public function update(UpdateInstanceCompanyRequest $request, int $company): JsonResponse
    {
        $record = $this->companies->update($company, $request->validated());

        if (! $record instanceof Company) {
            return $this->notFound();
        }

        return $this->success(
            InstanceCompanyResource::make($record)->resolve($request),
            'instance.company.updated'
        );
    }

    public function destroy(ArchiveInstanceCompanyRequest $request, int $company): JsonResponse
    {
        if (! $this->companies->archive($company)) {
            return $this->notFound();
        }

        return $this->success(null, 'instance.company.archived');
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

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => 'instance.company.not_found',
            'meta' => [],
        ], 404);
    }
}
