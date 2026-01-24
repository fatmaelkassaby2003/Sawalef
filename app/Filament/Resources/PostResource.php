<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Filament\Resources\PostResource\RelationManagers;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $modelLabel = 'منشور';
    protected static ?string $pluralModelLabel = 'المنشورات';
    protected static ?string $navigationGroup = 'إدارة المحتوى';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('المستخدم')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Textarea::make('content')
                    ->label('المحتوى')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('image')
                    ->label('الصور')
                    ->image()
                    ->directory('post_images'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('الصورة')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('content')
                    ->label('المحتوى')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ النشر')
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
                        'onclick' => 'if (!confirm("هل أنت متأكد من حذف هذا المنشور؟")) { event.stopPropagation(); return false; }'
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
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'view' => Pages\ViewPost::route('/{record}'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
