<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeEmergencyContactRequest;
use App\Http\Requests\Employee\UpdateEmployeeEmergencyContactRequest;
use App\Http\Resources\EmployeeEmergencyContactResource;
use App\Services\Employee\EmployeeEmergencyContactService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmployeeEmergencyContactController extends Controller
{
    public function __construct(private readonly EmployeeEmergencyContactService $contacts) {}

    public function index(Request $request, int $id): JsonResponse
    {
        Gate::authorize('employee.view');

        try {
            $contacts = $this->contacts->list($id);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(EmployeeEmergencyContactResource::collection($contacts)->resolve($request), 'employee.emergency_contacts.list');
    }

    public function store(StoreEmployeeEmergencyContactRequest $request, int $id): JsonResponse
    {
        try {
            $contact = $this->contacts->store($id, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(EmployeeEmergencyContactResource::make($contact)->resolve($request), 'employee.emergency_contacts.created', 201);
    }

    public function update(UpdateEmployeeEmergencyContactRequest $request, int $id, int $contactId): JsonResponse
    {
        try {
            $contact = $this->contacts->update($id, $contactId, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(EmployeeEmergencyContactResource::make($contact)->resolve($request), 'employee.emergency_contacts.updated');
    }

    public function archive(int $id, int $contactId): JsonResponse
    {
        Gate::authorize('employee.archive');

        try {
            $this->contacts->archive($id, $contactId);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(null, 'employee.emergency_contacts.archived');
    }

    private function success(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data, 'message' => $message], $status);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'employee.not_found'], 404);
    }
}
