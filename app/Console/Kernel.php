<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // /home/simplif1/public_html/api2.simplifies.cl
        // * * * * * cd /home/simplif1/public_html/api2.simplifies.cl && /opt/alt/php81/usr/bin/php artisan schedule:run >> /dev/null 2>&1

        // /opt/alt/php81/usr/bin/php /home/simplif1/public_html/api2.simplifies.cl/artisan app:load_tail


        $schedule->command('app:load_tail')->everyTenSeconds();
        // $schedule->command('organize_queue')->everyTenSeconds();
        // $schedule->call(function () {
        //     Log::info("Funcion ejecutandose cada 10");
        // })->everyTenSeconds();
       // $schedule->command('task:execute 12:00')->daily(); // Ejemplo: Ejecutar todos los días a las 12:00
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
