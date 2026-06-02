<?php

use App\Core\Health\Application\HealthCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function bindHealthyService(): void
{
    app()->bind(HealthCheckService::class, function () {
        return new class extends HealthCheckService
        {
            public function check(): array
            {
                return [
                    'status' => 'ok',
                    'environment' => 'testing',
                    'services' => [
                        'database' => 'ok',
                        'redis' => 'ok',
                        'storage' => 'ok',
                        'queue' => 'ok',
                    ],
                    'timestamp' => now('Asia/Jakarta')->toIso8601String(),
                ];
            }
        };
    });
}

it('returns 200 with ok status when all services are running', function () {
    bindHealthyService();

    $this->getJson('/health')
        ->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('services.database', 'ok')
        ->assertJsonPath('services.redis', 'ok')
        ->assertJsonPath('services.storage', 'ok')
        ->assertJsonPath('services.queue', 'ok');
});

it('response contains required keys: status, environment, services, timestamp', function () {
    bindHealthyService();

    $this->getJson('/health')
        ->assertOk()
        ->assertJsonStructure([
            'status',
            'environment',
            'services' => [
                'database',
                'redis',
                'storage',
                'queue',
            ],
            'timestamp',
        ]);
});

it('timestamp is in WIB timezone', function () {
    bindHealthyService();

    $timestamp = $this->getJson('/health')->json('timestamp');

    expect($timestamp)->toEndWith('+07:00');
});
