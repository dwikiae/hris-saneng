<?php

namespace App\Http\Controllers\Api\V1\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Requests\Company\StoreCompanyUserRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\UserResource;
use App\Models\Company;
use App\Models\User;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class CompanyController extends Controller
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companies,
        private readonly UserRepository $users,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('company.view');

        $actor = $request->user();
        $companyId = $actor instanceof User && ! $actor->isInstanceAdmin() ? (int) $actor->company_id : null;

        return $this->success(
            CompanyResource::collection($this->companies->all($companyId))->resolve($request),
            'company.list'
        );
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $company = $this->companies->create($request->validated());

        return $this->success(CompanyResource::make($company)->resolve($request), 'company.created', 201);
    }

    public function show(Request $request, int $company): JsonResponse
    {
        Gate::authorize('company.view');

        $record = $this->findVisibleCompany($request, $company);

        if (! $record instanceof Company) {
            return $this->notFound();
        }

        return $this->success(CompanyResource::make($record)->resolve($request), 'company.detail');
    }

    public function update(UpdateCompanyRequest $request, int $company): JsonResponse
    {
        $record = $this->findVisibleCompany($request, $company);

        if (! $record instanceof Company) {
            return $this->notFound();
        }

        $updated = $this->companies->update($record, $request->validated());

        return $this->success(CompanyResource::make($updated)->resolve($request), 'company.updated');
    }

    public function storeUser(StoreCompanyUserRequest $request, int $company): JsonResponse
    {
        $record = $this->findVisibleCompany($request, $company);

        if (! $record instanceof Company) {
            return $this->notFound();
        }

        $validated = $request->validated();
        $roleIds = $validated['role_ids'] ?? [];
        unset($validated['role_ids']);

        $user = $this->users->create(array_merge($validated, [
            'company_id' => $record->getKey(),
            'password' => Hash::make($validated['password']),
            'force_password_reset' => true,
            'created_by' => $request->user()?->getAuthIdentifier(),
        ]));

        if ($roleIds !== []) {
            $user = $this->users->syncRolesForCompany((int) $user->getKey(), $roleIds, (int) $record->getKey());
        }

        return $this->success(UserResource::make($user->load('roles'))->resolve($request), 'user.created', 201);
    }

    private function findVisibleCompany(Request $request, int $companyId): ?Company
    {
        $actor = $request->user();
        $visibleCompanyId = $actor instanceof User && ! $actor->isInstanceAdmin() ? (int) $actor->company_id : null;

        return $this->companies->find($companyId, $visibleCompanyId);
    }

    private function success(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data, 'message' => $message], $status);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'company.not_found'], 404);
    }
}
