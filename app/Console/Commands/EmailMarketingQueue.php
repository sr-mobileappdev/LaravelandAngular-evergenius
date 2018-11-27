<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\EmailMarketingController;
class EmailMarketingQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:EmailMarketingMails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to send marketing mails';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        EmailMarketingController::campaignMailJob();
    }
}
