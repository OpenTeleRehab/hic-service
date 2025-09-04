<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('hi:sync-category-data')->dailyAt('0:00')->runInBackground();
        $schedule->command('hi:sync-exercise-data')->dailyAt('0:10')->runInBackground();
        $schedule->command('hi:sync-education-material-data')->dailyAt('0:20')->runInBackground();
        $schedule->command('hi:sync-questionnaire-data')->dailyAt('0:30')->runInBackground();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
