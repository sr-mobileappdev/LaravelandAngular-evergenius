<?php

namespace App\Http\Controllers;

use App\Classes\HonestdoctorHelper;
use App\Company;
use App\User;
use App\TempHonestDoctorImport;
use Auth;
use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
//use Illuminate\Support\Facades\Storage;
use Input;

class HonestdoctorController extends Controller
{
    public function getSpecializations()
    {
        $all_Specializations = HonestdoctorHelper::getAllSpecializations();
        return response()->success($all_Specializations);
    }

    public function getFindSpecialization()
    {
        $all_Specializations = HonestdoctorHelper::getAllSpecializations();
        $out = [];
        $like = Input::get('s');
        $result = array_filter($all_Specializations, function ($item) use ($like) {
            if (stripos($item['name'], $like) !== false) {
                return true;
            }
            return false;
        });
        foreach ($result as $key => $value) {
            $out[] = ['text' => $value['name'], 'wp_id' => $value['id'], 'wp_slug' => $value['slug']];
        }
        return $out;
    }

    public function postHonestPosts($type)
    {
        if (in_array((string) $type, ['providers', 'clinics'])) {
            $Input = Input::get();
            if (isset($Input['start']) && isset($Input['length'])) {
                $start = $Input['start'];
                $length = $Input['length'];
                if ($start == 0) {
                    $page_num = 1;
                } else {
                    $page_num = ($start / $length) + 1;
                }
                $wheres = ['page' => $page_num];
            }
            $wheres['status'] = 'draft, publish';
            /* Custom filter and askig for status */
            if (isset($Input['customFilter']['status']) && $Input['customFilter']['status'] != '') {
                $wheres['status'] = $Input['customFilter']['status'];
            }

            /* Filter Claim Request */
            if (isset($Input['customFilter']['claim_status']) && $Input['customFilter']['claim_status'] != '') {
                $wheres['filter']['meta_query'][0]['key'] = 'wpcf_claim_status';
                $wheres['filter']['meta_query'][0]['value'] = $Input['customFilter']['claim_status'];
            }

            /* For Search data */
            if (isset($Input['search'])) {
                $wheres['search'] = $Input['search']['value'];
            }

            if (isset($Input['length'])) {
                $wheres['per_page'] = $Input['length'];
            }

            $posts = HonestdoctorHelper::getPostTypes($type, $wheres);
            if ($posts['content']) {
                $where['status'] = 'publish, draft';
                $data = convertSingleDimenstionalArray($posts['content'], $where);
                $out['recordsFiltered'] = (int) $posts['headers']['X-WP-Total'];
                $out['recordsTotal'] = (int) $posts['headers']['X-WP-Total'];
                $out['data'] = $data;
                $out['draw'] = $Input['draw'];
                $out['Input'] = $Input;
                return response()->json($out);
            }

            $out['recordsFiltered'] = 0;
            $out['recordsTotal'] = 0;
            $out['data'] = [];
            $out['draw'] = $Input['draw'];
            $out['Input'] = $Input;
            return response()->json($out);
            //return Datatables::of($out)->make(true);
        }

        return response()->error('invalid post type');
    }
    public function postAddClinic()
    {
        $company_data = Input::get();
        //print_r($company_data); die;
        $comp_id = null;
        $Specializations = [];
        if (isset($company_data['specialities'])) {
            $Specializations = HonestdoctorHelper::getSpecializationHDPost($company_data['specialities']);
        }
        $media_Id = null;
        if (isset($company_data['image_url'])) {
            $media_Id = HonestdoctorHelper::postMediaHD($company_data['image_url']);
        }
        if (isset($company_data['eg_id']) && $company_data['eg_id'] != '') {
            $comp_id = $company_data['eg_id'];
        }
        $clinics_id = HonestdoctorHelper::addCompanyHD($company_data, $Specializations, $comp_id, $media_Id);

        if ($clinics_id == true) {
            return response()->success(['status' => 'success']);
        }
    }

