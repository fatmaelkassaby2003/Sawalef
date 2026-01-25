<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Tables\Table;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Force Arabic locale
        app()->setLocale('ar');
        
        // Filament V3 Standard Pagination Configuration
        // This ensures compatibility with SPA mode
        Table::configureUsing(function (Table $table): void {
            $table
                ->paginationPageOptions([10, 25, 50, 100]);
        });
    }
}
