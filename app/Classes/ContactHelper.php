<?php
namespace App\Classes;

use App\Contact;
use App\ContactComment;
use App\ContactTag;
use App\EgTerm;
use App\Review;
use Auth;
use DB;

class ContactHelper
{
    public static function getContactName($contact_id, $company_id = null)
    {
        $contact = Contact::where('id', $contact_id);
        if ($company_id != null) {
            $contact->where('company_id', $company_id);
        }
        $contact = $contact->first();
        if (count($contact) > 0) {
            return $contact;
        } else {
            return false;
        }
    }

    public static function getContactInfo($contact_id)
    {
        $contact = Contact::find($contact_id);
        if (count($contact) > 0) {
            return $contact;
        } else {
            return false;
        }
    }

    public static function findContactByName($search_name, $company_id = '')
    {
        $where = array();
        if ($company_id != '') {
            $w = array('company_id', '=', $company_id);
            array_push($where, $w);
        }
        $contacts = Contact::Where(
            function ($qu) use ($search_name) {
                $qu->where(DB::raw('CONCAT(first_name," ",last_name)'), 'like', '%' . $search_name . '%');
            }
        )
            ->where($where)
            ->get();
        if ($contacts->count() > 0) {
            $contacts->toArray();
            return $contacts;
        }
        return false;
    }

    public static function findContactByemail($email, $company_id = '')
    {
        $where = array();
        if ($company_id != '') {
            $w = array('company_id', '=', $company_id);
            array_push($where, $w);
        }
        $contacts = Contact::Where('email', '=', $email)
            ->where('company_id', $company_id)
            ->first()->toArray();
        return $contacts;
    }

    public static function addCustonRefferByContact($data, $company_id)
    {
        $full_name = $data['first_name'];
        $name = trim($full_name);
        $last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
        $first_name = trim(preg_replace('#' . $last_name . '#', '', $name));

        /* Save New Contact */
        $contact = new Contact;
        $contact->first_name = $first_name;
        $contact->last_name = $last_name;
        $contact->company_id = $company_id;
        $contact->save();
        return $contact->id;
    }

    public static function isContactExists($company_id, $email = '', $mobile = '')
    {
        $where = array();
        if ($email != '') {
            $w_e = array('email', '=', $email);
            array_push($where, $w_e);
        }
        if ($mobile != '') {
            $w_m = array('mobile_number', '=', $mobile);
            array_push($where, $w_m);
        }
        $conatct_exists = Contact::where($where)->where('company_id', $company_id)->count();
        if ($conatct_exists > 0) {
            return true;
        } else {
            return false;
        }
    }

    public static function isContactExistsByPhone($companyId, $phone = null)
    {
        $conatct_exists = [];
        $default_phone_country_code = default_phone_country_code();
        $phone_number = null;

        if ($phone != null) {
            $input_phone_number = $phone;
            $phone_number = $default_phone_country_code . format_phone_number($phone);

            /* If contact exists then return with contact Id */
            $conatct_exists = Contact::select('id')
                ->where('company_id', $companyId)
                ->where('mobile_number', '=', $phone_number)
                ->first();
        }

        if (count($conatct_exists) > 0) {
            return $conatct_exists->id;
        } else {
            return false;
        }
    }

    /**
     * @param $companyId
     * @param $email
     * @return bool|mixed
     */
    public static function getContactIdByEmail($companyId, $email)
    {
        $conatct = Contact::select('id')
            ->where('company_id', $companyId)
            ->where('email', '=', $email)
            ->first();
        if (count($conatct)>0) {
            return $conatct->id;
        }
        return false;
    }

    public static function addReviewConact($f_name, $l_name, $email, $company_id)
    {
        $contact = new Contact;
        $contact->first_name = $f_name;
        $contact->last_name = $l_name;
        $contact->email = $email;
        $contact->company_id = $company_id;
        $contact->save();
        return $contact->id;
    }

