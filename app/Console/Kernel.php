<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('queue:work')->everyMinute()->withoutOverlapping();
        $schedule->command('clear:temp-urls')->everyThirtyMinutes();
        $schedule->command('app:clear-public')->hourly();
        $schedule->call(function () {
            cache()->forget('daily_inspections');
        })->daily()->at('00:00');
        $schedule->call(function () {
            cache()->forget('daily_assignments');
        })->daily()->at('00:00');

    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

}
