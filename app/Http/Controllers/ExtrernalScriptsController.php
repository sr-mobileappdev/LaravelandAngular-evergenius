<?php

namespace App\Http\Controllers;

use App\Classes\CompanyHelper;
use App\Classes\CompanySettingsHelper;
use App\Classes\ContactHelper;
use App\Classes\NotificationHelper;
use App\Classes\ReviewHelper;
use App\Classes\yextApiHelper;
use File;
use Illuminate\Http\Request;
use Input;
use Validator;

class ExtrernalScriptsController extends Controller
{
    public function webFormRequest()
    {
        $iframe_height = "760px";
        $input = Input::get();
        $is_company_exists = CompanyHelper::isApiExists($input['api_key']);
        if ($is_company_exists == false) {
            return '';
        }
        $link_txt = '';
        $i = 0;
        foreach ($input as $key => $value) {
            $link_txt .= $key . "=" . $value;
            if ($i != count($input) - 1) {
                $link_txt .= "&";
            }
            $i++;
        }
        $iframe_link = url('/') . '/scripts/widgets/form-preview/?' . $link_txt;
        return view('extrenal_scripts.web_form', ['iframe_link' => $iframe_link, 'iframe_height' => $iframe_height]);
    }

    public function webReviewPreviewRequest()
    {
        $input = Input::get();
        $link_txt = '';
        $iframe_height = "450px";
        $i = 0;
        foreach ($input as $key => $value) {
            $link_txt .= $key . "=" . $value;
            if ($i != count($input) - 1) {
                $link_txt .= "&";
            }
            $i++;
        }
        if (isset($input['iframe_height'])) {
            $iframe_height = $input['iframe_height'];
        }

        $is_company_exists = CompanyHelper::isApiExists($input['api_key']);
        if ($is_company_exists == false) {
            return '';
        }

        $iframe_link = url('/') . '/scripts/widgets/review-preview/?' . $link_txt;
        $eg_link = url('/');
        if ($input['widgetStyles'] == 'review_dispaly_3') {
            return view('extrenal_scripts.badge_widget', ['iframe_link' => $iframe_link, 'iframe_height' => $iframe_height, 'widget_type' => $input['widgetStyles'], 'eg_link' => $eg_link]);
        }
        return view('extrenal_scripts.web_form', ['iframe_link' => $iframe_link, 'iframe_height' => $iframe_height, 'widget_type' => $input['widgetStyles']]);
    }

    public function webFormPreview()
    {
        $input = Input::get();
        $website_link = url('/');
        $company_datails = [];
        $form_style = $input;
        $itemarray = array();
        $review_settings = [];
        $review_questions = [];
        if (isset($input['api_key'])) {
            $company_datails = CompanyHelper::getCompanyDetaisByApi($input['api_key']);
            $review_settings = ReviewHelper::getCompanyReviewSettings($company_datails['id']);
            $review_questions = ReviewHelper::getReviewQuestions($company_datails['id']);
        }

        if (!empty($company_datails['logo']) && $company_datails['logo']!=null) {
            $company_datails['logo'] = url($company_datails['logo']);
        }
        
        if (isset($input['amp;provider_id']) || isset($input['provider_id'])) {
            if(isset($input['amp;provider_id'])){
                $provider_id = $input['amp;provider_id'];
            } else {
                $provider_id = $input['provider_id'];
            }

            $provider_details = \App\Classes\UserHelper::getUserDetails($provider_id);
            if(!empty($provider_details['avatar']) && $provider_details['avatar']!=null){
              $company_datails['logo'] =  $provider_details['avatar'];
            }
            $company_datails['name'] =  $provider_details['name'];
        }
       
        $view_data = ['website_link' => $website_link,
            'company_details' => $company_datails,
            'form_style' => $input,
            'review_settings' => $review_settings, 'review_questions' => $review_questions,
        ];
        return view('extrenal_scripts.review_form', $view_data);
    }

