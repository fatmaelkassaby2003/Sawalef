<?php

namespace App\Filament\Resources\AdminContactResource\Pages;

use App\Filament\Resources\AdminContactResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdminContacts extends ListRecords
{
    protected static string $resource = AdminContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
