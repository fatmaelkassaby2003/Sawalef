<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class CustomAccountWidget extends Widget
{
    protected static string $view = 'filament.widgets.custom-account-widget';

    protected int | string | array $columnSpan = 1; // Half width

    protected static ?int $sort = 2; // Second item (Top Left)

    // Make it accessible for view
    public function getUser()
    {
        return Auth::user();
    }
}
