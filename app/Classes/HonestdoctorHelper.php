<?php

namespace App\Classes;

use App\Specialities;
use App\TermSpecialities;
use Curl;
use Illuminate\Support\Facades\Cache;

class HonestdoctorHelper
{
    public static function attachSpecializations($specializations, $term_type, $term_id)
    {
        $specializaion_hd_ids = [];
        self::clearTermSpecializations($term_type, $term_id);
        $ins_data = [];
        foreach ($specializations as $value) {
            $wp_id = null;
            $wp_slug = null;
            if (isset($value['wp_id'])) {
                $wp_id = (int)$value['wp_id'];
            }
            if (isset($value['wp_slug'])) {
                $wp_slug = $value['wp_slug'];
            }

            $spc_id = self::addSpecialization($value['text'], $wp_id, $wp_slug);
            $ins_data[] = array('type' => $term_type, 'specialization_id' => $spc_id, 'term_id' => $term_id);
            if ($wp_id != null) {
                $specializaion_hd_ids[] = $wp_id;
            } else {
                $specialization_n = self::getSpecializationByName($value['text']);
                $specializaion_hd_ids[] = $specialization_n['wp_id'];
            }
        }
        TermSpecialities::insert($ins_data);
        return $specializaion_hd_ids;
    }

    public static function clearTermSpecializations($term_type, $term_id)
    {
        TermSpecialities::where(['type' => $term_type, 'term_id' => $term_id])->delete();
    }

    public static function addSpecialization($specialization, $wp_id, $wp_slug)
    {
        $all_Specializations = [];
        $spc = Specialities::where(['wp_id' => $wp_id, 'wp_slug' => $wp_slug])
            ->first();
        if (count($spc) > 0) {
            return $spc->id;
        } else {
            if (Cache::has('hd_specialties')) {
                $all_Specializations = self::getAllSpecializations();
            }

            $like = (string)$wp_id;
            $result = array_filter($all_Specializations, function ($item) use ($like) {
                if (stripos($item['id'], $like) !== false) {
                    return true;
                }
                return false;
            });
            if (count($result) > 0) {
                $special_n = array_values($result)[0];
            }

            if (!isset($special_n)) {
                $special_n = self::createHdSpecialization($specialization);
                /* If Specialization alreay exists get from HD */
                if (isset($special_n['code']) && $special_n['code'] == 'term_exists') {
                    $spc_id = $special_n['data'];
                    $special_n = self::getHdSpecialization($spc_id);
                }
                Cache::forget('hd_specialties');
                self::getAllSpecializations();
            }

            $new_spcl = new Specialities;
            $new_spcl->title = $specialization;
            $new_spcl->wp_id = $special_n['id'];
            $new_spcl->wp_slug = $special_n['slug'];
            $new_spcl->save();
            return $new_spcl->id;
        }
    }

    public static function getAllSpecializations()
    {
        $full_url = getenv('HONESTDOCTOR_WEBSITE_URL') . '/wp-json/wp/v2/specialties';
        $auth = self::getHdAuth();
        $out = [];
        if (Cache::has('hd_specialties')) {
            $response = Cache::get('hd_specialties');
        } else {
            $minutes = 12 * 60;
            $response = Curl::to($full_url)
                ->withHeader('Authorization:' . 'Basic ' . $auth)
                ->withData(['per_page' => 100])
                ->asJson(true)
                ->get();
            Cache::add('hd_specialties', $response, $minutes);
        }
        if (count($response) > 0) {
            foreach ($response as $value) {
                if (isset($value['id']) && isset($value['id'])) {
                    $out[] = ['name' => $value['name'], 'id' => $value['id'], 'slug' => $value['slug']];
                }
            }
        }
        return $out;
    }

    public static function getHdAuth()
    {
        $username = getenv('HONESTDOCTOR_API_USERNAME');
        $password = getenv('HONESTDOCTOR_API_PASSWORD');
        $auth = base64_encode($username . ':' . $password);
        return $auth;
    }

    public static function createHdSpecialization($specialization)
    {
        $full_url = getenv('HONESTDOCTOR_WEBSITE_URL') . '/wp-json/wp/v2/specialties';
        $auth = self::getHdAuth();
        $out = [];
        $response = Curl::to($full_url)
            ->withHeader('Authorization:' . 'Basic ' . $auth)
            ->withData(['name' => $specialization])
            ->asJson(true)
            ->post();
        return $response;
    }

