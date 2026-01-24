<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        return [
            Stat::make('المستخدمين', \App\Models\User::count())
                ->description('إجمالي المستخدمين المسجلين')
                ->descriptionIcon('heroicon-m-users')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),
            Stat::make('المنشورات', \App\Models\Post::count())
                ->description('إجمالي المنشورات')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->chart([15, 3, 12, 5, 20, 8, 25])
                ->color('warning'),
            Stat::make('الهوايات', \App\Models\Hobby::count())
                ->description('الهوايات المتاحة')
                ->descriptionIcon('heroicon-m-heart')
                ->color('primary'),
        ];
    }
}
