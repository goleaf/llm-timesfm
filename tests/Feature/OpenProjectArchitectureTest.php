<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('keeps the project open without auth users or database connection subsystems', function (): void {
    expect(Route::has('login'))->toBeFalse()
        ->and(Route::has('register'))->toBeFalse()
        ->and(config('session.driver'))->not->toBe('database')
        ->and(config('queue.default'))->toBe('sync')
        ->and(config('cache.default'))->not->toBe('database')
        ->and(file_exists(config_path('auth.php')))->toBeFalse()
        ->and(Schema::hasTable('users'))->toBeFalse()
        ->and(Schema::hasTable('password_reset_tokens'))->toBeFalse()
        ->and(Schema::hasTable('sessions'))->toBeFalse()
        ->and(Schema::hasTable('cache'))->toBeFalse()
        ->and(Schema::hasTable('jobs'))->toBeFalse()
        ->and(Schema::hasTable('failed_jobs'))->toBeFalse();
});
