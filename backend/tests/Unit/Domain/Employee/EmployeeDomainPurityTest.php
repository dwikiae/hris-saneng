<?php

it('does not import laravel framework classes in employee domain files', function () {
    $domainPath = dirname(__DIR__, 4).DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Domain'.DIRECTORY_SEPARATOR.'Employee';
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($domainPath));

    foreach ($files as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        expect(file_get_contents($file->getPathname()))->not->toContain('Illuminate\\');
    }
})->group('employee-domain');
