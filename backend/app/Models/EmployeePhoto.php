<?php

namespace App\Models;

use App\Contracts\Archivable;
use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity;

class EmployeePhoto extends Model implements Archivable
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
        'original_path',
        'medium_path',
        'thumbnail_path',
        'storage_disk',
        'path',
        'mime_type',
        'size_bytes',
        'file_size',
        'uploaded_by',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
    ];

    protected $appends = [
        'url',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected function url(): Attribute
    {
        return Attribute::get(
            fn (): string => $this->publicPhotoUrl()
        );
    }

    private function publicPhotoUrl(): string
    {
        $path = $this->getAttribute('path');
        $disk = Storage::disk('public');

        if (! is_string($path) || ! $disk instanceof FilesystemAdapter) {
            return '';
        }

        return $disk->url($path);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }
}
