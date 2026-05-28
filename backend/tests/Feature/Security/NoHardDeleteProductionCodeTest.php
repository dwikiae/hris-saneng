<?php

use Illuminate\Support\Str;

it('does not use hard delete calls in production backend code', function () {
    $root = realpath(__DIR__.'/../../../app');

    expect($root)->not->toBeFalse();

    $violations = [];
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($files as $file) {
        if (! $file instanceof SplFileInfo || $file->getExtension() !== 'php') {
            continue;
        }

        $relativePath = Str::of($file->getPathname())
            ->replace('\\', '/')
            ->after('/backend/')
            ->toString();

        $contents = file_get_contents($file->getPathname());

        if ($contents === false) {
            continue;
        }

        foreach (['->delete(', 'forceDelete(', '::truncate(', '->truncate('] as $pattern) {
            if (str_contains($contents, $pattern)) {
                $violations[] = $relativePath.' contains '.$pattern;
            }
        }
    }

    expect($violations)->toBe([]);
});
