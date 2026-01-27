<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class ManageProfile extends Page
{
    protected static string $view = 'filament.pages.manage-profile';

    protected static ?string $navigationLabel = 'الملف الشخصي';

    protected static ?string $title = 'الملف الشخصي';

    protected static ?string $navigationGroup = 'الإدارة';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Auth::user()->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات الحساب')
                    ->schema([
                        TextInput::make('name')
                            ->label('الاسم')
                            ->required(),
                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->required()
                            ->unique(ignoreRecord: true),
                    ])->columns(2),

                Section::make('تغيير كلمة المرور')
                    ->description('اترك الحقول فارغة إذا كنت لا تريد تغيير كلمة المرور')
                    ->schema([
                        TextInput::make('new_password')
                            ->label('كلمة المرور الجديدة')
                            ->password()
                            ->minLength(8),
                        TextInput::make('new_password_confirmation')
                            ->label('تأكيد كلمة المرور الجديدة')
                            ->password()
                            ->same('new_password'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('حفظ التغييرات')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
        ];

        if (!empty($data['new_password'])) {
            $updateData['password'] = Hash::make($data['new_password']);
        }

        $user->update($updateData);

        Notification::make()
            ->title('تم تحديث الملف الشخصي بنجاح')
            ->success()
            ->send();
            
        $this->form->fill($user->attributesToArray());
    }
}
