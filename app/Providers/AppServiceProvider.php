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
        // Force Arabic locale and RTL direction
        app()->setLocale('ar');
        if (class_exists(\Filament\Facades\Filament::class)) {
            \Filament\Facades\Filament::serving(function () {
                \Filament\Facades\Filament::registerRenderHook(
                    'panels::body.start',
                    fn () => '<style>html { direction: rtl !important; }</style>'
                );
            });
        }
    }
}
