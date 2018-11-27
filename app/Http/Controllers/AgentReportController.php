<?php

namespace App\Http\Controllers;

use App\Classes\LeadHelper;
use App\Http\Controllers\LeadsController;
use Auth;
use Datatables;
use Input;

class AgentReportController extends Controller
{
    public function postIndex()
    {
        $Input = Input::get();
        $user_id = "";
        if (isset($Input['customFilter']['lead_status']) && $Input['customFilter']['lead_status'] != 'empty') {
            $user_id = $Input['customFilter']['lead_status'];
        }
        $start_date = $Input['customFilter']['start_time'];
        $end_date = $Input['customFilter']['end_time'];
        $user = Auth::user();
        $company_id = $user->company_id;
        $assignees = LeadHelper::getAssigneeReport($company_id, $user_id, $start_date, $end_date);
        return Datatables::of($assignees)->make(true);
    }

    public function getAssignees()
    {
        $lc = new LeadsController;
        return $lc->getLeadAssignees();
    }
}
