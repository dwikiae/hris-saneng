<?php

namespace App\Jobs\Recruitment;

use App\Application\Recruitment\PemberkasanService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;

class SendPemberkasanLinkJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly int $applicantId, public readonly int $userId) {}

    public function handle(PemberkasanService $pemberkasanService): void
    {
        Auth::loginUsingId($this->userId);

        try {
            $pemberkasanService->sendPemberkasanLink($this->applicantId, $this->userId);
        } finally {
            Auth::logout();
        }
    }
}
