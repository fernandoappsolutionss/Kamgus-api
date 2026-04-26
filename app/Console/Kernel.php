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
        Commands\Admin\CancelOldServices::class,
        Commands\Admin\DisableDriverWithSlowBalance::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command("refresh:old_services")->everyFiveMinutes()->withoutOverlapping();
        $schedule->command("driver:balance")->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('queue:work --stop-when-empty')
            ->everyMinute()
            ->withoutOverlapping()            
            ->sendOutputTo(storage_path() . '/logs/queue-jobs.log');
        $schedule->command('queue:restart')
            ->everyMinute()
            ->withoutOverlapping()
            ->sendOutputTo(storage_path() . '/logs/queue-jobs.log');
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
