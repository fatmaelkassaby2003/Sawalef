<?php

namespace App\Filament\Resources\AboutAppResource\Pages;

use App\Filament\Resources\AboutAppResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAboutApp extends EditRecord
{
    protected static string $resource = AboutAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
