<?php

namespace App\Filament\Resources\AdminContactResource\Pages;

use App\Filament\Resources\AdminContactResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdminContact extends EditRecord
{
    protected static string $resource = AdminContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
