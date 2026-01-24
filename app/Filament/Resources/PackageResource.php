<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackageResource\Pages;
use App\Filament\Resources\PackageResource\RelationManagers;
use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'الباقات';

    protected static ?string $modelLabel = 'باقة';

    protected static ?string $pluralModelLabel = 'الباقات';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الباقة')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الباقة')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('order')
                            ->label('ترتيب العرض')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->columnSpan(1),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('وصف الباقة')
                            ->maxLength(65535)
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('تفاصيل الباقة')
                    ->schema([
                        Forms\Components\TextInput::make('gems')
                            ->label('عدد الجواهر 💎')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->suffix('جوهرة')
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('price')
                            ->label('السعر')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('EGP')
                            ->suffix('جنيه')
                            ->columnSpan(1),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('الباقة نشطة')
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(2),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('gems')
                    ->label('عدد الجواهر 💎')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('price')
                    ->label('السعر')
                    ->money('EGP')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشطة')
                    ->boolean()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('order')
                    ->label('الترتيب')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('نشطة فقط')
                    ->falseLabel('غير نشطة فقط'),
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
            'index' => Pages\ListPackages::route('/'),
            'create' => Pages\CreatePackage::route('/create'),
            'edit' => Pages\EditPackage::route('/{record}/edit'),
        ];
    }
}
