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
                Forms\Components\TextInput::make('client'),
                Forms\Components\Select::make('status')->options([
                    'Un-Scheduled','Scheduled'
                ])->default(0),
                Forms\Components\DatePicker::make('start_date'),
                Forms\Components\DatePicker::make('due_date'),
                Forms\Components\TextInput::make('property_name'),
                Forms\Components\TextInput::make('city'),
                Forms\Components\TextInput::make('state'),
                Forms\Components\TextInput::make('zip'),
                Forms\Components\Select::make('user_id')->label('Inspector Name')
                    ->relationship('user', 'name', modifyQueryUsing: fn($query) => $query->where('is_admin', false))
                    ->searchable()
                    ->preload()
            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->is_admin) {
            return $table
                ->emptyStateHeading('No Upcoming Assignments')
                ->columns([
                    Tables\Columns\TextColumn::make('client')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(fn($state)=> $state? 'Scheduled':'Un-Scheduled'),
                    Tables\Columns\TextColumn::make('user.name')->label('Inspector Name'),
                    Tables\Columns\TextColumn::make('start_date'),
                    Tables\Columns\TextColumn::make('due_date'),
                    Tables\Columns\TextColumn::make('property_name'),
                    Tables\Columns\TextColumn::make('city'),
                    Tables\Columns\TextColumn::make('state'),
                    Tables\Columns\TextColumn::make('zip'),
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
                    Tables\Columns\TextColumn::make('client')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('status')
                        ->formatStateUsing(fn($state)=> $state? 'Scheduled':'Un-Scheduled'),
                    Tables\Columns\TextColumn::make('start_date'),
                    Tables\Columns\TextColumn::make('due_date'),
                    Tables\Columns\TextColumn::make('property_name'),
                    Tables\Columns\TextColumn::make('city'),
                    Tables\Columns\TextColumn::make('state'),
                    Tables\Columns\TextColumn::make('zip'),
                    Tables\Columns\ToggleColumn::make('is_completed')
                        ->disabled(fn($state) => $state)
                        ->label('Mark as Complete (Irreversible)')
                        ->afterStateUpdated(fn($state) => $state ? Notification::make()->title('Marked as Complete!')->success()->send() : Notification::make()->title('Marked as Incomplete!')->danger()->send())

                ])->actions([]);
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
