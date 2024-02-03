<?php

namespace App\Filament\Widgets;

use App\Models\Assignment;
use App\Models\Inspection;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends BaseWidget
{

    protected static ?string $pollingInterval = null;

    protected static ?int $sort = 1;
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
                Stat::make('Total Assignments', Assignment::query()->count())
                    ->description('Total Assignments given to You')
                    ->descriptionIcon('heroicon-m-clipboard-document-list')
                    ->color('primary'),
                Stat::make('Completed Assignments', Assignment::query()->where('is_completed',1)->count())
                    ->description('Assignments Completed by You')
                    ->descriptionIcon('heroicon-m-check-badge')
                    ->color('success'),
                Stat::make('Pending Assignments', Assignment::query()->where('is_completed',0)->count())
                    ->description('Assignment Currently Pending')
                    ->descriptionIcon('heroicon-m-x-circle')
                    ->color('warning'),
                Stat::make('Total Inspections', Inspection::query()->where('user_id',auth()->id())->count())
                    ->description('Inspection Conducted by You')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('primary'),
            ];
        }

    }
}