    public static function getHdSpecialization($specialization_id)
    {
        //print_r($specialization_id); die;
        $full_url = getenv('HONESTDOCTOR_WEBSITE_URL') . '/wp-json/wp/v2/specialties/' . $specialization_id;
        $auth = self::getHdAuth();
        $out = [];
        $response = Curl::to($full_url)
            ->withHeader('Authorization:' . 'Basic ' . $auth)
            ->asJson(true)
            ->post();
        return $response;
    }

    public static function getSpecializationByName($spcl_name)
    {
        $scpcl_z = Specialities::where('title', 'like', '%' . $spcl_name . '%')->first();
        if (count($scpcl_z) > 0) {
            return $scpcl_z->toArray();
        }
        return false;
    }

    public static function getTermSpecializations($term_type, $term_id)
    {
        $data = TermSpecialities::select(['specialities.title as text', 'specialities.wp_id', 'specialities.wp_slug'])
            ->join('specialities', 'specialities.id', '=', 'term_specialities.specialization_id')
            ->where('term_specialities.term_id', $term_id)
            ->where('term_specialities.type', $term_type)
            ->get();
        if (count($data) > 0) {
            return $data->toArray();
        } else {
            return [];
        }
    }

    public static function updateHdProvider(
        $update_data,
        $Specializations,
        $company_id = null,
        $hd_provider_id,
        $userId = null,
        $media_Id = null
    ) {
        // print_r($update_data['data.facebook_link']); die;
        $update_data_input = $update_data;
        $publish_status = 'draft';
        if ($update_data['data.hd_publish_status'] == 1 || $update_data['data.hd_publish_status'] == 'publish') {
            $publish_status = 'publish';
        }
        if (!isset($update_data['data.website_url'])) {
            $update_data['data.website_url'] = '';
        }

        $update_data = [
            'title' => $update_data['data.name'],
            'author' => 1,
            'content' => $update_data['data.bio'],
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'meta' => [
                ['key' => 'wpcf_gender', 'value' => $update_data['data.gender']],
                ['key' => 'wpcf_gender', 'value' => $update_data['data.gender']],
                ['key' => 'wpcf_email', 'value' => $update_data['data.email']],
                ['key' => 'wpcf_phone_no', 'value' => $update_data['data.phone']],
                ['key' => 'wpcf_website', 'value' => $update_data['data.website_url']],
                ['key' => 'wpcf_province', 'value' => $update_data['data.province']],
                ['key' => 'wpcf_city', 'value' => $update_data['data.city']],
                ['key' => 'wpcf_country', 'value' => $update_data['data.country']],
                ['key' => 'wpcf_address', 'value' => $update_data['data.address']],
                ['key' => 'wpcf_job_title', 'value' => $update_data['data.job_title']],
            ],
            'specialties' => $Specializations,
        ];


        if (isset($update_data_input['data.facebook_link'])) {
            $update_data['meta'][] = [
                'key' => 'wpcf_facebook_link',
                'value' => $update_data_input['data.facebook_link']
            ];
        }
        if (isset($update_data_input['data.additional_info'])) {
            $update_data['meta'][] = [
                'key' => 'wpcf_additional_info',
                'value' => $update_data_input['data.additional_info']
            ];
        }

        if (isset($update_data_input['data.twitter_link'])) {
            $update_data['meta'][] = ['key' => 'wpcf_twitter_link', 'value' => $update_data_input['data.twitter_link']];
        }
        if (isset($update_data_input['data.google_link'])) {
            $update_data['meta'][] = ['key' => 'wpcf_google_link', 'value' => $update_data_input['data.google_link']];
        }
        if (isset($update_data_input['data.youtube_link'])) {
            $update_data['meta'][] = ['key' => 'wpcf_youtube_link', 'value' => $update_data_input['data.youtube_link']];
        }
        if (isset($update_data_input['data.instagram_link'])) {
            $update_data['meta'][] = [
                'key' => 'wpcf_instagram_link',
                'value' => $update_data_input['data.instagram_link']
            ];
        }
        if (isset($update_data_input['data.social_links'])) {
            $update_data['meta'][] = ['key' => 'wpcf_social_links', 'value' => $update_data_input['data.social_links']];
        }
        if (isset($update_data_input['data.claim_status'])) {
            $update_data['meta'][] = ['key' => 'wpcf_claim_status', 'value' => $update_data_input['data.claim_status']];
        }
        if (isset($update_data_input['data.linkedin_link'])) {
            $update_data['meta'][] = [
                'key' => 'wpcf_linkedin_link',
                'value' => $update_data_input['data.linkedin_link']
            ];
        }
        if (isset($update_data_input['data.certifications'])) {
            $update_data['meta'][] = [
                'key' => 'wpcf_certifications',
                'value' => $update_data_input['data.certifications']
            ];
        }
        if (isset($update_data_input['data.education'])) {
            $update_data['meta'][] = ['key' => 'wpcf_education', 'value' => $update_data_input['data.education']];
        } else {
            $update_data['meta'][] = ['key' => 'wpcf_education', 'value' => ''];
        }
        if (isset($update_data_input['data.hospital_affiliations'])) {
            $update_data['meta'][] = [
                'key' => 'wpcf_hospital_affiliations',
                'value' => $update_data_input['data.hospital_affiliations']
            ];
        }
        if (isset($update_data_input['data.clinic_name'])) {
            $update_data['meta'][] = ['key' => 'wpcf_clinic_name', 'value' => $update_data_input['data.clinic_name']];
        }

        /* If Feature Image Exists */
        if ($media_Id != null) {
            $update_data['featured_media'] = $media_Id;
            if ($userId != null) {
                UserHelper::updateUser($userId, ['hd_media_id' => $media_Id]);
            }
        }
        $update_data['status'] = $publish_status;
        self::updateHdPost($update_data, 'providers/' . $hd_provider_id);
        return true;
    }

