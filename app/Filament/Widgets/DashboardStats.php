<?php

namespace App\Filament\Widgets;

use App\Models\Hobby;
use App\Models\Post;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends BaseWidget
{
    protected static ?int $sort = -1;
    
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            Stat::make('المستخدمين', User::count())
                ->description('المستخدمين الجدد +20%')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),
            
            Stat::make('المنشورات', Post::count())
                ->description('نشاط عالي')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('info'),
                
            Stat::make('الهوايات', Hobby::count())
                ->description('تنوع الاهتمامات')
                ->color('warning'),
        ];
    }
}
