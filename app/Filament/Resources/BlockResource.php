<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlockResource\Pages;
use App\Models\Block;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BlockResource extends Resource
{
    protected static ?string $model = Block::class;

    protected static ?string $navigationIcon  = 'heroicon-o-no-symbol';
    protected static ?string $navigationLabel = 'قائمة الحظر';
    protected static ?string $navigationGroup = 'الإشراف';
    protected static ?int    $navigationSort  = 2;
    protected static ?string $modelLabel      = 'حظر';
    protected static ?string $pluralModelLabel = 'الحظر';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('blocker.name')
                    ->label('الحاظر')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('blocker.phone')
                    ->label('رقم الحاظر')
                    ->searchable(),

                Tables\Columns\TextColumn::make('blocked.name')
                    ->label('المحظور')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('blocked.phone')
                    ->label('رقم المحظور')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الحظر')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->label('رفع الحظر'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('رفع الحظر المحدد'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlocks::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
