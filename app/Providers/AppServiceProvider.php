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

        // Global Table Configuration
        \Filament\Tables\Table::configureUsing(function (\Filament\Tables\Table $table): void {
            $table
                ->paginated([10, 25, 50, 100])
                ->defaultPaginationPageOption(10);
        });

        // Force Pagination Theme to Bootstrap for stability
        \Illuminate\Pagination\Paginator::useBootstrap();
    }
}
