<?php

namespace App\Providers;

use App\Models\SponsorshipProgram;
use App\Models\SystemSetting;
use App\Observers\SponsorshipProgramObserver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
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
        // Register the Laravel exception renderer's anonymous components so debug
        // error pages render in non-production environments. Skipped when debug
        // mode is disabled so `view:cache` never pre-compiles fragile vendor
        // exception-renderer views (e.g. laravel-exceptions-renderer::icons.sun)
        // during production deployments.
        if (app()->hasDebugModeEnabled()) {
            $rendererPath = base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/exceptions/renderer');

            if (is_dir($rendererPath.'/components')) {
                $rendererPath .= '/components';
            }

            if (is_dir($rendererPath)) {
                Blade::anonymousComponentPath($rendererPath, 'laravel-exceptions-renderer');
            }
        }

        // Force HTTPS URL generation in production
        if (config('app.env') === 'production' || env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }

        // Login auditing is registered once by Laravel's app/Listeners discovery.

        // Register model observers
        SponsorshipProgram::observe(SponsorshipProgramObserver::class);

        // Safely set session lifetime without running premature Auth checks
        if (! $this->app->runningInConsole() && Schema::hasTable('system_settings')) {
            $timeout = SystemSetting::get('session_timeout_minutes', 120);
            config(['session.lifetime' => (int) $timeout]);
        }
    }
}
