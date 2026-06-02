<?php

namespace App\Core\FileStorage\Domain;

final readonly class StoredFile
{
    public function __construct(
        public string $disk,
        public string $path,
        public string $originalFilename,
        public string $mimeType,
        public int $sizeBytes,
        public StorageVisibility $visibility,
    ) {}
}
