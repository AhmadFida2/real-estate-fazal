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
            ->query(Assignment::query()->latest()->take(5))
            ->columns([
                Tables\Columns\TextColumn::make('client'),
                Tables\Columns\TextColumn::make('inspection_type'),
                Tables\Columns\TextColumn::make('start_date'),
                Tables\Columns\TextColumn::make('due_date')
                ->formatStateUsing(fn($state) => Carbon::parse($state)->diffForHumans()),
                Tables\Columns\TextColumn::make('property_name'),
                Tables\Columns\TextColumn::make('loan_number'),
                Tables\Columns\TextColumn::make('city'),
                Tables\Columns\TextColumn::make('state'),
                Tables\Columns\TextColumn::make('zip'),
            ]);

    }

    public static function canView(): bool
    {
        return !auth()->user()->is_admin;
    }
}
