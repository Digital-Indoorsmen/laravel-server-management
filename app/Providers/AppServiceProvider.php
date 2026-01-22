<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('database.default') === 'sqlite') {
            $db = \Illuminate\Support\Facades\DB::connection()->getPdo();
            $db->exec('PRAGMA mmap_size = 2147483648');
            $db->exec('PRAGMA auto_vacuum = INCREMENTAL');
            $db->exec('PRAGMA temp_store = MEMORY');
        }
    }
}
