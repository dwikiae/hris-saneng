<?php

namespace App\Modules\Karyawan\Console;

use App\Modules\Karyawan\Database\Seeders\CitySeeder;
use App\Modules\Karyawan\Database\Seeders\ProvinceSeeder;
use Illuminate\Console\Command;

class WilayahSyncCommand extends Command
{
    protected $signature = 'wilayah:sync';

    protected $description = 'Sync offline Indonesian province and city reference data.';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => ProvinceSeeder::class]);
        $this->call('db:seed', ['--class' => CitySeeder::class]);
        $this->info('Wilayah reference data synced.');

        return self::SUCCESS;
    }
}
