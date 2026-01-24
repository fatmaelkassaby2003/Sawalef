<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class AppInfoWidget extends Widget
{
    protected static ?int $sort = -2;

    protected static string $view = 'filament.widgets.app-info-widget';
    
    protected int | string | array $columnSpan = [
        'default' => 12,
        'md' => 6,
    ];
}
