<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class WelcomeWidget extends Widget
{
    protected static ?int $sort = -3;
    
    protected static bool $isLazy = false;

    protected static string $view = 'filament.widgets.welcome-widget';
    
    protected int | string | array $columnSpan = [
        'default' => 12,
        'md' => 6,
    ];
}
