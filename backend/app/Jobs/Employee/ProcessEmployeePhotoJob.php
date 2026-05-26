<?php

namespace App\Jobs\Employee;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ProcessEmployeePhotoJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        private readonly string $originalPath,
        private readonly string $mediumPath,
        private readonly string $thumbnailPath,
        private readonly string $mimeType
    ) {}

    public function handle(): void
    {
        if (! extension_loaded('gd')) {
            $this->copyFallback();

            return;
        }

        $contents = Storage::disk('public')->get($this->originalPath);
        $manager = new ImageManager(new Driver);

        Storage::disk('public')->put(
            $this->mediumPath,
            (string) $manager->read($contents)->coverDown(300, 300)->encodeByMediaType($this->mimeType, quality: 85)
        );
        Storage::disk('public')->put(
            $this->thumbnailPath,
            (string) $manager->read($contents)->coverDown(80, 80)->encodeByMediaType($this->mimeType, quality: 85)
        );
    }

    private function copyFallback(): void
    {
        $contents = Storage::disk('public')->get($this->originalPath);
        Storage::disk('public')->put($this->mediumPath, $contents);
        Storage::disk('public')->put($this->thumbnailPath, $contents);
    }
}
