<?php /** @noinspection PhpUndefinedFieldInspection */

namespace App\Filament\Resources;

use App\Filament\Resources\AssignmentResource\Pages;
use App\Models\Assignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Assignment Details')->columns(3)
                    ->schema([
                        Forms\Components\Radio::make('inspection_type')->label('Inspection Type')->inline()
                            ->inlineLabel(false)
                            ->columnSpanFull()
                            ->options(['Basic Inspection', 'Fannie Mae Inspection', 'Repairs Verification', 'Freddie Mac Inspection']),
                        Forms\Components\TextInput::make('client')->required(),
                        Forms\Components\Select::make('status')->options([
                            'Un-Scheduled', 'Scheduled'
                        ])->default(0),
                        Forms\Components\DatePicker::make('start_date')->required(),
                        Forms\Components\DatePicker::make('due_date')->required(),
                        Forms\Components\TextInput::make('property_name')->required(),
                        Forms\Components\TextInput::make('property_address')->required(),
                        Forms\Components\TextInput::make('loan_number')->required(),
                        Forms\Components\TextInput::make('investor_number')->required(),
                        Forms\Components\TextInput::make('city')->required(),
                        Forms\Components\TextInput::make('state')->required(),
                        Forms\Components\TextInput::make('zip')->required(),
                        Forms\Components\Select::make('user_id')->label('Inspector Name')->required()
                            ->relationship('user', 'name', modifyQueryUsing: fn($query) => $query->where('is_admin', false))
                            ->searchable()
                            ->preload(),
                    ]),
                Forms\Components\Section::make('Payment Details')
                    ->description('Visible only to Admins')
                    ->statePath('payment_info')
                    ->columns(3)
                    ->schema([
                        Forms\Components\DatePicker::make('invoice_date'),
                        Forms\Components\TextInput::make('invoice_amount')->inputMode('decimal'),
                    ]),
                Forms\Components\Section::make('Payments')
                ->schema([
                    Forms\Components\Repeater::make('payments')
                    ->addActionLabel('New Client Payment')
                    ->columns(2)
                    ->relationship()
                    ->schema([
                        Forms\Components\DatePicker::make('date'),
                        Forms\Components\TextInput::make('amount')->inputMode('decimal'),
                    ])
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
                    Tables\Columns\TextColumn::make('property_name')->searchable(),
                    Tables\Columns\TextColumn::make('property_address')->searchable(),
                    Tables\Columns\TextColumn::make('loan_number')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('investor_number')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('city'),
                    Tables\Columns\TextColumn::make('payment_info.invoice_date')->label('Invoice Date')->dateTime('d M Y'),
                    Tables\Columns\TextColumn::make('payment_info.invoice_amount')->label('Invoice Amount')->money('USD'),
                    Tables\Columns\TextColumn::make('state'),
                    Tables\Columns\TextColumn::make('zip'),
                    Tables\Columns\IconColumn::make('is_completed')
                        ->label('Completed')
                        ->boolean(),
                ])
                ->actions([
                    Tables\Actions\Action::make('payment_data')->icon('heroicon-o-currency-dollar')
                        ->iconButton()->color('secondary')
                        ->infolist([
                            Grid::make(1)
                                ->schema([
                                   RepeatableEntry::make('payments')->columns(2)
                                    ->schema([
                                        TextEntry::make('date'),
                                        TextEntry::make('amount')->money('USD')
                                    ])
                                ])
                        ])
                        ->modalHeading('Payment Details')->closeModalByClickingAway()->modalAlignment(Alignment::Center)->modalFooterActions(fn() => []),
                    Tables\Actions\Action::make('download')->iconButton()->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($record) {
                            $assignment = $record;
                            $file_name = 'storage/invoices/invoice_' . $record->id . ".pdf";
                            if(!Storage::directoryExists(public_path('storage/invoices')))
                            {
                                Storage::disk('public')->makeDirectory('invoices');
                            }
                            if (file_exists(public_path($file_name))) {
                                return response()->download(public_path($file_name));
                            }
                            Pdf::view('invoice', compact('assignment'))->save(public_path($file_name));
                            return response()->download(public_path($file_name));
                        }),
                    Tables\Actions\EditAction::make()->iconButton(),
                    Tables\Actions\DeleteAction::make()->iconButton(),

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
                    Tables\Columns\TextColumn::make('property_name')->searchable(),
                    Tables\Columns\TextColumn::make('property_address')->searchable(),
                    Tables\Columns\TextColumn::make('loan_number')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('investor_number')
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
