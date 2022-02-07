<?php declare(strict_types=1);

namespace Statistics;

use Illuminate\Support\ServiceProvider as Provider;

class ServiceProvider extends Provider
{
    /**
     * Bootstrap any package services.
     *
     */
    public function boot() : void
    {
        $this->publishes([__DIR__ . '/../config/statistics.php' => config_path('statistics.php')]);

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Register any package services.
     *
     */
    public function register() : void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/statistics.php', 'statistics');
    }
}
