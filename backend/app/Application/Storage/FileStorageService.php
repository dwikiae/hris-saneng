<?php

namespace App\Application\Storage;

use App\Domain\Storage\StorageAdapterInterface;
use App\Domain\Storage\StorageVisibility;
use App\Domain\Storage\StoredFile;
use App\Domain\Storage\UploadProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;

class FileStorageService
{
    private const MAX_PRIVATE_DOCUMENT_BYTES = 10 * 1024 * 1024;

    private const MAX_EMPLOYEE_PHOTO_BYTES = 2 * 1024 * 1024;

    private const MAX_PUBLIC_ASSET_BYTES = 5 * 1024 * 1024;

    /**
     * @var array<string, string>
     */
    private const EXTENSIONS_BY_MIME = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'text/csv' => 'csv',
    ];

    public function __construct(private readonly StorageAdapterInterface $storage) {}

    public function storePrivateDocument(UploadedFile $file, string $directory): StoredFile
    {
        $this->validateFile($file, UploadProfile::PrivateDocument);
        $path = $this->path($directory, $this->extensionForFile($file));

        $this->storage->putPrivate($path, $file->getContent());

        return $this->storedFile($file, $path, 'documents', StorageVisibility::Private);
    }

    public function storePublicAsset(UploadedFile $file, string $directory, UploadProfile $profile = UploadProfile::PublicAsset): StoredFile
    {
        if (! in_array($profile, [UploadProfile::EmployeePhoto, UploadProfile::PublicAsset], true)) {
            throw new InvalidArgumentException('storage.invalid_public_profile');
        }

        $this->validateFile($file, $profile);
        $path = $this->path($directory, $this->extensionForFile($file));

        $this->storage->putPublic($path, $file->getContent());

        return $this->storedFile($file, $path, 'public', StorageVisibility::Public);
    }

    public function putPublicContents(string $path, string $contents): void
    {
        $this->storage->putPublic($path, $contents);
    }

    public function signedPrivateUrl(string $path, int $ttlMinutes = 15): string
    {
        return $this->storage->privateSignedUrl($path, $ttlMinutes);
    }

    public function publicUrl(string $path): string
    {
        return $this->storage->publicUrl($path);
    }

    public function publicExists(string $path): bool
    {
        return $this->storage->publicExists($path);
    }

    public function employeeDocumentDirectory(int $employeeId, string $documentType): string
    {
        $this->ensurePathSegment($documentType);

        return "employees/{$employeeId}/documents/{$documentType}";
    }

    public function employeePhotoDirectory(int $employeeId): string
    {
        return "employees/{$employeeId}/photo";
    }

    public function recruitmentStageDirectory(int $jobPostingId, int $applicantId, string $stage): string
    {
        $this->ensurePathSegment($stage);

        return "recruitment/{$jobPostingId}/applicants/{$applicantId}/stages/{$stage}";
    }

    public function companyPublicAssetDirectory(int $companyId, string $assetType): string
    {
        $this->ensurePathSegment($assetType);

        return "companies/{$companyId}/public/{$assetType}";
    }

    public function exportDirectory(?string $date = null): string
    {
        return 'exports/'.($date ?? now()->toDateString());
    }

    public function path(string $directory, string $extension): string
    {
        return trim($directory, '/').'/'.Str::uuid()->toString().'.'.$extension;
    }

    public function extensionForFile(UploadedFile $file): string
    {
        $mimeType = (string) $file->getMimeType();

        if (! array_key_exists($mimeType, self::EXTENSIONS_BY_MIME)) {
            throw new InvalidArgumentException('storage.invalid_mime_type');
        }

        return self::EXTENSIONS_BY_MIME[$mimeType];
    }

    private function validateFile(UploadedFile $file, UploadProfile $profile): void
    {
        $mimeType = (string) $file->getMimeType();
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $maxBytes = $this->maxBytes($profile);
        $allowedExtensions = $this->allowedExtensions($profile);

        if (! in_array($mimeType, $this->allowedMimeTypes($profile), true)) {
            throw new InvalidArgumentException('storage.invalid_mime_type');
        }

        if (! in_array($extension, $allowedExtensions, true)) {
            throw new InvalidArgumentException('storage.invalid_extension');
        }

        if ((int) $file->getSize() > $maxBytes) {
            throw new InvalidArgumentException('storage.file_too_large');
        }
    }

    /**
     * @return array<int, string>
     */
    private function allowedMimeTypes(UploadProfile $profile): array
    {
        return match ($profile) {
            UploadProfile::PrivateDocument => ['application/pdf', 'image/jpeg', 'image/png'],
            UploadProfile::EmployeePhoto, UploadProfile::PublicAsset => ['image/jpeg', 'image/png'],
            UploadProfile::ExportFile => ['text/csv'],
        };
    }

    /**
     * @return array<int, string>
     */
    private function allowedExtensions(UploadProfile $profile): array
    {
        return match ($profile) {
            UploadProfile::PrivateDocument => ['pdf', 'jpg', 'jpeg', 'png'],
            UploadProfile::EmployeePhoto, UploadProfile::PublicAsset => ['jpg', 'jpeg', 'png'],
            UploadProfile::ExportFile => ['csv'],
        };
    }

    private function maxBytes(UploadProfile $profile): int
    {
        return match ($profile) {
            UploadProfile::PrivateDocument => self::MAX_PRIVATE_DOCUMENT_BYTES,
            UploadProfile::EmployeePhoto => self::MAX_EMPLOYEE_PHOTO_BYTES,
            UploadProfile::PublicAsset => self::MAX_PUBLIC_ASSET_BYTES,
            UploadProfile::ExportFile => self::MAX_PRIVATE_DOCUMENT_BYTES,
        };
    }

    private function storedFile(UploadedFile $file, string $path, string $disk, StorageVisibility $visibility): StoredFile
    {
        return new StoredFile(
            disk: $disk,
            path: $path,
            originalFilename: $file->getClientOriginalName(),
            mimeType: (string) $file->getMimeType(),
            sizeBytes: $file->getSize() ?: 0,
            visibility: $visibility,
        );
    }

    private function ensurePathSegment(string $segment): void
    {
        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $segment)) {
            throw new InvalidArgumentException('storage.invalid_path_segment');
        }
    }
}