    public static function updateHdPost($data_send, $url_hit)
    {
        $full_url = getenv('HONESTDOCTOR_WEBSITE_URL') . '/wp-json/wp/v2/' . $url_hit;
        $auth = self::getHdAuth();
        $out = [];
        $response = Curl::to($full_url)
            ->withHeader('Authorization:' . 'Basic ' . $auth)
            ->withData($data_send)
            ->asJson(true)
            ->put();
        return $response;
    }

    public static function updateCompanyHD($company_data, $Specializations, $company_id = null, $media_Id = null)
    {
        $publish_status = 'draft';
        $ins_data = [
            'title' => $company_data['name'],
            'author' => 1,
            'content' => $company_data['description'],
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'meta' => [
                ['key' => 'wpcf_address', 'value' => $company_data['address']],
                ['key' => 'wpcf_province', 'value' => $company_data['state']],
                ['key' => 'wpcf_email', 'value' => $company_data['email']],
                ['key' => 'wpcf_phone_no', 'value' => $company_data['phone']],
                ['key' => 'wpcf_country', 'value' => $company_data['country']],
                ['key' => 'wpcf_city', 'value' => $company_data['city']],
                ['key' => 'wpcf_website', 'value' => $company_data['site_url']],
                //['key'=>'wpcf_ratings','value'=>$company_data['address']],
            ],
            'specialties' => $Specializations,
        ];

        if ($media_Id != null) {
            $ins_data['featured_media'] = $media_Id;
        }

        if (isset($company_data['hd_publish_status']) && ($company_data['hd_publish_status'] == 1 || $company_data['hd_publish_status'] == 'publish')) {
            $publish_status = 'publish';
        }
        if (isset($company_data['hd_publish_status']) && $company_data['hd_publish_status'] != 1 && $company_data['hd_publish_status'] != 'publish') {
            $publish_status = 'draft';
        }
        if (isset($company_data['facebook_link'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_facebook_link', 'value' => $company_data['facebook_link']];
        }
        if (isset($company_data['twitter_link'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_twitter_link', 'value' => $company_data['twitter_link']];
        }
        if (isset($company_data['google_link'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_google_link', 'value' => $company_data['google_link']];
        }
        if (isset($company_data['youtube_link'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_youtube_link', 'value' => $company_data['youtube_link']];
        }
        if (isset($company_data['instagram_link'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_instagram_link', 'value' => $company_data['instagram_link']];
        }
        if (isset($company_data['social_links'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_social_links', 'value' => $company_data['social_links']];
        }
        //if (isset($company_data['claim_status'])) {
        $ins_data['meta'][] = ['key' => 'wpcf_claim_status', 'value' => $company_data['claim_status']];
        //}
        if (isset($company_data['linkedin_link'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_linkedin_link', 'value' => $company_data['linkedin_link']];
        }
        if (isset($company_data['certifications'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_certifications', 'value' => $company_data['certifications']];
        }
        //print_r($ins_data); die;
        $ins_data['status'] = $publish_status;
        if ($company_id != null) {
            $company_info = CompanyHelper::getCompanyDetais($company_id);
            $ins_data['meta'][] = ['key' => 'EGClinicID', 'value' => $company_info['api_key']];
        }
        $Hd_status = self::updateHdPost($ins_data, 'clinics/' . $company_data['hd_post_id']);
        if (isset($Hd_status['id']) && $company_id != null) {
            CompanyHelper::updateCompanyDetailsHDid($Hd_status['id'], $company_id);
        }
        return true;
    }

    public static function updateCompanyMediaHD($media_id = null, $company_id)
    {
        $company_data = CompanyHelper::getCompanyDetais($company_id);
        if ($media_id != null) {
            $ins_data = [];
            $ins_data['featured_media'] = $media_id;
            CompanyHelper::updateCompanyDetails(['hd_media_id' => $media_id], $company_id);
            $Hd_status = self::updateHdPost($ins_data, 'clinics/' . $company_data['hd_post_id']);
        }
        return true;
    }

    public static function postMediaHD($mediaUrl)
    {
        $data_send = ['featured_image_src' => $mediaUrl];
        $full_url = getenv('HONESTDOCTOR_WEBSITE_URL') . '/wp-json/wp/v2/media';
        $auth = self::getHdAuth();
        $out = [];
        $response = Curl::to($full_url)
            ->withHeader('Authorization:' . 'Basic ' . $auth)
            ->asJson(true)
            ->withData($data_send)
            ->put();
        if ($response == '' || isset($response['code'])) {
            return null;
        }
        return $response;
    }

    public static function getPostTypes($post_type, $data_send)
    {
        //$data_send['status'] = 'publish, draft';
        //$data_send = ['status'=>'publish'];
        //$data_send = ['page'=>2];
        //print_r($data_send); die();
        $full_url = getenv('HONESTDOCTOR_WEBSITE_URL') . '/wp-json/wp/v2/' . $post_type;
        $auth = self::getHdAuth();
        $out = [];
        $response = Curl::to($full_url)
            ->withHeader('Authorization:' . 'Basic ' . $auth)
            ->withData($data_send)
            ->asJson(true)
            ->withResponseHeaders()
            ->returnResponseObject()
            ->get();
        //print_r($response); die();
        $out['content'] = $response->content;
        $out['headers'] = $response->headers;
        return $out;
    }

    public static function getSpecializationHDPost($specializations)
    {
        $specializaion_hd_ids = [];
        $ins_data = [];
        if (is_array($specializations)) {
            foreach ($specializations as $key => $value) {
                $wp_id = null;
                $wp_slug = null;
                if (isset($value['wp_id'])) {
                    $wp_id = (int)$value['wp_id'];
                }
                if (isset($value['wp_slug'])) {
                    $wp_slug = $value['wp_slug'];
                }
                if ($wp_id != null) {
                    $specializaion_hd_ids[] = $wp_id;
                } else {
                    $spcl_info = self::createHdSpecialization($value['text']);
                    $ins_data[] = array(
                        'title' => $value['text'],
                        'wp_id' => $spcl_info['id'],
                        'wp_slug' => $spcl_info['slug']
                    );
                    $specializaion_hd_ids[] = $spcl_info['id'];
                }
            }
            if (count($ins_data) > 0) {
                specialities::insert($ins_data);
                Cache::forget('hd_specialties');
                self::getAllSpecializations();
            }
        }
        return $specializaion_hd_ids;
    }

    public static function getHdPost($post_id, $type)
    {
        //$full_url = getenv('HONESTDOCTOR_WEBSITE_URL').'/wp-json/wp/v2/'.$type.'/'.$post_id;
        $full_url = getenv('HONESTDOCTOR_WEBSITE_URL') . '/wp-json/wp/v2/' . $type . '/' . $post_id;

        $auth = self::getHdAuth();
        $out = [];
        $response = Curl::to($full_url)
            ->withHeader('Authorization:' . 'Basic ' . $auth)
            ->asJson(true)
            ->get();
        return $response;
    }

    public static function FetchSpecializations($specialities)
    {
        $spclztion = self::getAllSpecializations();
        $out = [];
        foreach ($spclztion as $spcs) {
            if (in_array($spcs['id'], $specialities)) {
                $out[] = ['text' => $spcs['name'], 'wp_id' => $spcs['id'], 'wp_slug' => $spcs['slug']];
            }
        }
        return $out;
    }

    public static function getHdMediaUrl($media_Id)
    {
        $full_url = getenv('HONESTDOCTOR_WEBSITE_URL') . '/wp-json/wp/v2/media/' . $media_Id;
        $auth = self::getHdAuth();
        $out = [];
        $response = Curl::to($full_url)
            ->asJson(true)
            ->withHeader('Authorization:' . 'Basic ' . $auth)
            ->get();
        return $response['source_url'];
    }

    public static function deletePost($post_type, $post_id)
    {
        $full_url = getenv('HONESTDOCTOR_WEBSITE_URL') . '/wp-json/wp/v2/' . $post_type . '/' . $post_id;
        $auth = self::getHdAuth();
        $out = [];
        Curl::to($full_url)
            ->withHeader('Authorization:' . 'Basic ' . $auth)
            ->delete();
        return true;
    }

    public static function createHdProviderFile($fileData, $RequiredFileds)
    {
        $out = [];
        $inserted_count = 0;
        $failed = 0;
        $Specializations = [];
        if (count($fileData) > 0) {
            foreach ($fileData as $fields) {
                $hdData = [];
                $website_url = '';
                foreach ($fields as $fields_key => $field) {
                    if ($fields_key == 'website_url') {
                        $website_url = $field;
                    }
                    if ($fields_key == 'specialties') {
                        $Specialties = $field;
                        $spc_array = explode(',', $Specialties);
                        $Specializations = self::getSpecializationIdsArray($spc_array);
                        //print_r($Specializations); die;
                    }
                    $hdData[$fields_key] = $field;
                }
                $inserted = self::createProvider($hdData, $Specializations, null, null, $website_url, null);
                if ($inserted) {
                    $inserted_count++;
                } else {
                    $failed++;
                }
            }
        }
        return ['inserted' => $inserted_count, 'failed' => $failed];
    }

    public static function getSpecializationIdsArray($spec_texts)
    {
        $out = [];
        foreach ($spec_texts as $spc) {
            if (!empty($spc)) {
                $out[] = self::getSpecializationIds($spc);
            }
        }
        return $out;
    }

    public static function getSpecializationIds($text_spc)
    {
        $all_Specializations = self::getAllSpecializations();
        $like = $text_spc;
        $specialization = $text_spc;


        $result = array_filter($all_Specializations, function ($item) use ($like) {
            if (stripos($item['name'], $like) !== false) {
                return true;
            }
            return false;
        });
        if (count($result) > 0) {
            $special_n = array_values($result)[0];
            return $special_n['id'];
        }


        if (!isset($special_n)) {
            $special_n = self::createHdSpecialization($specialization);
            /* If Specialization alreay exists get from HD */
            if (isset($special_n['code']) && $special_n['code'] == 'term_exists') {
                $spc_id = $special_n['data']['term_id'];
                $special_n = self::getHdSpecialization($spc_id);
            }
            Cache::forget('hd_specialties');
            self::getAllSpecializations();
        }
        if (isset($special_n['id'])) {
            $new_spcl = new Specialities;
            $new_spcl->title = $specialization;
            $new_spcl->wp_id = $special_n['id'];
            $new_spcl->wp_slug = $special_n['slug'];
            $new_spcl->save();
            return $special_n['id'];
        } else {
            return '';
        }
    }

    public static function createProvider(
        $user_data,
        $Specializations,
        $user_id,
        $company_id,
        $website_url,
        $media_Id = null
    ) {
        $publish_status = 'draft';
        if (!isset($user_data['bio'])) {
            $user_data['bio'] = '';
        }
       
        $ins_data = [
            'title' => $user_data['name'],
            'author' => 1,
            'content' => $user_data['bio'],
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'meta' => [
                ['key' => 'wpcf_gender', 'value' => $user_data['gender']],
                ['key' => 'wpcf_email', 'value' => $user_data['email']],
                ['key' => 'wpcf_phone_no', 'value' => $user_data['phone']],
                ['key' => 'wpcf_province', 'value' => $user_data['state']],
                ['key' => 'wpcf_city', 'value' => $user_data['city']],
                ['key' => 'wpcf_country', 'value' => $user_data['country']],
                ['key' => 'wpcf_website', 'value' => $website_url],
            ],
            'specialties' => $Specializations,
        ];
        if (isset($user_data['hd_publish_status'])) {
           if($user_data['hd_publish_status'] !=0 || $user_data['hd_publish_status'] === "publish") {
            $publish_status = 'publish';
           }
        }
        if (isset($user_data['state'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_province', 'value' => $user_data['state']];
        }
        if (isset($user_data['job_title'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_job_title', 'value' => $user_data['job_title']];
        }
        if (isset($user_data['address'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_address', 'value' => $user_data['address']];
        }
        if (isset($user_data['facebook_link'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_facebook_link', 'value' => $user_data['facebook_link']];
        }
        if (isset($user_data['twitter_link'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_twitter_link', 'value' => $user_data['twitter_link']];
        }
        if (isset($user_data['google_link'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_google_link', 'value' => $user_data['google_link']];
        }
        if (isset($user_data['youtube_link'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_youtube_link', 'value' => $user_data['youtube_link']];
        }
        if (isset($user_data['instagram_link'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_instagram_link', 'value' => $user_data['instagram_link']];
        }
        if (isset($user_data['social_links'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_social_links', 'value' => $user_data['social_links']];
        }

        if (isset($user_data['claim_status'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_claim_status', 'value' => $user_data['claim_status']];
        } else {
            $ins_data['meta'][] = ['key' => 'wpcf_claim_status', 'value' => 'Approved'];
        }

        if (isset($user_data['linkedin_link'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_linkedin_link', 'value' => $user_data['linkedin_link']];
        }
        if (isset($user_data['certifications'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_certifications', 'value' => $user_data['certifications']];
        }
        if (isset($user_data['hospital_affiliations'])) {
            $ins_data['meta'][] = [
                'key' => 'wpcf_hospital_affiliations',
                'value' => $user_data['hospital_affiliations']
            ];
        }
        if (isset($user_data['education'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_education', 'value' => $user_data['education']];
        }
        if (isset($user_data['additional_info'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_additional_info', 'value' => $user_data['additional_info']];
        }
        if (isset($user_data['clinic_name'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_clinic_name', 'value' => $user_data['clinic_name']];
        }
       
        $ins_data['status'] = $publish_status;
        /* If Feature Image Exists */
        if ($media_Id != null) {
            $ins_data['featured_media'] = $media_Id;
            if ($user_id != null) {
                UserHelper::updateUser($user_id, ['hd_media_id' => $media_Id]);
            }
        }

        if ($user_id != null) {
            $ins_data['meta'][] = ['key' => 'EGProviderID', 'value' => $user_id];
            $user_info = UserHelper::getUserDetails($user_id);
            $company_id = $user_info['company_id'];
        }

        if ($company_id != null) {
            $company_info = CompanyHelper::getCompanyDetais($company_id);
            $ins_data['meta'][] = ['key' => 'EGClinicID', 'value' => $company_info['api_key']];
        }
        $Hd_status = self::createHdPost($ins_data, 'providers/');
        if (isset($Hd_status['id']) && $user_id != null) {
            UserHelper::updateUser($user_id, ['hd_provider_id' => $Hd_status['id'], 'hd_publish_status' => 1]);
        }
        return true;
    }

    public static function createHdPost($data_send, $url_hit)
    {
        $full_url = getenv('HONESTDOCTOR_WEBSITE_URL') . '/wp-json/wp/v2/' . $url_hit;
        $auth = self::getHdAuth();
        $out = [];
        $response = Curl::to($full_url)
            ->withHeader('Authorization:' . 'Basic ' . $auth)
            ->withData($data_send)
            ->asJson(true)
            ->post();
        return $response;
    }

    public static function createHdClinicFile($fileData, $RequiredFileds)
    {
        $out = [];
        $inserted_count = 0;
        $failed = 0;
        $Specializations = [];
        if (count($fileData) > 0) {
            foreach ($fileData as $fields) {
                $hdData = [];
                $website_url = '';

                foreach ($fields as $fields_key => $field) {
                    if ($fields_key == 'website_url') {
                        $website_url = $field;
                    }
                    if ($fields_key == 'specialties') {
                        $Specialties = $field;
                        $spc_array = explode(',', $Specialties);
                        print_r($spc_array); die;
                        $Specializations = self::getSpecializationIdsArray($spc_array);
                    }
                    $hdData[$fields_key] = $field;
                }
                $fields['site_url'] = $website_url;
                $inserted = self::addCompanyHD($fields, $Specializations, null, null);
                if ($inserted) {
                    $inserted_count++;
                } else {
                    $failed++;
                }
            }
        }
        return ['inserted' => $inserted_count, 'failed' => $failed];
    }

    public static function addCompanyHD($company_data, $Specializations, $company_id = null, $media_Id = null)
    {
        if (!isset($company_data['description'])) {
            $company_data['description'] = '';
        }
        $publish_status = 'draft';
        if ($company_id != null) {
            $company_info = CompanyHelper::getCompanyDetais($company_id);
        }
        $phone_num = '';
        $twilio_number = \App\Classes\CompanySettingsHelper::getSetting($company_id, 'twilio_number');
        if ($twilio_number == false && empty($twilio_number) == false) {
            if (isset($company_data['phone'])) {
                $phone_num = $company_data['phone'];
            }
        } else {
            $phone_num = $twilio_number;
        }

        $ins_data = [
            'title' => $company_data['name'],
            'author' => 1,
            'content' => $company_data['description'],
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'meta' => [
                ['key' => 'wpcf_address', 'value' => $company_data['address']],
                ['key' => 'wpcf_province', 'value' => $company_data['state']],
                ['key' => 'wpcf_country', 'value' => $company_data['country']],
                ['key' => 'wpcf_city', 'value' => $company_data['city']],
                ['key' => 'wpcf_email', 'value' => $company_data['email']],
                ['key' => 'wpcf_phone_no', 'value' => $phone_num],
                ['key' => 'wpcf_website', 'value' => $company_data['site_url']],

            ],
            'specialties' => $Specializations,
        ];
        if (isset($company_data['hd_publish_status']) && ($company_data['hd_publish_status'] == 1 || $company_data['hd_publish_status'] == 'publish')) {
            $publish_status = 'publish';
        }
        /* If Feature Image Exists */
        if ($media_Id != null) {
            $ins_data['featured_media'] = $media_Id;
            CompanyHelper::updateCompanyDetails(['hd_media_id' => $media_Id], $company_id);
        }
        if ($company_id != null) {
            $ins_data['meta'][] = ['key' => 'EGClinicID', 'value' => $company_info['api_key']];
        }

        if (isset($company_data['facebook_link'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_facebook_link', 'value' => $company_data['facebook_link']];
        }
        if (isset($company_data['twitter_link'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_twitter_link', 'value' => $company_data['twitter_link']];
        }
        if (isset($company_data['google_link'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_google_link', 'value' => $company_data['google_link']];
        }
        if (isset($company_data['youtube_link'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_youtube_link', 'value' => $company_data['youtube_link']];
        }
        if (isset($company_data['instagram_link'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_instagram_link', 'value' => $company_data['instagram_link']];
        }
        if (isset($company_data['social_links'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_social_links', 'value' => $company_data['social_links']];
        }
        if (isset($company_data['claim_status'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_claim_status', 'value' => $company_data['claim_status']];
        } else {
            $ins_data['meta'][] = ['key' => 'wpcf_claim_status', 'value' => 'Approved'];
        }
        if (isset($company_data['linkedin_link'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_linkedin_link', 'value' => $company_data['linkedin_link']];
        }
        if (isset($company_data['certifications'])) {
            $ins_data['meta'][] = ['key' => 'wpcf_certifications', 'value' => $company_data['certifications']];
        }
        $ins_data['status'] = $publish_status;

        $Hd_status = self::createHdPost($ins_data, 'clinics/');
        if (isset($Hd_status['id'])) {
            CompanyHelper::updateCompanyDetailsHDid((int)$Hd_status['id'], $company_id, 1);
            return true;
        }
        return true;
    }
}
