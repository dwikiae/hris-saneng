<?php

namespace App\Models;

use App\Application\Storage\FileStorageService;
use App\Contracts\Archivable;
use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class EmployeeDocument extends Model implements Archivable
{
    use HasArchive;
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'employee_id',
        'document_type',
        'original_filename',
        'storage_disk',
        'path',
        'mime_type',
        'size_bytes',
        'uploaded_by',
        'uploaded_at',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
    ];

    protected $appends = [
        'signed_url',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected function signedUrl(): Attribute
    {
        return Attribute::get(
            fn (): string => $this->temporaryDocumentUrl()
        );
    }

    private function temporaryDocumentUrl(): string
    {
        $path = $this->getAttribute('path');

        if (! is_string($path)) {
            return '';
        }

        return app(FileStorageService::class)->signedPrivateUrl($path);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }
}
