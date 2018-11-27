<?php

namespace App\Http\Controllers;

use App\Classes\FunnelHelper;
use App\Classes\LeadHelper;
use App\EmFunnel;
use App\User;
use Auth;
use Datatables;
use Illuminate\Http\Request;
use Input;

class FunnelController extends Controller
{
    public function postCreate(Request $request)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $data = $request->all();
        $status = 1;
        $status = FunnelHelper::setFunnel($data, $company_id, $status);
        if ($status) {
            return response()->success(['status' => 'success', 'funnel_id' => $status]);
        } else {
            return response()->error('Something went wrong !!');
        }
    }

    public function postList()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $data = FunnelHelper::fetchFunnels($company_id);
        return Datatables::of($data)->make(true);
    }

    public function getShow($funnel_id)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $status = FunnelHelper::getFunnel($company_id, $funnel_id);
        return response()->success($status);
    }

    public function deleteRemove($funnel_id)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $status = FunnelHelper::delFunnel($company_id, $funnel_id);
        if ($status) {
            return response()->success('Funnel deleted successfully');
        }
        return response()->error('Funnel does not exista');
    }

    public function postUpdate()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $data = Input::get();
        $status = $data['status'];
        $status = FunnelHelper::updateFunnel($data, $company_id, $status);
        if ($status) {
            return response()->success('Funnel saved successfully');
        } else {
            return response()->error('Something went wrong !!');
        }
    }

    public function postCreateActionStep()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $data = Input::get();
        $status = 2;
        $res = FunnelHelper::setActionStep($data, $company_id, $status);
        if ($res) {
            return response()->success(array('message' => 'Action step created successfully', 'step_id' => $res));
        }
        return response()->error('Something went wrong');
    }

    public function getListActionSteps($funnel_id)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $funnel = FunnelHelper::getFunnel($company_id, $funnel_id);
		if($funnel){
			$res = FunnelHelper::getFunnelActionSteps($funnel_id);
			$apt_status = FunnelHelper::getAppointmentStatus();
			$stages = LeadHelper::getAllStages($company_id);
			$service = LeadHelper::getServices($company_id);
			//$source                 = ContactHelper::getTermsByType($company_id,'source');
			$opportunity_status = LeadHelper::getleadstatuses();
			return response()->success(compact('res', 'funnel', 'apt_status', 'stages', 'service', 'source', 'opportunity_status'));
		}
		return response()->error('Invalid funnel Id');

    }

    public function postActionStep($step_id)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $data = Input::get();
        $res = FunnelHelper::updateActionSteps($data, $step_id, $company_id);
        if ($res) {
            return response()->success('Action step updated successfully');
        }
        return response()->success('Something went wrong !!');
    }

    public function deleteActionStep($step_id)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $found = FunnelHelper::findFunnelStepCompanyID($step_id);
        $found_id = isset($found['company_id']) ? $found['company_id'] : 0;
        if ($company_id == $found_id) {
            $res = FunnelHelper::deleteStep($step_id, $found['funnel_id']);
            if ($res) {
                return response()->success('Step deleted successfully');
            }
            return response()->error('No such step found');
        }
        return response()->error('Invalid company id');
    }

    public function postFunnelJob()
    {
        FunnelHelper::funnelCron(); //Add records to queue
        FunnelHelper::SendActionMailOrMessage(); //Send message user
    }

    public function postActionRule(Request $request)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $data = Input::all();
        $status = FunnelHelper::setStepRule($data, $company_id);
        if ($status) {
            return response()->success('Action rule created successfully');
        }
        return response()->error('Something went wrong with create action rule !!');
    }

    public function deleteActionRule($rule_id)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $stat = FunnelHelper::deleteActionRule($rule_id, $company_id);
        if ($stat) {
            return response()->success('Rule deleted successfully');
        }
        return response()->error('Invalid request !!');
    }

    public function postRecipients()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input_data = Input::get();
        $response = array();
        $sent_email_count = array();
        $recipients = array();
        if (isset($input_data)) {
            $input_data = $input_data['customFilter'];
            $company = EmFunnel::select('company_id')->where('id', $input_data['funnel_id'])->first();
            if ($company->company_id == $company_id) {
                $recipients = FunnelHelper::fetchSentItemsListing($input_data['funnel_id'], $input_data['action_id'], $input_data['type'], 2);
            } else {
                // return response()->error('Invalid Request !!');
            }
        }

        return Datatables::of(collect($recipients))->make(true);
    }

    public static function runFunnelCron()
    {
        FunnelHelper::funnelCron(); //Add records to queue
        FunnelHelper::SendActionMailOrMessage(); //Send message user
    }

    public function postStep()
    {
        $params = Input::get();
        if (isset($params['funnel_id']) && isset($params['id'])) {
            $user = Auth::user();
            $company_id = $user->company_id;
            $step = FunnelHelper::getFunnelStepById($params['funnel_id'], $params['id'], $company_id);
            if ($step != false) {
                return response()->success($step);
            }
            return response()->error('Invalid funnel or company');
        }
        return response()->error('Invalid funnel or company');
    }

    public function getStepSentCount()
    {
        $input_data = Input::get();
        $sent_email_count = FunnelHelper::getSentEmailCount($input_data['funnel_id'], $input_data['action_id'], $input_data['type'], 2);
        return $sent_email_count;
    }

    public function postCompanyTemplate()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input_data = Input::get();
        $status= FunnelHelper::updateCompanyTemplate($company_id, $input_data);
        if ($status) {
            return response()->success('Template saved');
        }
        return response()->error('Unable to save the template');
    }
    public function postDeleteCompanyTemplate()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input_data = Input::get();
        $status= FunnelHelper::DeleteCompanyTemplate($company_id, $input_data);
        if ($status) {
            return response()->success('Template Deleted');
        }
        return response()->error('Unable to delete the template');
    }
}
