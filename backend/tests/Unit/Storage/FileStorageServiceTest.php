<?php

use App\Core\FileStorage\Application\FileStorageService;
use App\Core\FileStorage\Domain\StorageAdapterInterface;
use App\Core\FileStorage\Domain\StorageVisibility;
use Illuminate\Http\UploadedFile;
use Tests\TestCase as BaseTestCase;

uses(BaseTestCase::class);

it('generates storage paths by convention', function () {
    $service = new FileStorageService(new FakeStorageAdapter);

    expect($service->employeeDocumentDirectory(10, 'ktp'))->toBe('employees/10/documents/ktp')
        ->and($service->employeePhotoDirectory(10))->toBe('employees/10/photo')
        ->and($service->recruitmentStageDirectory(5, 9, 'interview'))->toBe('recruitment/5/applicants/9/stages/interview')
        ->and($service->companyPublicAssetDirectory(3, 'logo'))->toBe('companies/3/public/logo')
        ->and($service->exportDirectory('2026-05-28'))->toBe('exports/2026-05-28');
});

it('stores private documents on documents disk', function () {
    $adapter = new FakeStorageAdapter;
    $service = new FileStorageService($adapter);
    $file = UploadedFile::fake()->createWithContent('contract.pdf', '%PDF-1.4');

    $stored = $service->storePrivateDocument($file, 'employees/1/documents/contract');

    expect($stored->disk)->toBe('documents')
        ->and($stored->visibility)->toBe(StorageVisibility::Private)
        ->and($stored->path)->toStartWith('employees/1/documents/contract/')
        ->and($adapter->privateWrites)->toHaveCount(1)
        ->and($adapter->publicWrites)->toBeEmpty();
});

it('stores public assets on public disk', function () {
    $adapter = new FakeStorageAdapter;
    $service = new FileStorageService($adapter);
    $file = UploadedFile::fake()->createWithContent(
        'logo.png',
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=') ?: ''
    );

    $stored = $service->storePublicAsset($file, 'companies/1/public/logo');

    expect($stored->disk)->toBe('public')
        ->and($stored->visibility)->toBe(StorageVisibility::Public)
        ->and($adapter->publicWrites)->toHaveCount(1)
        ->and($adapter->privateWrites)->toBeEmpty();
});

it('uses signed url for private files and public url for public files', function () {
    $service = new FileStorageService(new FakeStorageAdapter);

    expect($service->signedPrivateUrl('employees/1/documents/ktp/file.pdf'))->toBe('signed://employees/1/documents/ktp/file.pdf')
        ->and($service->publicUrl('companies/1/public/logo/file.png'))->toBe('public://companies/1/public/logo/file.png');
});

it('rejects invalid upload extension and path segment', function () {
    $service = new FileStorageService(new FakeStorageAdapter);
    $file = UploadedFile::fake()->create('payload.exe', 1, 'application/x-msdownload');

    expect(fn () => $service->storePrivateDocument($file, 'employees/1/documents/ktp'))
        ->toThrow(InvalidArgumentException::class, 'storage.invalid_mime_type')
        ->and(fn () => $service->employeeDocumentDirectory(1, '../ktp'))
        ->toThrow(InvalidArgumentException::class, 'storage.invalid_path_segment');
});

final class FakeStorageAdapter implements StorageAdapterInterface
{
    /**
     * @var array<int, string>
     */
    public array $privateWrites = [];

    /**
     * @var array<int, string>
     */
    public array $publicWrites = [];

    public function putPrivate(string $path, string $contents): void
    {
        $this->privateWrites[] = $path;
    }

    public function putPublic(string $path, string $contents): void
    {
        $this->publicWrites[] = $path;
    }

    public function privateSignedUrl(string $path, int $ttlMinutes): string
    {
        return 'signed://'.$path;
    }

    public function publicUrl(string $path): string
    {
        return 'public://'.$path;
    }

    public function privateExists(string $path): bool
    {
        return true;
    }

    public function publicExists(string $path): bool
    {
        return true;
    }
}