    public function storeWidgetReviews()
    {
        $input = Input::get();
        $video_url = null;
        $audio_url = null;
        $img_url = null;
        $review = null;
        $rating = 0;
        $rating_quality = 0;
        $rating_value = 0;
        $rating_timeliness = 0;
        $rating_experience = 0;
        $rating_satisfaction = 0;
        $phone = "";
        $provider_id = null;
        if (isset($input['site_key']) && isset($input['email']) && isset($input['first_name'])) {
            $api_key = $input['site_key'];

            $company_id = CompanyHelper::getCompanyIdByApi($api_key);

            if ($company_id == false) {
                return response()->error('Review not added something went wrong!!');
            }

            $is_contact_exists = ContactHelper::isContactExists($company_id, $input['email']);

            if ($is_contact_exists) {
                $get_contact_info = ContactHelper::findContactByemail($input['email'], $company_id);
                $Contact_id = $get_contact_info['id'];
            } else {
                $Contact_id = null;
            }
            if (isset($input['video_url'])) {
                $video_url = $input['video_url'];
            }

            if (isset($input['audio_url'])) {
                $audio_url = $input['audio_url'];
            }

            if (isset($input['provider_id'])) {
                $provider_id = $input['provider_id'];
            }
            
            if (isset($input['img_url'])) {
                $img_url = $input['img_url'];
            }

            if (isset($input['review'])) {
                $review = $input['review'];
            }
            if (isset($input['phone'])) {
                $phone = $input['phone'];
            }
            if (isset($input['r_quality'])) {
                $rating_quality = $input['r_quality'];
            }
            if (isset($input['r_value'])) {
                $rating_value = $input['r_value'];
            }
            if (isset($input['r_timeliness'])) {
                $rating_timeliness = $input['r_timeliness'];
            }
            if (isset($input['r_experience'])) {
                $rating_experience = $input['r_experience'];
            }
            if (isset($input['r_satisfaction'])) {
                $rating_satisfaction = $input['r_satisfaction'];
            }
            if (isset($input['rating'])) {
                $rating = $input['rating'];
            } else {
                $rating = (($rating_quality + $rating_value + $rating_timeliness + $rating_experience + $rating_satisfaction) / 5);
            }

            $type_review = 'website';
            $published_time = new \DateTime();
            ReviewHelper::IncrementReviewOrder($company_id);
            $review_id = ContactHelper::addContactReview($Contact_id, $provider_id, $input['first_name'], $input['last_name'], $input['email'], $company_id, $rating_quality, $rating_value, $rating_timeliness, $rating_experience, $rating_satisfaction, $rating, $review, null, $type_review, 'WEBSITE', null, $published_time, $video_url, $audio_url, $img_url, $phone);

            if ($review_id) {
                $this->sendEmailAdm($company_id, $input, $rating, $review_id); /*SEND EMAIL TO ADMIN*/
                $this->sendSmsAdm($company_id, $input, $rating, $review_id); /*SEND SMS TO ADMIN*/
                return response()->success('Review added successfully.');
            }
            return response()->error('Review not added something went wrong!!');
        }
        return response()->error('Review not added something went wrong dd!!');
    }

    public function reviewPreviewIframe()
    {
        $input = Input::get();
        $website_link = url('/');
        $company_datails = [];
        $form_style = $input;
        $review_settings = [];
        $number_of_reviews = 5;

        foreach ($input as $key => $value) {
            if (strpos($key, 'amp;') !== false) {
                $key_c = str_replace('amp;', '', $key);
                $input[$key_c] = $value;
            }
        }

        if (isset($input['api_key']) && isset($input['widgetStyles'])) {
            $company_datails = CompanyHelper::getCompanyDetaisByApi($input['api_key']);
            $company_id = $company_datails['id'];

            $review_settings = ReviewHelper::getCompanyReviewSettings($company_id);
            $rating_stars = ReviewHelper::getCompanyRatingByStars($company_id);
            $avg_rating = yextApiHelper::get_avg_ratings($company_id);

            $rate_4 = [];
            /* Reviews rate 4 or more than 4 */
            foreach ($rating_stars as $key => $rt) {
                if ($rt['rating'] >= 4) {
                    $rate_4[] = $rt['total_reviews'];
                }
            }
            $rating_more_4 = array_sum($rate_4);

            if (isset($input['reviewsPerPage'])) {
                $number_of_reviews = $input['reviewsPerPage'];
            }
            $company_reviews = ReviewHelper::getCompanyReviews($company_id, $number_of_reviews);
            $total_reviews = ReviewHelper::getTotalCompanyReviews($company_id, $number_of_reviews);
            $display_style = $input['widgetStyles'];
            $rating_scale = getRatingScale($avg_rating);
            // print_r($input);

            /** Widget 3 Calculations **/

            $avg_rating_quality = ReviewHelper::getAverageRatingByType($company_id, 'rating_quality');
            $avg_rating_value = ReviewHelper::getAverageRatingByType($company_id, 'rating_value');
            $avg_rating_timeliness = ReviewHelper::getAverageRatingByType($company_id, 'rating_timeliness');
            $avg_rating_experience = ReviewHelper::getAverageRatingByType($company_id, 'rating_experience');
            $avg_rating_satisfaction = ReviewHelper::getAverageRatingByType($company_id, 'rating_satisfaction');
            $review_questions = ReviewHelper::getReviewQuestions($company_id);
            /* echo '<pre>';
            print_r($company_reviews); die();*/
            /** Widget 3 Calculations **/
            $view_data = ['website_link' => $website_link,
                'company_details' => $company_datails,
                'form_style' => $input,
                'review_settings' => $review_settings,
                'review_listing' => $company_reviews,
                'review_questions' => $review_questions,
                'avg_rating' => $avg_rating,
                'rating_stars' => $rating_stars,
                'rating_scale' => $rating_scale,
                'total_reviews' => $total_reviews,
                'rating_more_4' => $rating_more_4,
                'widget_3' => [
                    'avg_rating_quality' => round($avg_rating_quality, 2),
                    'avg_rating_value' => round($avg_rating_value, 2),
                    'avg_rating_timeliness' => round($avg_rating_timeliness, 2),
                    'avg_rating_experience' => round($avg_rating_experience, 2),
                    'avg_rating_satisfaction' => round($avg_rating_satisfaction, 2),
                    'css_rating_quality' => round((($avg_rating_quality * 100) / 5), 2),
                    'css_rating_value' => round((($avg_rating_value * 100) / 5), 2),
                    'css_rating_timeliness' => round((($avg_rating_timeliness * 100) / 5), 2),
                    'css_rating_experience' => round((($avg_rating_experience * 100) / 5), 2),
                    'css_rating_satisfaction' => round((($avg_rating_satisfaction * 100) / 5), 2),
                ],
            ];

            return view('extrenal_scripts.Widgets.reviews.' . $display_style, $view_data);
        }

        echo "something went wrong!!!!!";
    }

