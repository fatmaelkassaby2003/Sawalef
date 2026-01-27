<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AboutAppResource\Pages;
use App\Models\AboutApp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AboutAppResource extends Resource
{
    protected static ?string $model = AboutApp::class;

    protected static ?string $navigationIcon = 'heroicon-o-information-circle';
    protected static ?string $navigationGroup = 'الإعدادات والصفحات';

    public static function getNavigationLabel(): string
    {
        return 'عن التطبيق';
    }

    public static function getPluralModelLabel(): string
    {
        return 'عن التطبيق';
    }

    public static function getModelLabel(): string
    {
        return 'عن التطبيق';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المحتوي العربي')
                    ->description('ادخل معلومات عن التطبيق باللغة العربية')
                    ->schema([
                        Forms\Components\TextInput::make('title_ar')
                            ->label('العنوان (AR)')
                            ->maxLength(255),
                        Forms\Components\RichEditor::make('content_ar')
                            ->label('المحتوى (AR)')
                            ->required()
                            ->columnSpanFull(),
                    ])->columnSpan(1),

                Forms\Components\Section::make('English Content')
                    ->description('Enter About App information in English')
                    ->schema([
                        Forms\Components\TextInput::make('title_en')
                            ->label('Title (EN)')
                            ->maxLength(255),
                        Forms\Components\RichEditor::make('content_en')
                            ->label('Content (EN)')
                            ->required()
                            ->columnSpanFull(),
                    ])->columnSpan(1),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title_ar')
                    ->label('العنوان (AR)')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title_en')
                    ->label('Title (EN)')
                    ->searchable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime()
                    ->sortable(),
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
            'index' => Pages\ListAboutApps::route('/'),
            'create' => Pages\CreateAboutApp::route('/create'),
            'edit' => Pages\EditAboutApp::route('/{record}/edit'),
        ];
    }
}
