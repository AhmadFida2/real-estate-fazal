<?php

namespace App\Livewire;


use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class UserProfile extends BaseEditProfile
{
    protected ?string $heading = 'User Profile';

    protected ?string $maxWidth = '2xl';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                ViewField::make('account_status')->label('Account Status')
                    ->view('account-status-badge'),
                Section::make('Basic Profile')
                    ->aside()
                    ->description('Edit User Name & Email Address')
                    ->schema([
                        TextInput::make('name'),
                        TextInput::make('email')->email()
                    ]),
                Section::make('Password Section')
                    ->aside()
                    ->description('Change User Password')
                    ->schema([
                        TextInput::make('current_password')->currentPassword()->filled(fn($get) => !empty($get('password'))),
                        TextInput::make('password')->label('New Password')->confirmed()->password()->revealable()->different('current_password'),
                        TextInput::make('password_confirmation')->label('Confirm New Password')
                    ]),
            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['password'])) {
            unset($data['password']);
        }
        return $data;
    }
}
