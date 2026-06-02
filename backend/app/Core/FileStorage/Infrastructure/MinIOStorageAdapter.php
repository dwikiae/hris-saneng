<?php

namespace App\Core\FileStorage\Infrastructure;

use App\Core\FileStorage\Domain\StorageAdapterInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MinIOStorageAdapter implements StorageAdapterInterface
{
    public function putPrivate(string $path, string $contents): void
    {
        $this->privateDisk()->put($path, $contents);
    }

    public function putPublic(string $path, string $contents): void
    {
        $this->publicDisk()->put($path, $contents);
    }

    public function privateSignedUrl(string $path, int $ttlMinutes): string
    {
        $disk = $this->privateDisk();

        if (! $disk instanceof FilesystemAdapter || ! $disk->providesTemporaryUrls()) {
            throw new RuntimeException('storage.signed_url_unavailable');
        }

        return $disk->temporaryUrl($path, now()->addMinutes($ttlMinutes));
    }

    public function publicUrl(string $path): string
    {
        $disk = $this->publicDisk();

        if (! $disk instanceof FilesystemAdapter) {
            throw new RuntimeException('storage.public_url_unavailable');
        }

        return $disk->url($path);
    }

    public function privateExists(string $path): bool
    {
        return $this->privateDisk()->exists($path);
    }

    public function publicExists(string $path): bool
    {
        return $this->publicDisk()->exists($path);
    }

    private function privateDisk(): Filesystem
    {
        return Storage::disk('documents');
    }

    private function publicDisk(): Filesystem
    {
        return Storage::disk('public');
    }
}
