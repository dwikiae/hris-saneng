<?php

namespace App\Application\Employee;

use App\Jobs\Employee\ProcessEmployeePhotoJob;
use App\Models\Employee;
use App\Models\EmployeePhoto;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UploadEmployeePhotoService
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
     * @throws ValidationException
     */
    public function execute(Employee $employee, UploadedFile $file): EmployeePhoto
    {
        Gate::authorize('employee.update');
        $this->validatePhoto($file);

        $mimeType = (string) $file->getMimeType();
        $extension = $this->extensionsByMimeType[$mimeType];
        $base = Str::uuid()->toString();
        $directory = 'employees/'.$employee->getKey().'/photo';
        $originalPath = $directory.'/'.$base.'.'.$extension;
        $mediumPath = $directory.'/'.$base.'_medium.'.$extension;
        $thumbnailPath = $directory.'/'.$base.'_thumbnail.'.$extension;

        Storage::disk('public')->put($originalPath, $file->getContent());
        ProcessEmployeePhotoJob::dispatch($originalPath, $mediumPath, $thumbnailPath, $mimeType);

        $photo = $this->employees->createPhoto($employee, [
            'storage_disk' => 'public',
            'path' => $originalPath,
            'original_path' => $originalPath,
            'medium_path' => $mediumPath,
            'thumbnail_path' => $thumbnailPath,
            'mime_type' => $mimeType,
            'size_bytes' => $file->getSize() ?: 0,
            'file_size' => $file->getSize() ?: 0,
            'uploaded_by' => Auth::id(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $this->employees->createSystemLog($employee, 'Foto karyawan diunggah');

        return $photo;
    }

    /**
     * @throws ValidationException
     */
    private function validatePhoto(UploadedFile $file): void
    {
        $mimeType = (string) $file->getMimeType();

        if (! array_key_exists($mimeType, $this->extensionsByMimeType)) {
            throw ValidationException::withMessages(['photo' => ['employee.photo_invalid_mime']]);
        }

        if (($file->getSize() ?: 0) > 2 * 1024 * 1024) {
            throw ValidationException::withMessages(['photo' => ['employee.photo_too_large']]);
        }
    }
}
