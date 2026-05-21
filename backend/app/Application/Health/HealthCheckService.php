<?php

namespace App\Application\Health;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthCheckService
{
    /**
     * @return array{status: string, environment: string, services: array<string, string>, timestamp: string}
     */
    public function check(): array
    {
        $services = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        return [
            'status' => $this->resolveStatus($services),
            'environment' => app()->environment(),
            'services' => $services,
            'timestamp' => now(config('app.timezone', 'Asia/Jakarta'))->toIso8601String(),
        ];
    }

    private function checkDatabase(): string
    {
        try {
            DB::select('select 1');

            return 'ok';
        } catch (Throwable) {
            return 'error';
        }
    }

    private function checkRedis(): string
    {
        try {
            $response = Redis::connection()->command('ping');

            return $response === true || strtoupper((string) $response) === 'PONG' ? 'ok' : 'error';
        } catch (Throwable) {
            return 'error';
        }
    }

    private function checkStorage(): string
    {
        try {
            $disk = config('filesystems.disks.documents');
            $endpoint = rtrim((string) ($disk['endpoint'] ?? ''), '/');

            if ($endpoint === '') {
                return 'error';
            }

            return Http::timeout(2)->get($endpoint.'/minio/health/live')->successful()
                ? 'ok'
                : 'error';
        } catch (Throwable) {
            return 'error';
        }
    }

    private function checkQueue(): string
    {
        try {
            Queue::connection()->getConnectionName();

            if (config('queue.default') === 'redis') {
                return $this->checkRedis();
            }

            return 'ok';
        } catch (Throwable) {
            return 'error';
        }
    }

    /**
     * @param  array<string, string>  $services
     */
    private function resolveStatus(array $services): string
    {
        $okCount = count(array_filter($services, fn (string $status): bool => $status === 'ok'));

        if ($okCount === count($services)) {
            return 'ok';
        }

        return $okCount === 0 ? 'error' : 'degraded';
    }
}
