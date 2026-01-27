<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminContactResource\Pages;
use App\Filament\Resources\AdminContactResource\RelationManagers;
use App\Models\AdminContact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AdminContactResource extends Resource
{
    protected static ?string $navigationGroup = 'الإدارة';

    protected static ?string $navigationLabel = 'بيانات التواصل';

    protected static ?string $modelLabel = 'بيانات التواصل';

    protected static ?string $pluralModelLabel = 'بيانات التواصل';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الاتصال الأساسية')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('الهاتف')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('روابط التواصل الإضافية')
                    ->schema([
                        Forms\Components\Repeater::make('links')
                            ->label('الروابط')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('اسم الرابط (مثلاً: واتساب)')
                                    ->required(),
                                Forms\Components\TextInput::make('url')
                                    ->label('الرابط (URL)')
                                    ->url()
                                    ->required(),
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),
                Tables\Columns\TextColumn::make('links_count')
                    ->label('عدد الروابط')
                    ->getStateUsing(fn ($record) => count($record->links ?? []) . ' روابط'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for singleton
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminContacts::route('/'),
            'create' => Pages\CreateAdminContact::route('/create'),
            'view' => Pages\ViewAdminContact::route('/{record}'),
            'edit' => Pages\EditAdminContact::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return AdminContact::count() < 1;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
