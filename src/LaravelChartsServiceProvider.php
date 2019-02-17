<?php

namespace LaravelDaily\LaravelCharts;

use Illuminate\Support\ServiceProvider;

class LaravelChartsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/views', 'laravelchart');
    }
}
