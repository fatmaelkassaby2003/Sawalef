<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class ProjectInfoWidget extends Widget
{
    protected static string $view = 'filament.widgets.project-info-widget';

    protected int | string | array $columnSpan = 1; // Half width

    protected static ?int $sort = 1; // First item (Top Right)
}
