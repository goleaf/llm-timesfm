<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

it('keeps public pages in full page livewire components without app controllers or volt', function (): void {
    $controllerFiles = Finder::create()
        ->files()
        ->in(app_path())
        ->name('*Controller.php');

    $bladeFiles = collect(Finder::create()
        ->files()
        ->in(resource_path('views'))
        ->name('*.blade.php'))
        ->map(fn (SplFileInfo $file): string => Str::of($file->getPathname())
            ->after(resource_path('views').DIRECTORY_SEPARATOR)
            ->replace(DIRECTORY_SEPARATOR, '/')
            ->toString())
        ->values();

    $invalidBladeFiles = $bladeFiles
        ->reject(fn (string $path): bool => str_starts_with($path, 'livewire/')
            || $path === 'components/layouts/app.blade.php')
        ->values();

    expect(iterator_count($controllerFiles))->toBe(0)
        ->and($invalidBladeFiles->all())->toBe([])
        ->and(Route::has('login'))->toBeFalse()
        ->and(Route::has('register'))->toBeFalse()
        ->and(class_exists('Livewire\Volt\Volt'))->toBeFalse();
});

it('keeps livewire components and commands behind request and action layers', function (): void {
    $entrypointFiles = collect(Finder::create()
        ->files()
        ->in([app_path('Livewire'), app_path('Console/Commands')])
        ->name('*.php'));

    $violations = $entrypointFiles
        ->mapWithKeys(fn (SplFileInfo $file): array => [$file->getRelativePathname() => $file->getContents()])
        ->filter(fn (string $contents): bool => str_contains($contents, '::query(')
            || str_contains($contents, 'use App\\Models\\'))
        ->keys()
        ->values();

    expect($violations->all())->toBe([]);
});
