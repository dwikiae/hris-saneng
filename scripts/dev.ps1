param(
    [Parameter(Position = 0)]
    [ValidateSet('status', 'install', 'test', 'lint', 'typecheck', 'verify')]
    [string] $Command = 'status'
)

$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $PSScriptRoot

function Invoke-InPath {
    param(
        [string] $Path,
        [scriptblock] $Script
    )

    Push-Location (Join-Path $Root $Path)
    try {
        & $Script
    } finally {
        Pop-Location
    }
}

function Invoke-Checked {
    param([scriptblock] $Script)

    & $Script
    if ($LASTEXITCODE -ne 0) {
        throw "Command failed with exit code $LASTEXITCODE"
    }
}

function Test-CommandExists {
    param([string] $Name)

    return [bool](Get-Command $Name -ErrorAction SilentlyContinue)
}

function Write-Step {
    param([string] $Message)

    Write-Host ""
    Write-Host "==> $Message"
}

function Show-Status {
    Write-Step 'Tool versions'
    php -v | Select-Object -First 1
    composer --version
    node --version
    npm.cmd --version

    if (Test-CommandExists 'docker') {
        docker --version
    } else {
        Write-Host 'Docker not found'
    }

    Write-Step 'Dependency folders'
    $checks = @(
        @('backend/vendor', (Test-Path (Join-Path $Root 'backend/vendor'))),
        @('frontend-hris/node_modules', (Test-Path (Join-Path $Root 'frontend-hris/node_modules'))),
        @('frontend-web/node_modules', (Test-Path (Join-Path $Root 'frontend-web/node_modules')))
    )

    foreach ($check in $checks) {
        $state = if ($check[1]) { 'OK' } else { 'MISSING' }
        Write-Host "$($check[0]): $state"
    }

    Write-Step 'Environment files'
    $envFiles = @(
        @('backend/.env', (Test-Path (Join-Path $Root 'backend/.env'))),
        @('frontend-hris/.env.local', (Test-Path (Join-Path $Root 'frontend-hris/.env.local'))),
        @('frontend-web/.env.local', (Test-Path (Join-Path $Root 'frontend-web/.env.local')))
    )

    foreach ($envFile in $envFiles) {
        $state = if ($envFile[1]) { 'OK' } else { 'OPTIONAL/MISSING' }
        Write-Host "$($envFile[0]): $state"
    }

    Write-Step 'PHP extensions for test suite'
    $extensions = php -m
    $sqliteReady = ($extensions -contains 'pdo_sqlite') -and ($extensions -contains 'sqlite3')
    $sqliteState = if ($sqliteReady) { 'OK' } else { 'MISSING pdo_sqlite/sqlite3' }
    Write-Host "SQLite in-memory tests: $sqliteState"
}

function Install-Dependencies {
    Write-Step 'Backend composer install'
    Invoke-InPath 'backend' { Invoke-Checked { composer install } }

    Write-Step 'Frontend HRIS npm install'
    Invoke-InPath 'frontend-hris' { Invoke-Checked { npm.cmd install } }

    Write-Step 'Frontend Web npm install'
    Invoke-InPath 'frontend-web' { Invoke-Checked { npm.cmd install } }
}

function Test-Backend {
    Write-Step 'Backend tests'
    Invoke-InPath 'backend' { Invoke-Checked { php artisan test } }
}

function Test-Lint {
    Write-Step 'Frontend HRIS lint'
    Invoke-InPath 'frontend-hris' { Invoke-Checked { npm.cmd run lint } }

    Write-Step 'Frontend Web lint'
    Invoke-InPath 'frontend-web' { Invoke-Checked { npm.cmd run lint } }
}

function Test-TypeScript {
    Write-Step 'Frontend HRIS typecheck'
    Invoke-InPath 'frontend-hris' { Invoke-Checked { npx.cmd tsc --noEmit } }

    Write-Step 'Frontend Web typecheck'
    Invoke-InPath 'frontend-web' { Invoke-Checked { npx.cmd tsc --noEmit } }
}

switch ($Command) {
    'status' {
        Show-Status
    }
    'install' {
        Install-Dependencies
    }
    'test' {
        Test-Backend
    }
    'lint' {
        Test-Lint
    }
    'typecheck' {
        Test-TypeScript
    }
    'verify' {
        Show-Status
        Test-Backend
        Test-Lint
        Test-TypeScript
    }
}
