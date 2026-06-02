<?php

namespace App\Services\Employee;

use App\Core\FileStorage\Application\FileStorageService;
use App\Core\FileStorage\Domain\UploadProfile;
use App\Models\EmployeePhoto;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use InvalidArgumentException;

class EmployeePhotoService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly FileStorageService $storage
    ) {}

    /**
     * @return array<string, string|null>
     */
    public function show(int $employeeId): array
    {
        $employee = $this->employees->show($employeeId);
        $photo = $this->employees->latestPhoto($employee);

        if (! $photo instanceof EmployeePhoto) {
            return [
                'original' => null,
                'medium' => null,
                'thumbnail' => null,
            ];
        }

        return $this->urlsForPath((string) $photo->getAttribute('path'));
    }

    public function store(int $employeeId, UploadedFile $file): EmployeePhoto
    {
        if (! extension_loaded('gd')) {
            throw new InvalidArgumentException('employee.photo_processor_unavailable');
        }

        $employee = $this->employees->show($employeeId);
        $mimeType = (string) $file->getMimeType();
        $storedFile = $this->storage->storePublicAsset(
            $file,
            $this->storage->employeePhotoDirectory((int) $employee->getKey()),
            UploadProfile::EmployeePhoto
        );
        $paths = $this->variantPaths($storedFile->path);

        $this->storeResized($file, $paths['medium'], 800, $mimeType);
        $this->storeResized($file, $paths['thumbnail'], 150, $mimeType);

        return $this->employees->createPhoto($employee, [
            'storage_disk' => $storedFile->disk,
            'path' => $storedFile->path,
            'mime_type' => $storedFile->mimeType,
            'size_bytes' => $storedFile->sizeBytes,
            'uploaded_by' => Auth::id(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    /**
     * @return array<string, string|null>
     */
    public function urlsForPath(string $path): array
    {
        $variantPaths = $this->variantPaths($path);

        return [
            'original' => $this->publicUrlIfExists($variantPaths['original']),
            'medium' => $this->publicUrlIfExists($variantPaths['medium']),
            'thumbnail' => $this->publicUrlIfExists($variantPaths['thumbnail']),
        ];
    }

    private function storeResized(UploadedFile $file, string $path, int $size, string $mimeType): void
    {
        $manager = new ImageManager(new Driver);
        $encoded = $manager
            ->read($file->getContent())
            ->coverDown($size, $size)
            ->encodeByMediaType($mimeType, quality: 85);

        $this->storage->putPublicContents($path, (string) $encoded);
    }

    /**
     * @return array<string, string>
     */
    private function variantPaths(string $path): array
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $basePath = substr($path, 0, -strlen('.'.$extension));

        return [
            'original' => $path,
            'medium' => $basePath.'_medium.'.$extension,
            'thumbnail' => $basePath.'_thumbnail.'.$extension,
        ];
    }

    private function publicUrlIfExists(string $path): ?string
    {
        if (! $this->storage->publicExists($path)) {
            return null;
        }

        return $this->storage->publicUrl($path);
    }
}
