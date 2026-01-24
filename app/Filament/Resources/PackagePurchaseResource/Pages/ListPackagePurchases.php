<?php

namespace App\Filament\Resources\PackagePurchaseResource\Pages;

use App\Filament\Resources\PackagePurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPackagePurchases extends ListRecords
{
    protected static string $resource = PackagePurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
