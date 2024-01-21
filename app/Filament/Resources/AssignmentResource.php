<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssignmentResource\Pages;
use App\Filament\Resources\AssignmentResource\RelationManagers;
use App\Models\Assignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required(),
                Forms\Components\TextInput::make('address')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('user_id')->relationship('user', 'name')
            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->is_admin) {
            return $table
                ->emptyStateHeading('No Upcoming Assignments')
                ->columns([
                    Tables\Columns\TextColumn::make('date')
                        ->date()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('user.name'),
                    Tables\Columns\TextColumn::make('address')->words(8),
                    Tables\Columns\IconColumn::make('is_completed')
                        ->label('Completed')
                        ->boolean()
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ]),
                ]);
        } else {
            return $table
                ->emptyStateHeading('No Upcoming Assignments')
                ->columns([
                    Tables\Columns\TextColumn::make('date')
                        ->date()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('address')->words(8),
                    Tables\Columns\ToggleColumn::make('is_completed')
                        ->label('Mark as Complete')
                    ->afterStateUpdated(fn($state)=> $state ? Notification::make()->title('Marked as Complete!')->success()->send() : Notification::make()->title('Marked as Incomplete!')->danger()->send())

                ]);
        }
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
            'index' => Pages\ListAssignments::route('/'),
            'create' => Pages\CreateAssignment::route('/create'),
            'edit' => Pages\EditAssignment::route('/{record}/edit'),
        ];
    }
}
