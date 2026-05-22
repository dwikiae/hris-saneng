<?php

namespace App\Services\Employee;

use App\Models\EmployeeDocument;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\ArchiveService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class EmployeeDocumentService
{
    /**
     * @var array<string, string>
     */
    private array $extensionsByMimeType = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly ArchiveService $archiveService
    ) {}

    /**
     * @return Collection<int, EmployeeDocument>
     */
    public function list(int $employeeId): Collection
    {
        $employee = $this->employees->show($employeeId);

        return $this->employees->documents($employee);
    }

    public function store(int $employeeId, string $documentType, UploadedFile $file): EmployeeDocument
    {
        $employee = $this->employees->show($employeeId);
        $mimeType = (string) $file->getMimeType();
        $extension = $this->extensionForMimeType($mimeType);
        $directory = "employees/{$employee->getKey()}/documents/{$documentType}";
        $path = $directory.'/'.Str::uuid()->toString().'.'.$extension;

        Storage::disk('documents')->put($path, $file->getContent());

        return $this->employees->createDocument($employee, [
            'document_type' => $documentType,
            'original_filename' => $file->getClientOriginalName(),
            'storage_disk' => 'documents',
            'path' => $path,
            'mime_type' => $mimeType,
            'size_bytes' => $file->getSize() ?: 0,
            'uploaded_by' => Auth::id(),
            'uploaded_at' => now(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    public function archive(int $employeeId, int $documentId): void
    {
        $employee = $this->employees->show($employeeId);
        $document = $this->employees->documentForEmployee($employee, $documentId);

        $this->archiveService->archive($document);
    }

    private function extensionForMimeType(string $mimeType): string
    {
        if (! array_key_exists($mimeType, $this->extensionsByMimeType)) {
            throw new InvalidArgumentException('employee.document_invalid_mime');
        }

        return $this->extensionsByMimeType[$mimeType];
    }
}
