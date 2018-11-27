<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\HonestdoctorController;

class PublishEgHonestdoctor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'publish:eghonestdoctor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        //HonestdoctorController::getCreateExistingClinics();
        HonestdoctorController::getCreateExistingProviders();
    }
}
