<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Markdown;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Split::make([
                    Forms\Components\Section::make('Basic Info')
                        ->columns(3)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('email')
                                ->email()
                                ->required(),
                            Forms\Components\TextInput::make('password')
                                ->password()->revealable()->autocomplete(false)
                        ]),
                    Forms\Components\Section::make('Privileges')
                        ->columns(1)
                        ->schema([
                            Forms\Components\Toggle::make('is_admin')->label('Admin')
                                ->required()
                                ->onIcon('heroicon-m-bolt')
                                ->offIcon('heroicon-m-user'),
                            Forms\Components\Toggle::make('is_active')->label('Active')
                                ->onIcon('heroicon-m-check')
                                ->offIcon('heroicon-m-x-mark')
                                ->required()
                                ->default(true),
                        ])->grow(false)
                ])->columnSpanFull()->from('md'),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_admin')->label('Admin')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')->label('Active')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\Action::make('reset_password')->iconButton()->visible(fn($record) => $record->id > 1)
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->action(function (User $record, Component $livewire) {
                        $pass = \Illuminate\Support\Str::random(10);
                        $record->password = Hash::make($pass);
                        $record->save();
                        Notification::make()->title('Password Reset')
                            ->body(Markdown::inline('New Password: **' . $pass . '**'))->persistent()->info()->send();
                    })->requiresConfirmation()->modalHeading('Reset Password')
                    ->modalDescription(fn($record) => Markdown::inline('Are you sure you want to reset the Password of **' . $record->name . '**')),
                Tables\Actions\DeleteAction::make()->iconButton()->visible(fn($record) => $record->id > 1),

            ])
            ->bulkActions([
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
