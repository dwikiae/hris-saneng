<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Application\MasterData\BankService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BankController extends MasterDataController
{
    public function __construct(private readonly BankService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [];

        if ($request->has('is_active')) {
            $filters['is_active'] = $request->boolean('is_active');
        }

        return $this->success($this->service->list($filters), '');
    }

    public function show(int $id): JsonResponse
    {
        try {
            $record = $this->service->findById($id);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success($record, '');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('banks', 'code')
                    ->where('company_id', config('company.default_id', 1)),
            ],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $data = array_merge($validated, ['company_id' => (int) config('company.default_id', 1)]);
        $record = $this->service->create($data, (int) Auth::id());

        return $this->success($record, 'master_data.created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        try {
            $record = $this->service->update($id, $validated, (int) Auth::id());
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success($record, 'master_data.updated');
    }

    public function archive(int $id): JsonResponse
    {
        if (! $this->service->archive($id, (int) Auth::id())) {
            return $this->notFound();
        }

        return $this->success(null, 'master_data.archived');
    }

    public function restore(int $id): JsonResponse
    {
        if (! $this->service->restore($id)) {
            return $this->notFound();
        }

        return $this->success(null, 'master_data.restored');
    }
}
