<?php

namespace Sikei\CloudfrontEdge\Laravel;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Make sure the directory for compiled views exist
        if (!is_dir(config('view.compiled'))) {
            mkdir(config('view.compiled'), 0755, true);
        }

        if (!is_dir(config('cache.stores.file.path'))) {
            mkdir(config('cache.stores.file.path'), 0755, true);
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
