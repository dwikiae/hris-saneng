<?php

namespace App\Services\Employee;

use App\Models\EmployeePhoto;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use InvalidArgumentException;

class EmployeePhotoService
{
    /**
     * @var array<string, string>
     */
    private array $extensionsByMimeType = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    public function __construct(private readonly EmployeeRepositoryInterface $employees) {}

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
        $extension = $this->extensionForMimeType($mimeType);
        $basename = Str::uuid()->toString();
        $directory = "employees/{$employee->getKey()}/photo";
        $paths = [
            'original' => "{$directory}/{$basename}.{$extension}",
            'medium' => "{$directory}/{$basename}_medium.{$extension}",
            'thumbnail' => "{$directory}/{$basename}_thumbnail.{$extension}",
        ];

        Storage::disk('public')->put($paths['original'], $file->getContent());
        $this->storeResized($file, $paths['medium'], 800, $mimeType);
        $this->storeResized($file, $paths['thumbnail'], 150, $mimeType);

        return $this->employees->createPhoto($employee, [
            'storage_disk' => 'public',
            'path' => $paths['original'],
            'mime_type' => $mimeType,
            'size_bytes' => $file->getSize() ?: 0,
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
        $disk = Storage::disk('public');

        if (! $disk instanceof FilesystemAdapter) {
            return [
                'original' => null,
                'medium' => null,
                'thumbnail' => null,
            ];
        }

        $variantPaths = $this->variantPaths($path);

        return [
            'original' => $disk->exists($variantPaths['original']) ? $disk->url($variantPaths['original']) : null,
            'medium' => $disk->exists($variantPaths['medium']) ? $disk->url($variantPaths['medium']) : null,
            'thumbnail' => $disk->exists($variantPaths['thumbnail']) ? $disk->url($variantPaths['thumbnail']) : null,
        ];
    }

    private function storeResized(UploadedFile $file, string $path, int $size, string $mimeType): void
    {
        $manager = new ImageManager(new Driver);
        $encoded = $manager
            ->read($file->getContent())
            ->coverDown($size, $size)
            ->encodeByMediaType($mimeType, quality: 85);

        Storage::disk('public')->put($path, (string) $encoded);
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

    private function extensionForMimeType(string $mimeType): string
    {
        if (! array_key_exists($mimeType, $this->extensionsByMimeType)) {
            throw new InvalidArgumentException('employee.photo_invalid_mime');
        }

        return $this->extensionsByMimeType[$mimeType];
    }
}
