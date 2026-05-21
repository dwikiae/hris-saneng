<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreUserRequest;
use App\Http\Requests\Auth\UpdateUserRequest;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(private readonly UserRepository $repository) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [];

        if ($request->has('is_active')) {
            $filters['is_active'] = $request->boolean('is_active');
        }

        return $this->success($this->repository->all($filters));
    }

    public function show(int $id): JsonResponse
    {
        $record = $this->repository->findById($id);

        if ($record === null) {
            return $this->notFound();
        }

        return $this->success($record);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'company_id'           => (int) config('company.default_id', 1),
            'password'             => Hash::make($request->validated()['password']),
            'force_password_reset' => true,
            'created_by'           => Auth::id(),
        ]);

        $record = $this->repository->create($data);

        return $this->success($record, 201);
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        try {
            $data = array_merge($request->validated(), ['updated_by' => Auth::id()]);
            $record = $this->repository->update($id, $data);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success($record);
    }

    public function archive(int $id): JsonResponse
    {
        if (! $this->repository->archive($id, (int) Auth::id())) {
            return $this->notFound();
        }

        return $this->success(null);
    }

    public function restore(int $id): JsonResponse
    {
        if (! $this->repository->restore($id)) {
            return $this->notFound();
        }

        return $this->success(null);
    }

    private function success(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data], $status);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'user.not_found'], 404);
    }
}
