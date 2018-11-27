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
        // Commands\Inspire::class,
           Commands\UpdateCallRecords::class,
           Commands\SendSmsNotification::class,
           Commands\UpdateSmsRecords::class,
           Commands\SiSchedulePost::class,
           Commands\YextPullReviews::class,
           Commands\PostQueued::class,
           Commands\PerfectAudience::class,
           Commands\FetchLeads::class,
           Commands\SendLeadActionEmail::class,
           Commands\DailySummaryReport::class,
           Commands\EmailMarketingQueue::class,
           Commands\FunnelQueue::class,
           Commands\PublishEgHonestdoctor::class
       ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        $schedule->command('send:dailsummaryreport')
        ->everyMinute();
        $schedule->command('hx:callRecords')
            ->hourly();
        $schedule->command('update:ContactsByInfusionLeads')
            ->everyFiveMinutes();
        $schedule->command('send:sms_notification')
            ->everyMinute();
        $schedule->command('retrieve:sms_incoming')
            ->everyMinute();
        $schedule->command('publish:SiSchedulePost')
            ->everyMinute();
        $schedule->command('post:queued')
            ->everyMinute();
        $schedule->command('pull:reviews')
            ->everyThirtyMinutes();
        $schedule->command('update:perfectaudience')
            ->daily();
        $schedule->command('send:leadactionemail')->hourly();
       
            //->timezone('Canada/Pacific')
            //->dailyAt('21:00');
       // $schedule->command('send:EmailMarketingMails')->everyMinute();
        //$schedule->command('send:FunnelMails')->everyMinute();
    }

}
