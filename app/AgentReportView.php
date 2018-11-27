<?php

namespace App;
use Carbon\Carbon;
use App\Classes\CompanySettingsHelper;
use Illuminate\Database\Eloquent\Model;

class AgentReportView extends Model
{
    protected $table = 'agent_report_view';


    public function getAvgLeadTimeeAttribute($value)
    {
    	if($value!=null){
			$t = round($value);
			return sprintf('%02d:%02d:%02d', ($t/3600),($t/60%60), $t%60);
    	}else{
    		return '00:00:00';
    	}
         
    }
    	
}



