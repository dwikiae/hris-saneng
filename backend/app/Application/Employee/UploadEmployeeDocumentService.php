<?php

namespace App\Application\Employee;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UploadEmployeeDocumentService
{
    /**
     * @var array<string, string>
     */
    private array $extensionsByMimeType = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    /**
     * @var list<string>
     */
    private array $documentTypes = [
        'ktp',
        'foto_ktp',
        'npwp',
        'ijazah',
        'kontrak',
        'bpjs_kesehatan',
        'bpjs_ketenagakerjaan',
        'skck',
        'sertifikasi',
        'lainnya',
    ];

    public function __construct(private readonly EmployeeRepositoryInterface $employees) {}

    /**
     * @throws ValidationException
     */
    public function execute(Employee $employee, string $documentType, UploadedFile $file, ?string $notes = null): EmployeeDocument
    {
        Gate::authorize('employee.update');
        $this->validateDocument($documentType, $file);

        $mimeType = (string) $file->getMimeType();
        $extension = $this->extensionsByMimeType[$mimeType];
        $path = 'employees/'.$employee->getKey().'/documents/'.$documentType.'/'.Str::uuid()->toString().'.'.$extension;

        Storage::disk('documents')->put($path, $file->getContent());

        $document = $this->employees->createDocument($employee, [
            'document_type' => $documentType,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'original_filename' => $file->getClientOriginalName(),
            'storage_disk' => 'documents',
            'path' => $path,
            'mime_type' => $mimeType,
            'size_bytes' => $file->getSize() ?: 0,
            'file_size' => $file->getSize() ?: 0,
            'notes' => $notes,
            'uploaded_by' => Auth::id(),
            'uploaded_at' => now(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $this->employees->createSystemLog($employee, 'Dokumen '.$documentType.' diunggah');

        return $document;
    }

    /**
     * @throws ValidationException
     */
    private function validateDocument(string $documentType, UploadedFile $file): void
    {
        if (! in_array($documentType, $this->documentTypes, true)) {
            throw ValidationException::withMessages(['document_type' => ['employee.document_invalid_type']]);
        }

        $mimeType = (string) $file->getMimeType();

        if (! array_key_exists($mimeType, $this->extensionsByMimeType)) {
            throw ValidationException::withMessages(['file' => ['employee.document_invalid_mime']]);
        }

        if (($file->getSize() ?: 0) > 10 * 1024 * 1024) {
            throw ValidationException::withMessages(['file' => ['employee.document_too_large']]);
        }
    }
}
