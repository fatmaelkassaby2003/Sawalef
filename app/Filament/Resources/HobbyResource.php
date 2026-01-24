<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HobbyResource\Pages;
use App\Filament\Resources\HobbyResource\RelationManagers;
use App\Models\Hobby;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HobbyResource extends Resource
{
    protected static ?string $model = Hobby::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $modelLabel = 'هواية';
    protected static ?string $pluralModelLabel = 'الهوايات';
    protected static ?string $navigationGroup = 'إدارة المحتوى';

    protected static ?int $navigationSort = 3;

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
                    ->directory('hobby_icons'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon')
                    ->label('الأيقونة')
                    ->alignCenter()
                    ->circular()
                    ->getStateUsing(fn ($record) => (str_contains($record->icon ?? '', '.') || str_contains($record->icon ?? '', '/')) ? $record->icon : null)
                    ->defaultImageUrl('https://ui-avatars.com/api/?name=%E2%9D%A4&color=FFFFFF&background=ec4899&size=128'),
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الهواية')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->tooltip('عرض'),
                Tables\Actions\EditAction::make()->iconButton()->tooltip('تعديل'),
                Tables\Actions\Action::make('delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->iconButton()
                    ->tooltip('حذف')
                    ->extraAttributes([
                        'onclick' => 'if (!confirm("هل أنت متأكد من حذف هذه الهواية؟")) { event.stopPropagation(); return false; }'
                    ])
                    ->action(function ($record) {
                        try {
                            $record->delete();
                            
                            \Filament\Notifications\Notification::make()
                                ->title('تم الحذف بنجاح')
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('فشل الحذف')
                                ->danger()
                                ->send();
                        }
                    })
                    ->after(function () {
                        return redirect()->to(request()->header('Referer'));
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true;
    }

    public static function canDeleteAny(): bool
    {
        return true;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHobbies::route('/'),
            'create' => Pages\CreateHobby::route('/create'),
            'view' => Pages\ViewHobby::route('/{record}'),
            'edit' => Pages\EditHobby::route('/{record}/edit'),
        ];
    }
}
