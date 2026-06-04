<?php

namespace App\Modules\Karyawan\Console;

use App\Modules\Karyawan\Database\Seeders\CountrySeeder;
use Illuminate\Console\Command;

class CountriesSyncCommand extends Command
{
    protected $signature = 'countries:sync';

    protected $description = 'Sync offline ISO 3166-1 alpha-2 country reference data.';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => CountrySeeder::class]);
        $this->info('Country reference data synced.');

        return self::SUCCESS;
    }
}