    public function getShowHdClinic($clinic_id = null)
    {
        if ($clinic_id != null) {
            $clinics_info = HonestdoctorHelper::getHdPost($clinic_id, 'clinics');
            //print_r($clinics_info); die;
        }
        if (isset($clinics_info['id'])) {
            $clinics[] = $clinics_info;
            $where = [];
            $data = convertSingleDimenstionalArray($clinics, $where)[0];
            //print_r($data);
            $specialities = HonestdoctorHelper::FetchSpecializations($clinics_info['specialties']);
            $data['specialities'] = $specialities;
            $data['image_url'] = '';
            if (isset($data['featured_media']) && $data['featured_media'] != 0) {
                $data['image_url'] = HonestdoctorHelper::getHdMediaUrl($data['featured_media']);
            }
            $clinics_information = $data;
            return response()->success(compact('clinics_information'));
        }
        return response()->error('Clinic not found', 403);
    }

    public function getShowHdProvider($provider_id = null)
    {
        if ($provider_id != null) {
            $pro_info = HonestdoctorHelper::getHdPost($provider_id, 'providers');
        }
        if (isset($pro_info['id'])) {
            $clinics[] = $pro_info;
            $where = [];
            $data = convertSingleDimenstionalArray($clinics, $where)[0];
            $specialities = HonestdoctorHelper::FetchSpecializations($pro_info['specialties']);
            $data['specialities'] = $specialities;
            $data['image_url'] = '';
            if (isset($data['featured_media']) && $data['featured_media'] != 0) {
                $data['image_url'] = HonestdoctorHelper::getHdMediaUrl($data['featured_media']);
            }
            $provider_information = $data;
            return response()->success(compact('provider_information'));
        }
        return response()->error('Clinic not found', 403);
    }

    public function putShowHdClinic($clinic_id = null)
    {
        if ($clinic_id != null) {
            $input = Input::get();
            $input = $input['data']['clinics_information'];
            $media_Id = null;
            if (isset($input['id']) && $input['id'] == $clinic_id) {
                $Specializations = [];
                if (isset($input['specialities'])) {
                    $Specializations = HonestdoctorHelper::getSpecializationHDPost($input['specialities']);
                }
                $company_data = convertHdClinicData($input);
                $is_logo_chng = strpos($input['image_url'], getenv('HONESTDOCTOR_WEBSITE_URL'));

                if (isset($input['image_url']) && $is_logo_chng === false) {
                    $media_Id = HonestdoctorHelper::postMediaHD($input['image_url']);
                }
                HonestdoctorHelper::updateCompanyHD($company_data, $Specializations, null, $media_Id);
                return response()->success(['status' => 'success']);
            }
        }
        return response()->error('Clinic not found', 403);
    }

    public function putShowHdProvider($provider_id = null)
    {
        if ($provider_id != null) {
            $input = Input::get();
            $input = $input['data']['provider_information'];
            if (isset($input['id']) && $input['id'] == $provider_id) {
                $Specializations = [];
                if (isset($input['specialities'])) {
                    $Specializations = HonestdoctorHelper::getSpecializationHDPost($input['specialities']);
                }
                $provider_data = convertHdProviderData($input);
                $is_logo_chng = strpos($input['image_url'], getenv('HONESTDOCTOR_WEBSITE_URL'));
                //print_r($is_logo_chng); die;
                $media_Id = null;
                if (isset($input['image_url']) && $is_logo_chng === false) {
                    $media_Id = HonestdoctorHelper::postMediaHD($input['image_url']);
                }

                HonestdoctorHelper::updateHdProvider($provider_data, $Specializations, null, $provider_id, null, $media_Id);
                return response()->success(['status' => 'success']);
            }
        }
        return response()->error('Provider not found', 403);
    }

    public function postAddProvider()
    {
        $user_data = Input::get();

        $user_id = null;

        if (isset($user_data['eg_id']) && $user_data['eg_id'] != '') {
            $user_id = $user_data['eg_id'];
        }

        $company_id = null;
        $website_url = null;
        $media_Id = null;
        $is_logo_chng = true;
        $Specializations = [];
        if (isset($user_data['specialities'])) {
            $Specializations = HonestdoctorHelper::getSpecializationHDPost($user_data['specialities']);
        }
        if (isset($user_data['image_url']) && empty($user_data['image_url']) === false) {
            $is_logo_chng = strpos($user_data['image_url'], getenv('HONESTDOCTOR_WEBSITE_URL'));
            $media_Id = HonestdoctorHelper::postMediaHD($user_data['image_url']);
        }
        HonestdoctorHelper::createProvider($user_data, $Specializations, $user_id, $company_id, $website_url, $media_Id);
        return response()->success(['status' => 'success']);
    }

