<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::updateOrCreate(
            ['name' => 'PT Saneng'],
            [
                'legal_name' => 'PT Saneng',
                'timezone' => 'Asia/Jakarta',
                'date_format' => 'DD/MM/YYYY',
                'language_default' => 'id',
            ]
        );
    }
}
