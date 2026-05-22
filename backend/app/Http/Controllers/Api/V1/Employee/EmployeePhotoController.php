<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeePhotoRequest;
use App\Services\Employee\EmployeePhotoService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class EmployeePhotoController extends Controller
{
    public function __construct(private readonly EmployeePhotoService $photos) {}

    public function show(int $id): JsonResponse
    {
        Gate::authorize('employee.update');

        try {
            $urls = $this->photos->show($id);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success($urls, 'employee.photo.detail');
    }

    public function store(StoreEmployeePhotoRequest $request, int $id): JsonResponse
    {
        try {
            $photo = $this->photos->store($id, $request->file('photo'));
        } catch (ModelNotFoundException) {
            return $this->notFound();
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success($this->photos->urlsForPath((string) $photo->getAttribute('path')), 'employee.photo.uploaded', 201);
    }

    private function success(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data, 'message' => $message], $status);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    private function notFound(): JsonResponse
    {
        return $this->error('employee.not_found', 404);
    }
}
