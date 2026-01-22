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
            $pragmas = config('database.connections.sqlite.pragmas', []);

            foreach ($pragmas as $key => $value) {
                $db->exec("PRAGMA {$key} = {$value};");
            }
        }

        \App\Models\SshKey::observe(\App\Observers\ResourceObserver::class);
        \App\Models\Server::observe(\App\Observers\ResourceObserver::class);
        \App\Models\Site::observe(\App\Observers\ResourceObserver::class);
    }
}
