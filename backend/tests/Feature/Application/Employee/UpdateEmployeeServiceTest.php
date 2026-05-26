<?php

use App\Application\Employee\UpdateEmployeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

require_once __DIR__.'/EmployeeCoreTestSupport.php';

it('updates employee in any editable status and logs redacted sensitive changes', function () {
    $fixture = employeeCoreFixture(['employee.update']);
    $employee = employeeCoreEmployee($fixture);

    $this->actingAs($fixture['actor']);

    $updated = app(UpdateEmployeeService::class)->execute($employee, [
        'full_name' => 'Updated A4',
        'name' => 'Updated A4',
        'nik' => '3374010101010099',
    ]);

    $messages = $updated->chatterMessages()->pluck('message')->all();

    expect($updated->full_name)->toBe('Updated A4')
        ->and($messages)->toContain('Field full_name diperbarui oleh hr_core')
        ->and(collect($messages)->contains(fn (string $message): bool => str_contains($message, 'nik') && str_contains($message, '[REDACTED]')))->toBeTrue()
        ->and(collect($messages)->contains(fn (string $message): bool => str_contains($message, '3374010101010099')))->toBeFalse();
});
