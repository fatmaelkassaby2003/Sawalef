<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Models\Faq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationGroup = 'الإعدادات والصفحات';

    public static function getNavigationLabel(): string
    {
        return 'الأسئلة الشائعة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الأسئلة الشائعة';
    }

    public static function getModelLabel(): string
    {
        return 'سؤال';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المحتوي العربي')
                    ->schema([
                        Forms\Components\TextInput::make('question_ar')
                            ->label('السؤال (AR)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\RichEditor::make('answer_ar')
                            ->label('الإجابة (AR)')
                            ->required()
                            ->columnSpanFull(),
                    ])->columnSpan(1),

                Forms\Components\Section::make('English Content')
                    ->schema([
                        Forms\Components\TextInput::make('question_en')
                            ->label('Question (EN)')
                            ->maxLength(255),
                        Forms\Components\RichEditor::make('answer_en')
                            ->label('Answer (EN)')
                            ->columnSpanFull(),
                    ])->columnSpan(1),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question_ar')
                    ->label('السؤال (AR)')
                    ->searchable(),
                Tables\Columns\TextColumn::make('question_en')
                    ->label('Question (EN)')
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
            'index' => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'view' => Pages\ViewFaq::route('/{record}'),
            'edit' => Pages\EditFaq::route('/{record}/edit'),
        ];
    }
}
