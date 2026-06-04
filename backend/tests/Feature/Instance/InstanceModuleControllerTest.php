<?php

use App\Core\FileStorage\Domain\StorageAdapterInterface;
use App\Models\Company;
use App\Models\InstanceSetting;
use App\Models\ModuleRegistryEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('requires authentication for instance module endpoints', function () {
    $this->getJson('/api/v1/instance/modules')
        ->assertUnauthorized();
});

it('blocks company users from instance module endpoints', function () {
    $user = instanceModuleCompanyUser();

    $this->actingAs($user)
        ->getJson('/api/v1/instance/modules')
        ->assertForbidden();

    $this->actingAs($user)
        ->postJson('/api/v1/instance/modules/website/install')
        ->assertForbidden();
});

it('lists modules from manifests merged with registry state', function () {
    $admin = instanceModuleAdmin();
    ModuleRegistryEntry::query()->create([
        'code' => 'website',
        'name' => 'Website',
        'version' => '1.0.0',
        'description' => 'Installed website',
        'is_mandatory' => false,
        'dependencies' => [],
        'is_installed' => true,
        'installed_at' => now(),
    ]);

    $this->actingAs($admin)
        ->getJson('/api/v1/instance/modules')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'instance.module.list')
        ->assertJsonPath('data.0.code', 'aset')
        ->assertJsonFragment(['code' => 'karyawan', 'isMandatory' => true, 'isInstalled' => true])
        ->assertJsonFragment(['code' => 'website', 'status' => 'installed'])
        ->assertJsonStructure([
            'data' => [
                [
                    'code',
                    'name',
                    'version',
                    'description',
                    'isMandatory',
                    'dependencies',
                    'missingDependencies',
                    'isInstalled',
                    'installedAt',
                    'status',
                ],
            ],
        ]);
});

it('blocks installing and uninstalling mandatory modules', function () {
    $admin = instanceModuleAdmin();

    $this->actingAs($admin)
        ->postJson('/api/v1/instance/modules/karyawan/install')
        ->assertStatus(422)
        ->assertJsonPath('message', 'module.lifecycle.mandatory_locked');

    $this->actingAs($admin)
        ->postJson('/api/v1/instance/modules/karyawan/uninstall')
        ->assertStatus(422)
        ->assertJsonPath('message', 'module.lifecycle.mandatory_locked');
});

it('installs optional modules and records audit log', function () {
    $admin = instanceModuleAdmin();

    $this->actingAs($admin)
        ->postJson('/api/v1/instance/modules/website/install')
        ->assertOk()
        ->assertJsonPath('message', 'instance.module.installed')
        ->assertJsonPath('data.code', 'website')
        ->assertJsonPath('data.isInstalled', true);

    $this->assertDatabaseHas('module_registry', [
        'code' => 'website',
        'is_installed' => true,
    ]);
    $this->assertDatabaseHas('activity_log', ['description' => 'instance.module.installed']);
});

it('exports module data per company and records export marker', function () {
    $admin = instanceModuleAdmin();
    $storage = new InstanceModuleFakeStorageAdapter;
    $this->app->instance(StorageAdapterInterface::class, $storage);
    instanceModuleCompany('PT One');
    instanceModuleCompany('PT Two');

    $this->actingAs($admin)
        ->postJson('/api/v1/instance/modules/website/export-data')
        ->assertOk()
        ->assertJsonPath('message', 'instance.module.exported')
        ->assertJsonPath('data.module', 'website')
        ->assertJsonCount(2, 'data.companies')
        ->assertJsonPath('data.companies.0.path', 'exports/1/'.now()->toDateString().'/website.xlsx')
        ->assertJsonPath('data.companies.0.downloadUrl', 'signed://exports/1/'.now()->toDateString().'/website.xlsx');

    expect($storage->private)->toHaveCount(2)
        ->and(array_key_first($storage->private))->toBe('exports/1/'.now()->toDateString().'/website.xlsx')
        ->and(InstanceSetting::query()->where('key', 'module_uninstall_exports')->value('value'))->toContain('website');

    $this->assertDatabaseHas('activity_log', ['description' => 'instance.module.exported']);
});

it('requires export before uninstalling optional modules', function () {
    $admin = instanceModuleAdmin();
    installInstanceModule('website');

    $this->actingAs($admin)
        ->postJson('/api/v1/instance/modules/website/uninstall')
        ->assertStatus(422)
        ->assertJsonPath('message', 'module.lifecycle.export_required');
});

it('uninstalls optional modules after export and records pdp warning audit log', function () {
    $admin = instanceModuleAdmin();
    $this->app->instance(StorageAdapterInterface::class, new InstanceModuleFakeStorageAdapter);
    instanceModuleCompany('PT Uninstall');
    installInstanceModule('website');

    $this->actingAs($admin)->postJson('/api/v1/instance/modules/website/export-data')->assertOk();

    $this->actingAs($admin)
        ->postJson('/api/v1/instance/modules/website/uninstall')
        ->assertOk()
        ->assertJsonPath('message', 'instance.module.uninstalled')
        ->assertJsonPath('data', null);

    $this->assertDatabaseHas('module_registry', [
        'code' => 'website',
        'is_installed' => false,
    ]);
    $this->assertDatabaseHas('activity_log', ['description' => 'instance.module.uninstalled']);
});

it('registers instance module routes', function () {
    $routes = collect(Route::getRoutes())->map(fn ($route): string => implode('|', $route->methods()).' '.$route->uri());

    expect($routes)->toContain(
        'GET|HEAD api/v1/instance/modules',
        'POST api/v1/instance/modules/{code}/install',
        'POST api/v1/instance/modules/{code}/export-data',
        'POST api/v1/instance/modules/{code}/uninstall',
    );
});

function instanceModuleAdmin(): User
{
    return User::withoutCompanyScope()->create([
        'company_id' => null,
        'name' => 'Platform Module Admin',
        'email' => uniqid('platform.module.', true).'@example.test',
        'password' => 'password',
    ]);
}

function instanceModuleCompanyUser(): User
{
    $company = instanceModuleCompany('PT Module User');

    return User::create([
        'company_id' => $company->id,
        'name' => 'Company Module User',
        'email' => uniqid('company.module.', true).'@example.test',
        'password' => 'password',
    ]);
}

function instanceModuleCompany(string $name): Company
{
    return Company::create(['name' => uniqid($name.' ', false), 'legal_name' => $name.' Legal']);
}

function installInstanceModule(string $code): void
{
    ModuleRegistryEntry::query()->create([
        'code' => $code,
        'name' => ucfirst($code),
        'version' => '1.0.0',
        'description' => 'Installed '.$code,
        'is_mandatory' => false,
        'dependencies' => [],
        'is_installed' => true,
        'installed_at' => now(),
    ]);
}

class InstanceModuleFakeStorageAdapter implements StorageAdapterInterface
{
    /** @var array<string, string> */
    public array $private = [];

    public function putPrivate(string $path, string $contents): void
    {
        $this->private[$path] = $contents;
    }

    public function putPublic(string $path, string $contents): void {}

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
        return array_key_exists($path, $this->private);
    }

    public function publicExists(string $path): bool
    {
        return false;
    }
}
