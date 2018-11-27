<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\InfusionsoftController;
class FetchLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:ContactsByInfusionLeads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to update the  leads information from infusion';

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
        InfusionsoftController::fetchContactDetails();
    }
}
