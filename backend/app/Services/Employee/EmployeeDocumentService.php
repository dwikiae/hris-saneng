<?php

namespace App\Services\Employee;

use App\Application\Storage\FileStorageService;
use App\Models\EmployeeDocument;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\ArchiveService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class EmployeeDocumentService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly ArchiveService $archiveService,
        private readonly FileStorageService $storage
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
        $storedFile = $this->storage->storePrivateDocument(
            $file,
            $this->storage->employeeDocumentDirectory((int) $employee->getKey(), $documentType)
        );

        return $this->employees->createDocument($employee, [
            'document_type' => $documentType,
            'original_filename' => $storedFile->originalFilename,
            'storage_disk' => $storedFile->disk,
            'path' => $storedFile->path,
            'mime_type' => $storedFile->mimeType,
            'size_bytes' => $storedFile->sizeBytes,
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
}