    public function deleteHdClinic($clinic_id = null)
    {
        if ($clinic_id != null) {
            HonestdoctorHelper::deletePost('clinics', $clinic_id);
            return response()->success(['status' => 'success']);
        }
        return response()->error('Clinic not found', 403);
    }

    public function deleteHdProvider($provider_id = null)
    {
        if ($provider_id != null) {
            HonestdoctorHelper::deletePost('providers', $provider_id);
            return response()->success(['status' => 'success']);
        }
        return response()->error('Clinic not found', 403);
    }

    public function postSearchCompanies()
    {
        $data = Input::get();
        if (isset($data['searched_text']) && $data['searched_text'] != "") {
            $user = Auth::user();
            $searchtext = $data['searched_text'];
            $user_role = $user->roles()->select(['slug'])->first()->toArray();
            $companies = array();
            $companies = Company::where(function ($query) use ($searchtext) {
                $query->orWhere('name', 'LIKE', "%" . $searchtext);
                $query->orWhere('email', 'LIKE', "%" . $searchtext . "%");
            })
                ->WhereNull('hd_post_id');

            $companies = $companies->get();
            //}

            if ($companies) {
                $contact = $companies->toArray();
                return response()->success($companies);
            }
            return response()->error('No company found !!');
        } else {
            $companies = array();
            return response()->success($companies);
        }
    }

    public function postSearchProviders()
    {
        $data = Input::get();
        if (isset($data['searched_text']) && $data['searched_text'] != "") {
            $searchtext = $data['searched_text'];
            $providers = array();
            $providers = User::where(function ($query) use ($searchtext) {
                $query->orWhere('name', 'LIKE', "%" . $searchtext);
                $query->orWhere('email', 'LIKE', "%" . $searchtext . "%");
            })
                ->WhereNull('hd_provider_id')
                ->whereHas('roles', function ($q) {
                    $q->where('role_id', 5);
                });
            $providers = $providers->get();
            //}

            if ($providers) {
                $providers = $providers->toArray();
                return response()->success($providers);
            }
            return response()->error('No company found !!');
        } else {
            $providers = array();
            return response()->success($providers);
        }
    }

    public static function getCreateExistingClinics()
    {
        $nonHdCompanies = \App\Classes\CompanyHelper::GetNonHdCompanies();
        foreach ($nonHdCompanies as $company) {
            $company_id = $company['id'];
            $media_Id = null;
            $company_name = $company['name'];
            $company_specialities = HonestdoctorHelper::getTermSpecializations('company', $company_id);
            $Specializations = specializationsArray($company_specialities);
            $company_data = $company;
            if (isset($company_data['logo']) && $company_data['logo'] != null) {
                $media_Id = HonestdoctorHelper::postMediaHD(url('/').'/'.$company_data['logo']);
            }
            $clinics_id = HonestdoctorHelper::addCompanyHD($company_data, $Specializations, $company_id, $media_Id);
            if ($clinics_id == true) {
                echo 'Company "' . $company_name . '" Added Succssfully' . "\n";
            }
            sleep(1);
        }
        echo 'All Clinics Updated Successfully' . "\n";
    }

    public static function getCreateExistingProviders()
    {
        $nonHdUsers = \App\Classes\UserHelper::GetNonHdUsers();
        foreach ($nonHdUsers as $user) {
            $userId = $user['id'];
            $companyId = $user['company_id'];
            $mediaId = null;
            $userName = $user['name'];
            
            $company_information = \App\Classes\CompanyHelper::getCompanyDetais($companyId);

            $userSpecialities = HonestdoctorHelper::getTermSpecializations('user', $userId);
            $Specializations = specializationsArray($userSpecialities);
            $userData = $user;
            if (isset($userData['avatar']) && $userData['avatar'] != null) {
                $mediaId = HonestdoctorHelper::postMediaHD($userData['avatar']);
            }
            $websiteurl = $userData['avatar'];
            $twilio_number = \App\Classes\CompanySettingsHelper::getSetting($companyId, 'twilio_number');
            if ($twilio_number!==false && empty($twilio_number)===false) {
                $userData['phone'] = $twilio_number;
            } else {
                $userData['phone'] = $company_information['phone'];
            }
            $providerId = HonestdoctorHelper::createProvider($userData, $Specializations, $userId, $companyId, $websiteurl, $mediaId);
            if ($providerId == true) {
                echo 'Provider "' . $userName . '" Added Succssfully' . "\n";
            }
            sleep(1);
        }
    }

