<?php /** @noinspection PhpUndefinedFieldInspection */

namespace App\Filament\Resources;

use App\Filament\Resources\AssignmentResource\Pages;
use App\Models\Assignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('client')->required(),
                Forms\Components\Select::make('status')->options([
                    'Un-Scheduled', 'Scheduled'
                ])->default(0),
                Forms\Components\Radio::make('inspection_type')->label('Inspection Type')->inline()
                    ->inlineLabel(false)
                    ->columnSpanFull()
                    ->options(['Basic Inspection', 'Fannie Mae Inspection', 'Repairs Verification', 'Freddie Mac Inspection']),
                Forms\Components\DatePicker::make('start_date')->required(),
                Forms\Components\DatePicker::make('due_date')->required(),
                Forms\Components\TextInput::make('property_name')->required(),
                Forms\Components\TextInput::make('loan_number')->required(),
                Forms\Components\TextInput::make('city')->required(),
                Forms\Components\TextInput::make('state')->required(),
                Forms\Components\TextInput::make('zip')->required(),
                Forms\Components\Select::make('user_id')->label('Inspector Name')->required()
                    ->relationship('user', 'name', modifyQueryUsing: fn($query) => $query->where('is_admin', false))
                    ->searchable()
                    ->preload(),
                Forms\Components\Section::make('Payment Details')
                    ->statePath('payment_info')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('payment_amount')->inputMode('decimal'),
                        Forms\Components\DatePicker::make('payment_date'),
                        Forms\Components\DatePicker::make('invoice_date'),
                        Forms\Components\TextInput::make('invoice_amount')->inputMode('decimal'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->is_admin) {
            return $table
                ->emptyStateHeading('No Upcoming Assignments')
                ->actionsColumnLabel('Actions')
                ->columns([
                    Tables\Columns\TextColumn::make('client')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('status')
                        ->formatStateUsing(fn($state) => $state ? 'Scheduled' : 'Un-Scheduled'),
                    Tables\Columns\TextColumn::make('user.name')->label('Inspector Name'),
                    Tables\Columns\TextColumn::make('inspection_type')
                        ->formatStateUsing(function ($state) {
                            $types = ['Basic Inspection', 'Fannie Mae Inspection', 'Repairs Verification', 'Freddie Mac Inspection'];
                            return $types[$state];
                        }), Tables\Columns\TextColumn::make('start_date'),
                    Tables\Columns\TextColumn::make('due_date'),
                    Tables\Columns\TextColumn::make('property_name')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('loan_number')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('city'),
                    Tables\Columns\TextColumn::make('state'),
                    Tables\Columns\TextColumn::make('zip'),
                    Tables\Columns\IconColumn::make('is_completed')
                        ->label('Completed')
                        ->boolean(),
                    Tables\Columns\TextColumn::make('payment_info')->label('Payment Details')
                    ->listWithLineBreaks()->default('No Payment Data')
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),

                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ]),
                ]);
        } else {
            return $table
                ->emptyStateHeading('No Upcoming Assignments')
                ->actionsColumnLabel('Actions')
                ->columns([
                    Tables\Columns\TextColumn::make('client')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('start_date'),
                    Tables\Columns\TextColumn::make('inspection_type')
                        ->formatStateUsing(function ($state) {
                            $types = ['Basic', 'Fannie Mae', 'Repairs Verification', 'Freddie Mac'];
                            return $types[$state];
                        }), Tables\Columns\TextColumn::make('due_date'),
                    Tables\Columns\TextColumn::make('property_name')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('loan_number')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('city'),
                    Tables\Columns\TextColumn::make('state'),
                    Tables\Columns\TextColumn::make('zip'),
                    Tables\Columns\ToggleColumn::make('is_completed')
                        ->disabled(fn($state) => $state)
                        ->label('Mark as Complete (Irreversible)')
                        ->afterStateUpdated(fn($state) => $state ? Notification::make()->title('Marked as Complete!')->success()->send() : Notification::make()->title('Marked as Incomplete!')->danger()->send())

                ])->actions([
                    Tables\Actions\Action::make('create_inspection')
                        ->icon('heroicon-o-plus-circle')
                        ->color('primary')
                        ->action(function ($record) {
                            \Illuminate\Support\Facades\Session::flash('assignment_data', $record);
                            return redirect(InspectionResource::getUrl('create'));
                        })
                ])->recordUrl(null);
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
