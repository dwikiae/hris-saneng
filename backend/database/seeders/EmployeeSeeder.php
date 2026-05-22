<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    /**
     * @return array<int, array<string, mixed>>
     */
    private function employees(): array
    {
        return [
            [
                'role' => 'hr_manager',
                'employee_number' => 'EMP-HRM-001',
                'name' => 'Maya Lestari',
                'email' => 'maya.lestari@saneng.co.id',
                'phone' => '081200000001',
                'address' => 'Jl. Industri Raya No. 1, Karawang',
                'birth_date' => '1988-04-12',
                'birth_place' => 'Bandung',
                'gender' => 'female',
                'department_code' => 'HRD',
                'position_code' => 'MANAGER',
                'employment_type_code' => 'TETAP',
                'join_date' => '2016-01-15',
                'nik' => '3273015204880001',
                'npwp' => '09.111.222.3-444.000',
                'bank_name' => 'BCA',
                'bank_account_number' => '0011223344',
                'salary' => '18000000',
                'allowances' => '2500000',
                'deductions' => '500000',
            ],
            [
                'role' => 'hr_staff',
                'employee_number' => 'EMP-HRS-001',
                'name' => 'Rizky Pratama',
                'email' => 'rizky.pratama@saneng.co.id',
                'phone' => '081200000002',
                'address' => 'Jl. Melati No. 8, Karawang',
                'birth_date' => '1994-09-20',
                'birth_place' => 'Bekasi',
                'gender' => 'male',
                'department_code' => 'HRD',
                'position_code' => 'STAFF',
                'employment_type_code' => 'TETAP',
                'join_date' => '2020-06-01',
                'nik' => '3216022009940002',
                'npwp' => '09.222.333.4-555.000',
                'bank_name' => 'Mandiri',
                'bank_account_number' => '1122334455',
                'salary' => '8500000',
                'allowances' => '1000000',
                'deductions' => '250000',
            ],
            [
                'role' => 'employee',
                'employee_number' => 'EMP-OPS-001',
                'name' => 'Siti Aminah',
                'email' => 'siti.aminah@saneng.co.id',
                'phone' => '081200000003',
                'address' => 'Jl. Kenanga No. 12, Karawang',
                'birth_date' => '1997-12-03',
                'birth_place' => 'Karawang',
                'gender' => 'female',
                'department_code' => 'OPERASIONAL',
                'position_code' => 'OPERATOR',
                'employment_type_code' => 'TETAP',
                'join_date' => '2022-03-10',
                'nik' => '3215014312970003',
                'npwp' => '09.333.444.5-666.000',
                'bank_name' => 'BRI',
                'bank_account_number' => '2233445566',
                'salary' => '5500000',
                'allowances' => '750000',
                'deductions' => '150000',
            ],
        ];
    }

    public function run(): void
    {
        $this->ensurePrerequisites();

        $company = Company::query()->where('name', 'PT Saneng')->firstOrFail();
        $consentUser = User::query()->withoutGlobalScope('company')->where('email', 'admin@saneng.co.id')->firstOrFail();

        foreach ($this->employees() as $row) {
            $department = $this->department($company->getKey(), (string) $row['department_code']);
            $position = $this->position($company->getKey(), (string) $row['position_code']);
            $employmentType = $this->employmentType($company->getKey(), (string) $row['employment_type_code']);

            $employee = Employee::query()->withoutGlobalScope('company')->firstOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'employee_number' => $row['employee_number'],
                ],
                [
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'phone' => $row['phone'],
                    'address' => $row['address'],
                    'birth_date' => $row['birth_date'],
                    'birth_place' => $row['birth_place'],
                    'gender' => $row['gender'],
                    'department_id' => $department->getKey(),
                    'position_id' => $position->getKey(),
                    'employment_type_id' => $employmentType->getKey(),
                    'join_date' => $row['join_date'],
                    'nik' => $row['nik'],
                    'npwp' => $row['npwp'],
                    'bank_name' => $row['bank_name'],
                    'bank_account_number' => $row['bank_account_number'],
                    'salary' => $row['salary'],
                    'allowances' => $row['allowances'],
                    'deductions' => $row['deductions'],
                    'consent_at' => now(),
                    'consent_by' => $consentUser->getKey(),
                    'status' => Employee::ACTIVE,
                    'approved_by' => $consentUser->getKey(),
                    'approved_at' => now(),
                    'created_by' => $consentUser->getKey(),
                    'updated_by' => $consentUser->getKey(),
                ]
            );

            $user = User::query()->withoutGlobalScope('company')->firstOrCreate(
                ['email' => $row['email']],
                [
                    'company_id' => $company->getKey(),
                    'name' => $row['name'],
                    'password' => Hash::make('Password@123'),
                    'language_preference' => 'id',
                    'force_password_reset' => true,
                    'login_attempts' => 0,
                ]
            );

            $user->forceFill([
                'company_id' => $company->getKey(),
                'name' => $row['name'],
                'employee_id' => $employee->getKey(),
                'updated_by' => $consentUser->getKey(),
            ])->save();

            $role = Role::query()->withoutGlobalScope('company')
                ->where('company_id', $company->getKey())
                ->where('code', $row['role'])
                ->firstOrFail();

            $user->roles()->syncWithoutDetaching([$role->getKey()]);
        }
    }

    private function ensurePrerequisites(): void
    {
        $this->call([
            CompanySeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            AdminUserSeeder::class,
        ]);
    }

    private function department(int $companyId, string $code): Department
    {
        $names = [
            'HRD' => 'HRD',
            'OPERASIONAL' => 'Operasional',
        ];

        /** @var Department $department */
        $department = Department::query()->withoutGlobalScope('company')->firstOrCreate(
            [
                'company_id' => $companyId,
                'code' => $code,
            ],
            [
                'name' => $names[$code] ?? $code,
                'is_active' => true,
            ]
        );

        return $department;
    }

    private function position(int $companyId, string $code): Position
    {
        $names = [
            'MANAGER' => 'Manager',
            'STAFF' => 'Staff',
            'OPERATOR' => 'Operator',
        ];

        /** @var Position $position */
        $position = Position::query()->withoutGlobalScope('company')->firstOrCreate(
            [
                'company_id' => $companyId,
                'code' => $code,
            ],
            [
                'name' => $names[$code] ?? $code,
                'is_active' => true,
            ]
        );

        return $position;
    }

    private function employmentType(int $companyId, string $code): EmploymentType
    {
        $names = [
            'TETAP' => 'Tetap',
        ];

        /** @var EmploymentType $employmentType */
        $employmentType = EmploymentType::query()->withoutGlobalScope('company')->firstOrCreate(
            [
                'company_id' => $companyId,
                'code' => $code,
            ],
            [
                'name' => $names[$code] ?? $code,
                'is_active' => true,
            ]
        );

        return $employmentType;
    }
}
