<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TermResource\Pages;
use App\Models\Term;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TermResource extends Resource
{
    protected static ?string $model = Term::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'الإعدادات والصفحات';

    public static function getNavigationLabel(): string
    {
        return 'الشروط والاحكام';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الشروط والاحكام';
    }

    public static function getModelLabel(): string
    {
        return 'شرط';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المحتوي العربي')
                    ->description('ادخل الشروط والاحكام باللغة العربية')
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
                    ->description('Enter Terms and Conditions in English')
                    ->schema([
                        Forms\Components\TextInput::make('title_en')
                            ->label('Title (EN)')
                            ->maxLength(255),
                        Forms\Components\RichEditor::make('content_en')
                            ->label('Content (EN)')
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
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListTerms::route('/'),
            'create' => Pages\CreateTerm::route('/create'),
            'view' => Pages\ViewTerm::route('/{record}'),
            'edit' => Pages\EditTerm::route('/{record}/edit'),
        ];
    }
}