    public function postUploadImage(Request $request)
    {
        $destinationPath = public_path('/upload/others/images');
        $image = $request->file('upload_image');
        $actual_path = "/upload/others/images";

        /* If company Api exists move image to folder */
        if (Input::get('site_key')) {
            $site_key = Input::get('site_key');
            $company_id = CompanyHelper::getCompanyIdByApi($site_key);
            $destinationPath = public_path('/upload/' . $company_id . '/images');
            $actual_path = '/upload/' . $company_id . '/images/';
        }
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, $mode = 0777, true, true);
        }

        if ($image) {
            $validator = Validator::make($request->all(), ['upload_image' => 'required|mimes:jpeg,png,jpg,gif,svg|max:5000']);
            if ($validator->fails()) {
                $messages = $validator->messages();
                return response()->success(['error' => $messages]);
            }
            $input['upload_image'] = time() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $input['upload_image']);
            $image_path = $actual_path . $input['upload_image'];
            $path = url('/') . $image_path;
            $out = array(
                'path' => $path,
            );
            return response()->success($out);
        }
        return response()->error('file not found');
    }

    public function postUploadVideo(Request $request)
    {
        $destinationPath = public_path('/upload/others/videos');
        $actual_path = "/upload/others/videos";

        /* If company Api exists move image to folder */
        if (Input::get('site_key')) {
            $site_key = Input::get('site_key');
            $company_id = CompanyHelper::getCompanyIdByApi($site_key);
            $destinationPath = public_path('/upload/' . $company_id . '/videos');
            $actual_path = '/upload/' . $company_id . '/videos/';
        }
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, $mode = 0777, true, true);
        }

        $video = $request->file('upload_video');
        if ($video) {
            $validator = Validator::make($request->all(), ['upload_video' => 'mimes:mp4,wmv,webm,ogx,oga,ogv,ogg|max:50000']);
            if ($validator->fails()) {
                $messages = $validator->messages();
                return response()->success(['error' => $messages]);
            }
            $input['upload_video'] = time() . '.' . $video->getClientOriginalExtension();
            $video->move($destinationPath, $input['upload_video']);
            $video_path = $actual_path . $input['upload_video'];
            $path = url('/') . $video_path;
            $out = array(
                'path' => $path,
            );
            return response()->success($out);
        }
        return response()->error('file not found');
    }

    public function postUploadAudio(Request $request)
    {
        $destinationPath = public_path('/upload/others/audios');
        $actual_path = "/upload/others/audios";

        /* If company Api exists move image to folder */
        if (Input::get('site_key')) {
            $site_key = Input::get('site_key');
            $company_id = CompanyHelper::getCompanyIdByApi($site_key);
            $destinationPath = public_path('/upload/' . $company_id . '/audios');
            $actual_path = '/upload/' . $company_id . '/audios/';
        }
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, $mode = 0777, true, true);
        }

        $video = $request->file('upload_audio');
        //print_r($video); die();
        if ($video) {
            $validator = Validator::make($request->all(), ['upload_audio' => 'mimes:mpga,mp3,audio/mp3,audio/mpeg,mpeg3,audio/x-mpeg-3,audio/mp3,audio/wav,audio/mpeg,audio/x-wav,wav,ogg|max:50000']);
            if ($validator->fails()) {
                $messages = $validator->messages();
                return response()->success(['error' => $messages]);
            }
            $input['upload_audio'] = time() . '.' . $video->getClientOriginalExtension();

            $video->move($destinationPath, $input['upload_audio']);
            $video_path = $actual_path . $input['upload_audio'];
            $path = url('/') . $video_path;
            $out = array(
                'path' => $path,
            );
            return response()->success($out);
        }
        return response()->error('file not found');
    }
    private function sendEmailAdm($company_id, $input, $rating, $reviewId = null)
    {
        $default_phone_country_code = default_phone_country_code();
        $company_information = CompanyHelper::getCompanyDetais($company_id);
        $company_email = $company_information['email'];
        $company_phone = $default_phone_country_code . $company_information['phone'];

        $email_message = NotificationHelper::getNotificationMethod(0, 'mail', 'RECEIVE_REVIEW_EMAIL');
        $email_subject = NotificationHelper::getNotificationSubject(0, 'mail', 'RECEIVE_REVIEW_EMAIL');
        $email_subject = str_replace("{{client_name}}", ucwords($company_information['name']), $email_subject);
        /* Change Logo of email */
        $url_app = url('');
        $email_message = str_replace("{{app_url}}", $url_app, $email_message);
        $email_message = str_replace("{{contact_name}}", ucwords($input['first_name'] . " " . $input['last_name']), $email_message);
        $email_message = str_replace("{{review_text}}", $input['review'], $email_message);
        $email_message = str_replace("{{rating}}", $rating, $email_message);
        $email_message = str_replace("{{email}}", $input['email'], $email_message);
        $bob_s = '<img src="' . url('/') . '/img/bob_sign.png" alt="Bob Signature">';
        $email_message = str_replace("{{bob_signature}}", $bob_s, $email_message);
        $enable_email_notification = CompanySettingsHelper::getSetting($company_id, 'new_review_notification_email');

        if ($email_message != false && $email_subject != false && $enable_email_notification == 1) {
            $message = nl2br($email_message);
            $app_from_email = app_from_email();
            $data['company_information'] = $company_information;
            $data['company_information']['logo'] = '/img/mail_image_preview.png';

            $data['content_data'] = $email_message;
            $bcc_email = getenv('BCC_EMAIL');
            CompanySettingsHelper::sendCompanyEmailNotifcation($company_id, $data, $email_subject, $bcc_email, 'emails.social_post_publish', $app_from_email);
            \App\Classes\CompanySettingsHelper::sendCompanyEmailNotifcationLogs($company_id, $email_message, $email_subject, $reviewId, 'review', 'RECEIVE_REVIEW_EMAIL');
        }
    }
    private function sendSmsAdm($company_id, $input, $rating, $review_id = null)
    {
        $url_app = url('');
        $company_information = CompanyHelper::getCompanyDetais($company_id);
        $company_phone = $company_information['phone'];
        $sms_message = NotificationHelper::getNotificationMethod(0, 'sms', 'RECEIVE_REVIEW_SMS');
        $sms_message = str_replace("{{app_url}}", $url_app, $sms_message);
        $sms_message = str_replace("{{contact_name}}", ucwords($input['first_name'] . " " . $input['last_name']), $sms_message);
        $sms_message = str_replace("{{review_text}}", $input['review'], $sms_message);
        CompanySettingsHelper::sendSmsToCompanyNotifyUsers($company_id, $sms_message, $company_phone);
        \App\Classes\CompanySettingsHelper::sendCompanySmsNotifcationLogs($company_id, $sms_message, $company_phone, $review_id, 'RECEIVE_REVIEW_SMS', 'review', $company_id);
    }

    public function getEmailTemplate($slug_template = null)
    {
        if ($slug_template==null) {
            return response()->error(['message'=>'please provide email template id']);
        }
        $slug_template =  strtoupper($slug_template);
        $email_template = NotificationHelper::getNotificationMethod(0, 'evergenius_admin_notifications', $slug_template);
        $email_subject = NotificationHelper::getNotificationSubject(0, 'evergenius_admin_notifications', $slug_template);
        if ($email_template!=false && $email_subject!=false) {
            $email_template = ['subject'=>$email_subject,'body'=>$email_template];
            return response()->success(compact('email_template'));
        }
        return response()->error(['message'=>'please provide valid email template id']);
    }
}
