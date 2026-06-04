<?php

namespace App\Modules\Karyawan\Application;

use App\Modules\Karyawan\Repositories\Contracts\WilayahRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class WilayahService
{
    public function __construct(private readonly WilayahRepositoryInterface $wilayah) {}

    public function provinces(): Collection
    {
        return $this->wilayah->provinces();
    }

    public function citiesByProvince(string $provinceCode): Collection
    {
        return $this->wilayah->citiesByProvince($provinceCode);
    }

    public function countries(): Collection
    {
        return $this->wilayah->countries();
    }
}
