<?php

namespace App\Providers;

use App\Listeners\LogSuccessfulLogin;
use App\Models\SponsorshipProgram;
use App\Models\SystemSetting;
use App\Observers\SponsorshipProgramObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
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
        // Register anonymous component namespace for Laravel exception renderer views
        Blade::anonymousComponentPath(
            base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/exceptions/renderer'),
            'laravel-exceptions-renderer',
        );

        // Force HTTPS URL generation in production
        if (config('app.env') === 'production' || env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }

        // Register login audit listener
        Event::listen(Login::class, LogSuccessfulLogin::class);

        // Register model observers
        SponsorshipProgram::observe(SponsorshipProgramObserver::class);

        // Safely set session lifetime without running premature Auth checks
        if (! $this->app->runningInConsole() && Schema::hasTable('system_settings')) {
            $timeout = SystemSetting::get('session_timeout_minutes', 120);
            config(['session.lifetime' => (int) $timeout]);
        }
    }
}
