<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(\Illuminate\Console\Scheduling\Schedule $schedule): void
    {
        // 定時任務排程
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
