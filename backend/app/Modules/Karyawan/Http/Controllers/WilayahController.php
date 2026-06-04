<?php

namespace App\Modules\Karyawan\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Karyawan\Application\WilayahService;
use App\Modules\Karyawan\Http\Requests\ListProvinceCitiesRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class WilayahController extends Controller
{
    public function __construct(private readonly WilayahService $wilayah) {}

    public function provinces(): JsonResponse
    {
        return $this->success(
            $this->wilayah->provinces()->map(fn (Model $province): array => [
                'code' => $province->getAttribute('code'),
                'name' => $province->getAttribute('name'),
            ])->values(),
            'wilayah.provinces.list'
        );
    }

    public function cities(ListProvinceCitiesRequest $request, string $code): JsonResponse
    {
        return $this->success(
            $this->wilayah->citiesByProvince($code)->map(fn (Model $city): array => [
                'code' => $city->getAttribute('code'),
                'provinceCode' => $city->getAttribute('province_code'),
                'name' => $city->getAttribute('name'),
            ])->values(),
            'wilayah.cities.list'
        );
    }

    public function countries(): JsonResponse
    {
        return $this->success(
            $this->wilayah->countries()->map(fn (Model $country): array => [
                'code' => $country->getAttribute('code'),
                'name' => $country->getAttribute('name'),
                'isActive' => (bool) $country->getAttribute('is_active'),
            ])->values(),
            'wilayah.countries.list'
        );
    }

    private function success(mixed $data, string $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'meta' => [],
        ]);
    }
}
