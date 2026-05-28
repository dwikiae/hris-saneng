<?php

namespace App\Domain\Storage;

interface StorageAdapterInterface
{
    public function putPrivate(string $path, string $contents): void;

    public function putPublic(string $path, string $contents): void;

    public function privateSignedUrl(string $path, int $ttlMinutes): string;

    public function publicUrl(string $path): string;

    public function privateExists(string $path): bool;

    public function publicExists(string $path): bool;
}
