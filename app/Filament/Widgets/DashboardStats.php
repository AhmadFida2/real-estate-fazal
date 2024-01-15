<?php

namespace App\Filament\Widgets;

use App\Models\Inspection;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends BaseWidget
{
    protected function getStats(): array
    {
        if(auth()->user()->is_admin)
        {
            return [
                Stat::make('Total Users', User::query()->count())
                    ->description('Total Application Users')
                    ->descriptionIcon('heroicon-m-user')
                    ->color('success'),
                Stat::make('Total Inspections', Inspection::query()->count())
                    ->description('Total Inspections in Database')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('primary'),
            ];
        }
        else
        {
            return [
                Stat::make('Total Inspections', Inspection::query()->where('user_id',auth()->id())->count())
                    ->description('Inspection Conducted by You')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('primary'),
            ];
        }

    }
}
