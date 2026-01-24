<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HobbyResource\Pages;
use App\Models\Hobby;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HobbyResource extends Resource
{
    protected static ?string $model = Hobby::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';
    
    protected static ?string $navigationLabel = 'الهوايات';
    protected static ?string $pluralModelLabel = 'الهوايات';
    protected static ?string $modelLabel = 'هواية';
    protected static ?string $navigationGroup = 'الإعدادات';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم الهواية')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('icon')
                    ->label('الأيقونة')
                    ->image()
                    ->directory('hobby_icons')
                    ->visibility('public'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon')
                    ->label('الأيقونة'),
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListHobbies::route('/'),
            'create' => Pages\CreateHobby::route('/create'),
            'edit' => Pages\EditHobby::route('/{record}/edit'),
        ];
    }
}