    /* Save User Reviews */
    /*public static function addContactReview($contact_id=null, $provider_id=null,$f_name=null,$l_name='',$email=null, $company_id, $rating, $review_u, $url=null,$type_review=null,$publisher_id=null, $yext_review_id=null,$publisherDate=null,$video_url=null, $audio_url=null, $img_url=null){

    $review = new Review;
    $review->company_id = $company_id;
    $review->provider_id = $provider_id;
    $review->yext_review_id = $yext_review_id;
    $review->first_name = $f_name;
    $review->last_name = $l_name;
    $review->email = $email;
    $review->type_review = $type_review;
    $review->url = $url;
    $review->order_review = 1;
    $review->publisher_id = $publisher_id;
    $review->published_time = $publisherDate;
    $review->video_url = $video_url;
    $review->audio_url = $audio_url;
    $review->img_url = $img_url;
    $review->contact_id = $contact_id;
    $review->rating = $rating;
    $review->user_review = $review_u;
    $review->save();
    return $review->id;
    }*/
    public static function addContactReview($contact_id = null, $provider_id = null, $f_name = null, $l_name = '', $email = null, $company_id, $rating_quality, $rating_value, $rating_timeliness, $rating_experience, $rating_satisfaction, $rating, $review_u, $url = null, $type_review = null, $publisher_id = null, $yext_review_id = null, $publisherDate = null, $video_url = null, $audio_url = null, $img_url = null, $phone = null)
    {
        $review = new Review;
        $review->company_id = $company_id;
        $review->provider_id = $provider_id;
        $review->yext_review_id = $yext_review_id;
        $review->first_name = $f_name;
        $review->last_name = $l_name;
        $review->email = $email;
        $review->type_review = $type_review;
        $review->url = $url;
        $review->order_review = 1;
        $review->publisher_id = $publisher_id;
        $review->published_time = $publisherDate;
        $review->video_url = $video_url;
        $review->audio_url = $audio_url;
        $review->img_url = $img_url;
        $review->contact_id = $contact_id;
        $review->rating = $rating;
        $review->rating_quality = $rating_quality;
        $review->rating_value = $rating_value;
        $review->rating_timeliness = $rating_timeliness;
        $review->rating_experience = $rating_experience;
        $review->rating_satisfaction = $rating_satisfaction;
        $review->phone = $phone;
        $review->user_review = $review_u;
        $review->save();
        return $review->id;
    }
    public static function getTowerDataFromEmail($email)
    {
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => "https://api.towerdata.com/v5/td?api_key=3d7d2219080e0333712aea042853400d&email=" . urlencode($email) . "&fields=" . urlencode("age,gender,city,state,zip,household income,net worth,marital_status,home_owner_status,length_of_residence,occupation,business,health_and_wellness,expecting_parent,home_buyer,deal_seeker,luxury_shopper,big_pender,online_buyer"),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
            )
        );

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return false;
        } else {
            return $response;
        }
    }

    public static function findTermByName($search_name, $company_id = '', $term_type = '')
    {
        $where = array();
        if ($company_id != '') {
            $w = array('company_id', '=', $company_id);
            array_push($where, $w);
        }
        $term = EgTerm::where('term_value', 'like', '%' . $search_name . '%')
            ->where($where)
            ->where('term_type', $term_type)
            ->get()->toArray();
        return $term;
    }

    public static function addCustonTerm($term_title, $term_type, $company_id)
    {
        if ($term_title == '') {
            return null;
        }
        $exists = ContactHelper::isCustonTermExists($term_title, $term_type, $company_id);
        if (!$exists) {
            $term = new EgTerm;
            $term->term_type = $term_type;
            $term->term_value = $term_title;
            $term->company_id = $company_id;
            //    $term->created_at = new dateTime();
            $term->save();
            return $term->id;
        }
        return $exists;
    }

    public static function updateContactTags($tags, $contact_id, $company_id, $import = null, $list = false)
    {
        ContactHelper::deleteConatcTags($contact_id);
        $ins = [];
        foreach ($tags as $tag) {
            if ($list == true && $import == true) {
                $term_title = $tag;
            } elseif ($import == null && $import != true && $list == false) {
                $term_title = $tag['text'];
            } elseif ($import != null && $import == true && $list == false) {
                $term_title = $tag;
            }

            $tag_id = ContactHelper::addCustonTerm($term_title, 'tag', $company_id);
            $ins[] = array(
                'tag_id' => $tag_id,
                'contact_id' => $contact_id,
            );
        }

        if (count($ins) > 0) {
            ContactTag::insert($ins);
        }
        return true;
    }

    public static function deleteConatcTags($contact_id)
    {
        ContactTag::where('contact_id', $contact_id)->delete();
        return true;
    }

    public static function getContactTags($contact_id)
    {
        $out = [];
        $contactTag = ContactTag::with('tag')->where('contact_id', $contact_id)->get();
        if (count($contactTag) > 0) {
            $contactTag = $contactTag->toArray();
            foreach ($contactTag as $tag) {
                if ($tag['tag']['term_value'] != null) {
                    $out[] = array('text' => $tag['tag']['term_value']);
                }
            }
        }
        return $out;
    }

    public static function isCustonTermExists($search_name, $term_type, $company_id)
    {
        $where = array();
        if ($company_id != '') {
            $w = array('company_id', '=', $company_id);
            array_push($where, $w);
        }
        $term = EgTerm::where('term_value', 'like', '%' . $search_name . '%')
            ->where($where)
            ->where('term_type', $term_type)
            ->first();
        if (count($term) > 0) {
            return $term->id;
        }
        return false;
    }

    public static function updateContactModal($input)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $app_id = '';
        //$tag = null;
        $source = null;

        if (isset($input['id']) && $input['id'] != '') {
            $app_id = $input['id'];
        } else {
            $con = Contact::select('id')->where('email', $input['email'])->first();
            $app_id = $con->id;
        }
        if (isset($input['id'])) {
            unset($input['id']);
        }
        if (isset($input['phone'])) {
            $input['mobile_number'] = default_phone_country_code() . format_phone_number($input['phone']);
            unset($input['phone']);
        } else {
            $input['mobile_number'] = default_phone_country_code() . format_phone_number($input['mobile_number']);
        }
        /* Tag and Source */
        if (isset($input['source'])) {
            $source = ContactHelper::addCustonTerm($input['source'], 'source', $company_id);
            unset($input['source']);
        }
        if (isset($input['tags'])) {
            ContactHelper::updateContactTags($input['tags'], intval($app_id), $company_id);
        }
        if (isset($input['tag'])) {
            unset($input['tag']);
        }

        unset($input['tags']);
        unset($input['area']);
        Contact::where('id', $app_id)->update($input);
        return $app_id;
    }

    public static function updateContact($contact_id, $update_data)
    {
        Contact::find($contact_id)->update($update_data);
        return true;
    }

    public static function createContact($company_id, $first_name, $last_name, $phone, $email)
    {
        $default_phone_country_code = default_phone_country_code();
        $contact = new Contact;
        $contact->first_name = $first_name;
        $contact->last_name = $last_name;
        $contact->company_id = $company_id;
        $contact->mobile_number = $phone;
        $contact->phone_country_code = $default_phone_country_code;
        $contact->email = $email;
        $contact->save();
        return $contact->id;
    }

    public static function storeContact($company_id, $first_name = null, $last_name = null, $email = null, $phone = null, $gender = null, $address = null, $city = null, $state = null, $country = null, $zipcode = null, $source_id = null, $dnd = null, $country_code = '+1', $existing = 0)
    {
        $default_phone_country_code = default_phone_country_code();
        $phone_number = null;
        $conatct_exists = [];
        if ($phone != null) {
            $input_phone_number = $phone;
            $phone_number = $default_phone_country_code . format_phone_number($phone);

            /* If contact exists then return with contact Id */
            $conatct_exists = Contact::select('id')
                ->where('company_id', $company_id)
                ->where('mobile_number', '=', $phone_number)
                ->first();
        }

        if (count($conatct_exists) > 0) {
            return $conatct_exists->id;
        }

        /* Add New Contact */
        $contact = new Contact;
        $contact->first_name = $first_name;
        $contact->last_name = $last_name;
        $contact->company_id = $company_id;
        $contact->email = $email;
        $contact->mobile_number = $phone_number;
        $contact->gender = $gender;
        $contact->address = $address;
        $contact->city = $city;
        $contact->state = $state;
        $contact->zip_code = $zipcode;
        $contact->source_id = $source_id;
        $contact->is_existing = $existing;
        $contact->dnd = $dnd;
        $contact->phone_country_code = $country_code;
        $user_tower_data = ContactHelper::getTowerDataFromEmail($email);
        if ($user_tower_data) {
            $contact->additional_information = $user_tower_data;
        }
        $contact->save();
        /*Subscribe to news letter*/
        \App\Classes\EmailMarketingHelper::addContactNewsletter($contact->id, $company_id);
        return $contact->id;
    }

    public static function getTermsByType($company_id, $type)
    {
        $terms_get = EgTerm::select('id', 'term_value as value')
            ->where(['company_id' => $company_id, 'term_type' => $type])
            ->get();
        if (count($terms_get) > 0) {
            return $terms_get->toArray();
        }
        return [];
    }

    public static function getTermsById($term_id, $company_id, $type)
    {
        $terms_get = EgTerm::select('term_value as value')
            ->where(['company_id' => $company_id, 'term_type' => $type, 'id' => $term_id])
            ->first();
        if (count($terms_get) > 0) {
            return $terms_get->value;
        }
        return false;
    }

    public static function updateConatctSource($company_id, $contact_id, $source_id)
    {
        $update_data = ['source_id' => $source_id];
        Contact::where('id', $contact_id)
            ->where('company_id', $company_id)
            ->update($update_data);
        return true;
    }

    public static function attachContactNote($contact_id, $note, $user_id = null)
    {
        if (Auth::user()) {
            $user = Auth::user();
            $user_id = $user->id;
        }

        $comment = new ContactComment;
        $comment->contact_id = $contact_id;
        $comment->created_by = $user_id;
        $comment->comment = $note;
        $status = $comment->save();
        return $comment->id;
    }

    public static function updateContactById($contactId, $updateObject)
    {
        $contact = Contact::find($contactId);
        if (isset($updateObject['name']) && empty($updateObject['name'])==false) {
            $contact->first_name = $updateObject['name'];
            if (!empty($contact->last_name)) {
                $contact->first_name = str_replace($contact->last_name, "", $updateObject['name']);
            }
        }
        if (isset($updateObject['first_name']) && empty($updateObject['first_name'])==false) {
            $contact->first_name = $updateObject['first_name'];
            if (!empty($contact->last_name)) {
                $contact->first_name = str_replace($contact->last_name, "", $updateObject['first_name']);
            }
        }
        if (isset($updateObject['last_name']) && empty($updateObject['last_name'])==false) {
            $contact->last_name = $updateObject['last_name'];
        }
        if (isset($updateObject['email']) && empty($updateObject['email'])==false) {
            $contact->email = $updateObject['email'];
            $user_tower_data = ContactHelper::getTowerDataFromEmail($updateObject['email']);
            if ($user_tower_data) {
                $contact->additional_information = $user_tower_data;
            }
        }
        if (isset($updateObject['mobile_number']) && empty($updateObject['mobile_number'])==false) {
            $contact->mobile_number = $updateObject['mobile_number'];
        }
        if (isset($updateObject['mobile_number']) && empty($updateObject['mobile_number'])==false) {
            $contact->mobile_number = $updateObject['mobile_number'];
        }
        if (isset($updateObject['gender']) && empty($updateObject['gender'])==false) {
            $contact->gender = $updateObject['gender'];
        }
        if (isset($updateObject['address']) && empty($updateObject['address'])==false) {
            $contact->address = $updateObject['address'];
        }
        if (isset($updateObject['city']) && empty($updateObject['city'])==false) {
            $contact->city = $updateObject['city'];
        }
        if (isset($updateObject['state']) && empty($updateObject['state'])==false) {
            $contact->state = $updateObject['state'];
        }
        if (isset($updateObject['zip_code']) && empty($updateObject['zip_code'])==false) {
            $contact->zip_code = $updateObject['zip_code'];
        }
        if (isset($updateObject['zip']) && empty($updateObject['zip'])==false) {
            $contact->zip_code = $updateObject['zip'];
        }
        if (isset($updateObject['phone_country_code']) && empty($updateObject['phone_country_code'])==false) {
            $contact->phone_country_code = $updateObject['phone_country_code'];
        }
        $contact->save();
        return true;
    }
}
