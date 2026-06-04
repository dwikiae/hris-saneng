<?php

namespace App\Modules\Karyawan\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface WilayahRepositoryInterface
{
    public function provinces(): Collection;

    public function citiesByProvince(string $provinceCode): Collection;

    public function countries(): Collection;
}
