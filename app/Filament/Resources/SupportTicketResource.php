<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportTicketResource\Pages;
use App\Models\SupportTicket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'الدعم الفني';

    public static function getNavigationLabel(): string
    {
        return 'رسائل الدعم';
    }

    public static function getPluralModelLabel(): string
    {
        return 'رسائل الدعم';
    }

    public static function getModelLabel(): string
    {
        return 'رسالة';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('المستخدم')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->placeholder('مستحدم مسجل (اختياري)'),
                Forms\Components\Select::make('issue_type_id')
                    ->label('نوع المشكلة')
                    ->relationship('issueType', 'name_ar')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('الاسم')
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->maxLength(255),
                Forms\Components\Textarea::make('message')
                    ->label('الرسالة')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'open' => 'مفتوحة',
                        'pending' => 'قيد الانتظار',
                        'closed' => 'مغلقة',
                    ])
                    ->default('open')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->searchable()
                    ->placeholder('زائر'),
                Tables\Columns\TextColumn::make('issueType.name_ar')
                    ->label('نوع المشكلة')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المرسل')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد')
                    ->searchable(),
                Tables\Columns\SelectColumn::make('status')
                    ->label('الحالة')
                    ->options([
                        'open' => 'مفتوحة',
                        'pending' => 'قيد الانتظار',
                        'closed' => 'مغلقة',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإرسال')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'open' => 'مفتوحة',
                        'pending' => 'قيد الانتظار',
                        'closed' => 'مغلقة',
                    ]),
                Tables\Filters\SelectFilter::make('issue_type_id')
                    ->relationship('issueType', 'name_ar')
                    ->label('نوع المشكلة'),
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
            'index' => Pages\ListSupportTickets::route('/'),
            'create' => Pages\CreateSupportTicket::route('/create'),
            'view' => Pages\ViewSupportTicket::route('/{record}'),
            'edit' => Pages\EditSupportTicket::route('/{record}/edit'),
        ];
    }
}
