<?php

namespace App\Filament\Resources\PackagePurchaseResource\Pages;

use App\Filament\Resources\PackagePurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPackagePurchase extends EditRecord
{
    protected static string $resource = PackagePurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
