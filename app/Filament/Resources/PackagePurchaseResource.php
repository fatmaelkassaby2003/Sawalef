<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackagePurchaseResource\Pages;
use App\Models\PackagePurchase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PackagePurchaseResource extends Resource
{
    protected static ?string $model = PackagePurchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'مشتريات الباقات';

    protected static ?string $modelLabel = 'عملية شراء';

    protected static ?string $pluralModelLabel = 'مشتريات الباقات';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('تفاصيل الشراء')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('المستخدم')
                            ->relationship('user', 'name')
                            ->required()
                            ->disabled(),
                        
                        Forms\Components\Select::make('package_id')
                            ->label('الباقة')
                            ->relationship('package', 'name')
                            ->required()
                            ->disabled(),
                        
                        Forms\Components\Select::make('wallet_transaction_id')
                            ->label('رقم المعاملة المالية')
                            ->relationship('walletTransaction', 'reference_number')
                            ->disabled(),
                        
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'completed' => 'مكتملة',
                                'failed' => 'فشلت',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('التفاصيل المالية')
                    ->schema([
                        Forms\Components\TextInput::make('price_paid')
                            ->label('المبلغ المدفوع')
                            ->numeric()
                            ->prefix('EGP')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('gems_received')
                            ->label('الجواهر المستلمة')
                            ->numeric()
                            ->suffix('جوهرة')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('package.name')
                    ->label('الباقة')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('price_paid')
                    ->label('المبلغ')
                    ->money('EGP')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('gems_received')
                    ->label('الجواهر')
                    ->numeric()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'completed' => 'مكتملة',
                        'failed' => 'فشلت',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الشراء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('package')
                    ->relationship('package', 'name')
                    ->label('الباقة'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(), // Modal view
            ])
            ->bulkActions([
                // No delete
            ])
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(10);
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
            'index' => Pages\ListPackagePurchases::route('/'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
}
