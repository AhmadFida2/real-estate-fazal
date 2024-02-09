<?php

namespace App\Filament\Widgets;

use App\Models\Assignment;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AssignmentTable extends BaseWidget
{
    protected static ?string $heading = 'Upcoming Assignments';
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(Assignment::query()->orderBy('due_date')->take(5))
            ->columns([
                Tables\Columns\TextColumn::make('client'),
                Tables\Columns\TextColumn::make('inspection_type')
                ->formatStateUsing(function($state){
                    $types = ['Basic Inspection', 'Fannie Mae Inspection', 'Repairs Verification', 'Freddie Mac Inspection'];
                    return $types[$state];
                }),
                Tables\Columns\TextColumn::make('start_date'),
                Tables\Columns\TextColumn::make('due_date')
                ->since(),
                Tables\Columns\TextColumn::make('property_name'),
                Tables\Columns\TextColumn::make('loan_number'),
                Tables\Columns\TextColumn::make('city'),
                Tables\Columns\TextColumn::make('state'),
                Tables\Columns\TextColumn::make('zip'),
            ])->recordClasses(fn($record)=> match (Carbon::parse($record->due_date)->isPast()){
                true => 'bg-red-200',
                false => null
            });

    }

    public static function canView(): bool
    {
        return !auth()->user()->is_admin;
    }
}