    public function postImportProdivders(Request $request)
    {
        $import_fields_validate = array('name');
        if ($request->providers) {
            $file = Input::file('providers');
            $name = time() . '-' . $file->getClientOriginalName();
            $path = storage_path('hd_providers');
            $file_path = $file->move($path, $name);
            $out = Excel::load($file_path, function ($reader) use ($import_fields_validate) {
                $success_count = 0;
                $Faied_count = 0;
                $contact_exists_count = 0;
                $report = HonestdoctorHelper::createHdProviderFile($reader->toArray(), $import_fields_validate);
                $this->outut_data = $report;
            });
            if (!empty($this->outut_data)) {
                return response()->success($this->outut_data);
            } else {
                $out_msg = array('upload_status' => 'failed', 'message' => 'Fields not Found');
                return response()->success($out_msg);
            }
        }
        return response()->error('File not Found');
    }
    public function postImportClinics(Request $request)
    {
        $import_fields_validate = array('name');
        if ($request->clinics) {
            $file = Input::file('clinics');
            $name = time() . '-' . $file->getClientOriginalName();
            $path = storage_path('hd_providers');
            $file_path = $file->move($path, $name);
            $out = Excel::load($file_path, function ($reader) use ($import_fields_validate) {
                $success_count = 0;
                $Faied_count = 0;
                $contact_exists_count = 0;
                $report = HonestdoctorHelper::createHdClinicFile($reader->toArray(), $import_fields_validate);
                $this->outut_data = $report;
            });
            if (!empty($this->outut_data)) {
                return response()->success($this->outut_data);
            } else {
                $out_msg = array('upload_status' => 'failed', 'message' => 'Fields not Found');
                return response()->success($out_msg);
            }
        }
        return response()->error('File not Found');
    }

    public function importHdFromTemp()
    {
        //$this->importClinicsFromTemp();
        $this->importProvidersFromTemp();
    }

    public function importClinicsFromTemp()
    {
        $import_fields_validate = array('name');
        $import_data = [];
        $Hd_posts = TempHonestDoctorImport::where('imported', 0)->where('type', 'clinic')->take(40)->get();
        if (count($Hd_posts)>0) {
            $posts = $Hd_posts->toArray();
            foreach ($posts as $post) {
                $import_data[] = [
                    'name'=>$post['name'],
                    'specialties'=>$post['specializations'],
                    'address'=>$post['office_address'],
                    'gender'=>$post['gender'],
                    'city'=>$post['office_city'],
                    'phone'=>$post['phone_number'],
                ];
            }
            $report = HonestdoctorHelper::createHdClinicFile($import_data, $import_fields_validate);
        }
    }

    public function importProvidersFromTemp()
    {
        $import_fields_validate = array('name');
        $import_data = [];
        $imported_ids = [];
        $Hd_posts = TempHonestDoctorImport::where('imported', 0)->where('type', 'provider')->take(35)->get();
        if (count($Hd_posts)>0) {
            $posts = $Hd_posts->toArray();
            foreach ($posts as $post) {
                $city = '';
                $state = '';
                if (strpos($post['office_city'], ",") !== false) {
                    $add_array = explode(',', $post['office_city']);
                    $city = $add_array[0];
                    $state = trim($add_array[1]);
                }

                $import_data[] = [
                    'name'=>$post['name'],
                    'specialties'=>$post['specializations'],
                    'address'=>$post['office_address'],
                    'gender'=>$post['gender'],
                    'city'=>$city,
                    'email'=>'',
                    'state'=>$state,
                    'country'=>'',
                    'phone'=>$post['phone_number'],
                ];
                $imported_ids[] = $post['id'];
            }
            $report = HonestdoctorHelper::createHdProviderFile($import_data, $import_fields_validate);
            TempHonestDoctorImport::whereIn('id', $imported_ids)->update(['imported'=>1]);
        }
    }
}
