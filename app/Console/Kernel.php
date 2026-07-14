<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * アプリケーションの定期実行処理を定義します。
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule
            ->command('tasks:send-due-notifications')
            ->name('tasks:send-due-notifications-daily')
            ->dailyAt('09:00')
            ->timezone(
                config('app.timezone', 'Asia/Tokyo')
            )
            ->withoutOverlapping(60);
    }

    /**
     * アプリケーションのArtisan Commandを登録します。
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}