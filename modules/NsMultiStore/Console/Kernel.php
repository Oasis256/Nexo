<?php

namespace Modules\NsMultiStore\Console;

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
    public function schedule(Schedule $schedule)
    {
        // $schedule->job(new StoreExecuteExpensesJob)->daily('00:01');
        // $schedule->job(new StoreStockProcurementJob)->daily('00:05');
        // $schedule->job(new StorePurgeOrderStorageJob)->daily('15:00');
        // $schedule->job(new StoreClearHoldOrdersJob)->dailyAt('14:00');
        // $schedule->job(new StoreTrackLaidAwayOrdersJob)->dailyAt('13:00'); // we don't want all job to run daily at the same time
    }
}
