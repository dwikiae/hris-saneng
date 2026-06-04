<?php

namespace App\Modules\Karyawan\Repositories\Eloquent;

use App\Modules\Karyawan\Models\City;
use App\Modules\Karyawan\Models\Country;
use App\Modules\Karyawan\Models\Province;
use App\Modules\Karyawan\Repositories\Contracts\WilayahRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class WilayahRepository implements WilayahRepositoryInterface
{
    public function provinces(): Collection
    {
        return Province::query()->orderBy('code')->get();
    }

    public function citiesByProvince(string $provinceCode): Collection
    {
        return City::query()
            ->where('province_code', $provinceCode)
            ->orderBy('code')
            ->get();
    }

    public function countries(): Collection
    {
        return Country::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
