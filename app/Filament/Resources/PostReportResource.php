<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostReportResource\Pages;
use App\Models\PostReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PostReportResource extends Resource
{
    protected static ?string $model = PostReport::class;

    protected static ?string $navigationIcon  = 'heroicon-o-flag';
    protected static ?string $navigationLabel = 'بلاغات البوستات';
    protected static ?string $navigationGroup = 'الإشراف';
    protected static ?int    $navigationSort  = 1;
    protected static ?string $modelLabel      = 'بلاغ';
    protected static ?string $pluralModelLabel = 'البلاغات';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('reporter_id')
                    ->label('المُبلِّغ')
                    ->relationship('reporter', 'name')
                    ->disabled(),

                Forms\Components\Select::make('post_id')
                    ->label('البوست')
                    ->relationship('post', 'id')
                    ->disabled(),

                Forms\Components\TextInput::make('reason')
                    ->label('السبب')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reporter.name')
                    ->label('المُبلِّغ')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('post_id')
                    ->label('ID البوست')
                    ->sortable()
                    ->url(fn ($record) => "/admin/posts/{$record->post_id}"),

                Tables\Columns\TextColumn::make('post.content')
                    ->label('محتوى البوست')
                    ->limit(60)
                    ->placeholder('(صورة فقط)'),

                Tables\Columns\TextColumn::make('reason')
                    ->label('السبب')
                    ->placeholder('لم يُحدد')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ البلاغ')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('has_reason')
                    ->label('بوستات ذات سبب')
                    ->query(fn ($query) => $query->whereNotNull('reason')),
            ])
            ->actions([
                Tables\Actions\Action::make('view_post')
                    ->label('عرض البوست')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => "/admin/posts/{$record->post_id}"),

                Tables\Actions\DeleteAction::make()
                    ->label('حذف البلاغ'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPostReports::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
