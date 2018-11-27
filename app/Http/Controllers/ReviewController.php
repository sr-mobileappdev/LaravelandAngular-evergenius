<?php

namespace App\Http\Controllers;

use App\Classes\CompanyHelper;
use App\Classes\CompanySettingsHelper;
use App\Classes\Html;
use App\Classes\ActivityHelper;
use App\User;
use App\Appointment;
use App\Contact;
use App\AppointmentsDt;
use App\Company;
use Auth;
use App\DtReview;
use Bican\Roles\Models\Permission;
use Bican\Roles\Models\Role;
use Hash;
use Illuminate\Http\Request;
use Input;
use Validator;
use App\AppointmentStatus;
use dateTime;
use App\AppointmentService;
use App\UserSetting;
use App\Review;
use Mail;
use App\Classes\ContactHelper;
use App\Classes\CronHelper;
use App\Classes\NotificationHelper;
use App\Classes\ReviewHelper;
use App\Classes\yextApiHelper;
use DB;

class ReviewController extends Controller
{
    public function addReview(Request $request)
    {
        $Contact_id = null;
        $provider_id = null;
        $company_id = $request['company_id'];
        $input = $request['data'];
        $publisher_id = 'WEBSITE';
        if (isset($input['source']) && $input['source'] !='') {
            $publisher_id = strtoupper($input['source']);
        }
        $this->validate($request, [
            'data.first_name' => 'required',
            //'data.last_name' => 'required',
            //'data.doctor_id' => 'required',
            'data.rating' => 'required',
            'data.review' => 'required',
            'data.email' => 'required|email' ,
            'data.rating' => 'required'
        ]);
        $l_name = null;

        $is_contact_exists = ContactHelper::isContactExists($company_id, $input['email']);
        if ($is_contact_exists) {
            $get_contact_info = ContactHelper::findContactByemail($input['email'], $company_id);
            $Contact_id = $get_contact_info['id'];
        } else {
            $Contact_id = null;
        }
        if (isset($input['doctor_id'])) {
            $provider_id = $input['doctor_id'];
        }


        $f_name = ucfirst(trim($input['first_name']));
        if (isset($input['last_name'])) {
            $l_name = ucfirst(trim($input['last_name']));
        }
        $email = strtolower(trim($input['email']));
        $rating =  trim($input['rating']);
        $review_u =  trim($input['review']);
        $type_review = 'website';
        $published_time = new \DateTime();
        ReviewHelper::IncrementReviewOrder($company_id);

        $review = ContactHelper::addContactReview($Contact_id, $provider_id, $f_name, $l_name, $email, $company_id, null, null, null, null, null, $rating, $review_u, null, $type_review, 'WEBSITE', null, $published_time);

        $default_phone_country_code = default_phone_country_code();
        $company_information = CompanyHelper::getCompanyDetais($company_id);
        $company_phone = $default_phone_country_code . $company_information['phone'];

        $email_message = NotificationHelper::getNotificationMethod(0, 'mail', 'RECEIVE_REVIEW_EMAIL');
        $email_subject = NotificationHelper::getNotificationSubject(0, 'mail', 'RECEIVE_REVIEW_EMAIL');
        $email_subject = str_replace("{{client_name}}", ucwords($company_information['name']), $email_subject);

        /* Change Logo of email */

        $url_app = url('');

        $email_message = str_replace("{{app_url}}", $url_app, $email_message);
        $email_message = str_replace("{{contact_name}}", ucwords($input['first_name'] . " " . $input['last_name']), $email_message);
        $email_message = str_replace("{{review_text}}", $input['review'], $email_message);
        $email_message = str_replace("{{rating}}", $input['rating'], $email_message);
        $email_message = str_replace("{{email}}", $input['email'], $email_message);
        $bob_s = '<img src="' . url('/') . '/img/bob_sign.png" alt="Bob Signature">';
        $email_message = str_replace("{{bob_signature}}", $bob_s, $email_message);

        $enable_email_notification = CompanySettingsHelper::getSetting($company_id, 'new_review_notification_email');

        if ($email_message != false && $email_subject != false && $enable_email_notification == 1) {
            $email_message = nl2br($email_message);
            $app_from_email = app_from_email();
            $data['company_information'] = $company_information;
            $data['company_information']['logo'] = '/img/mail_image_preview.png';

            $data['content_data'] = $email_message;
            $bcc_email = getenv('BCC_EMAIL');
            CompanySettingsHelper::sendCompanyEmailNotifcation($company_id, $data, $email_subject, $bcc_email, 'emails.social_post_publish', $app_from_email);
            \App\Classes\CompanySettingsHelper::sendCompanyEmailNotifcationLogs($company_id, $email_message, $email_subject, $review, 'review', 'RECEIVE_REVIEW_EMAIL');
        }

        $sms_message = NotificationHelper::getNotificationMethod(0, 'sms', 'RECEIVE_REVIEW_SMS');
        $sms_message = str_replace("{{app_url}}", $url_app, $sms_message);
        $sms_message = str_replace("{{contact_name}}", ucwords($input['first_name'] . " " . $input['last_name']), $sms_message);
        $sms_message = str_replace("{{review_text}}", $input['review'], $sms_message);
        $email_message = str_replace("{{rating}}", $input['rating'], $email_message);
        $email_message = str_replace("{{email}}", $input['email'], $email_message);
        $bob_s = '<img src="' . url('/') . '/img/bob_sign.png" alt="Bob Signature">';
        $email_message = str_replace("{{bob_signature}}", $bob_s, $email_message);
        //$company_notify_contact = CompanySettingsHelper::fetchNotifyUsersContacts($company_id,$company_phone);
        $enable_sms_notification = CompanySettingsHelper::getSetting($company_id, 'new_review_notification_sms');
        if ($enable_sms_notification) {
            CompanySettingsHelper::sendSmsToCompanyNotifyUsers($company_id, $sms_message, $company_phone);
            \App\Classes\CompanySettingsHelper::sendCompanySmsNotifcationLogs($company_id, $sms_message, $company_phone, $review, 'RECEIVE_REVIEW_SMS', 'review', $company_id);
        }
        return response()->success('Review added successfully.');
    }

    /******** Reviews pull from yext ********/
    public static function pull_yext_reviews()
    {
        $last_fetch_time = CronHelper::getRecentExecutedTime('yext_review_pull');
        $cron_id = CronHelper::createCronRecord('yext_review_pull');
        $compnies = CompanyHelper::getAllCompanies();
        yextApiHelper::updateYextReviews($compnies, $last_fetch_time);
        CronHelper::udateCronEndTime($cron_id);
        return response()->success('success');
    }

    public function getIndex()
    {
        $user = Auth::user();
        $company_id = $user->company_id;

        $reviews = DtReview::select('id', 'publisher_id', 'published_time', 'rating', 'provider_id', 'user_review', 'url', 'provider_name', 'order_review', 'hide', 'audio_url', 'video_url', 'img_url', 'featured', DB::raw('CONCAT(first_name," ",last_name) as client_name'))
            ->where('company_id', $company_id)
            ->orderBy('order_review', 'asc')->get();
        return response()->success(compact('reviews'));
        //return Datatables::of($reviews)->make(true);
    }

    public function getSiteReputation()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $total_reviws = yextApiHelper::get_total_reviews($company_id);
        $avg_rating = yextApiHelper::get_avg_ratings($company_id);
        $review_platforms = yextApiHelper::get_revies_platforms($company_id);
        $nul_c = 0;
        $out_res = [];
        foreach ($review_platforms as $key => $value) {
            if (is_null($value['publisher_id'])) {
                $nul_c = $value['total'];
                unset($review_platforms[$key]);
            }
            if ($value['publisher_id'] == 'WEBSITE' || $value['publisher_id'] == 'website') {
                if ($nul_c > 0) {
                    $value['total'] = $value['total'] + $nul_c;
                }
            }
            if (!is_null($value['publisher_id'])) {
                $out_res[] = $value;
            }
        }
        $out = array(
            'total_reviws' => $total_reviws,
            'avg_rating' => $avg_rating,
            'review_platforms' => $out_res,
        );
        return response()->success($out);
    }

    public function getDashboardWidget()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $total_reviws = yextApiHelper::get_total_reviews($company_id, null, null, true);
        $star_ratings = yextApiHelper::getCompanyRatingByStars($company_id);
        $positive_reviews = yextApiHelper::ratingAboveEuqal(3, $company_id);
        $negtive_reviews = yextApiHelper::ratingLessEuqal(2, $company_id);
        return response()->success(compact('total_reviws', 'star_ratings', 'positive_reviews', 'negtive_reviews'));
    }

    public function getCompanyReviewEmails()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $review_emails = \App\Classes\NotificationHelper::getCompanyReviesEmails($company_id);
        if (count($review_emails) == 0) {
            \App\Classes\NotificationHelper::createCompanyReviesEmails($company_id);
            $review_emails = \App\Classes\NotificationHelper::getCompanyReviesEmails($company_id);
        }
        return response()->success(compact('review_emails'));
    }

    public function getReviewSettings()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $settings = ReviewHelper::getCompanyReviewSettings($company_id);
        if ($settings == false) {
            ReviewHelper::createNewCompanySettings($company_id);
            $settings = ReviewHelper::getCompanyReviewSettings($company_id);
        }
        $review_emails = \App\Classes\NotificationHelper::getCompanyReviesEmails($company_id);
        if (count($review_emails) == 0) {
            \App\Classes\NotificationHelper::createCompanyReviesEmails($company_id);
            $review_emails = \App\Classes\NotificationHelper::getCompanyReviesEmails($company_id);
        }
        return response()->success(compact(['settings', 'review_emails']));
    }

    public function postReviewSettings()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input = input::get();

        if ($input['settings']) {
            $settings = $input['settings'];

            if (isset($input['settings']['email_1_id']) && isset($input['settings']['email_1_enable'])) {
                \App\Classes\NotificationHelper::updateNotificationEmailStatus(
                    $input['settings']['email_1_id'],
                    $input['settings']['email_1_enable']
                );
                unset($settings['email_1_enable']);
            }
            if (isset($input['settings']['email_2_id']) && isset($input['settings']['email_2_enable'])) {
                \App\Classes\NotificationHelper::updateNotificationEmailStatus($input['settings']['email_2_id'], $input['settings']['email_2_enable']);
                unset($settings['email_2_enable']);
            }
            if (isset($input['settings']['email_3_id']) && isset($input['settings']['email_2_enable'])) {
                \App\Classes\NotificationHelper::updateNotificationEmailStatus($input['settings']['email_3_id'], $input['settings']['email_3_enable']);
                unset($settings['email_3_enable']);
            }
            ReviewHelper::updateReviewSettings($settings, $company_id);
            return response()->success(compact('settings'));
        }
    }

    public function getEmailContent($mail_id)
    {
        $email_content = \App\Classes\NotificationHelper::getNotificationEmail($mail_id);
        if ($email_content) {
            return response()->success(compact('email_content'));
        }
        return response()->failed('email content not found');
    }

    public function postUpdateEmailContent()
    {
        $data = Input::get();
        $id = $data['id'];
        \App\Classes\NotificationHelper::updateNotificationEmail($id, $data);
        return response()->success(['status' => 'success']);
    }

    public function postChangeOrder()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $Input = Input::get();
        if (isset($Input['review_order'])) {
            $review_order = $Input['review_order'];
            ReviewHelper::updateReviewOrder($review_order, $company_id);
        }
    }

    public function postReviewStatus()
    {
        $Input = Input::get();
        if (isset($Input['id']) && isset($Input['status'])) {
            $user = Auth::user();
            $company_id = $user->company_id;
            $review_id = $Input['id'];
            $status = $Input['status'];
            ReviewHelper::updateReviewStatus($review_id, $status, $company_id);
            return response()->success(['status' => 'success']);
        }
        return response()->error('review not updated');
    }

    public function postReviewFeatureUpdate()
    {
        $Input = Input::get();
        if (isset($Input['id']) && isset($Input['status'])) {
            $user = Auth::user();
            $company_id = $user->company_id;
            $review_id = $Input['id'];
            $status = $Input['status'];
            ReviewHelper::updateReviewFeature($review_id, $status, $company_id);
            return response()->success(['status' => 'success']);
        }
        //return response()->error('review not updated');
    }

    public function postSaveRequestForm()
    {
        $input = Input::get();
        $user = Auth::user();
        $company_id = $user->company_id;
        if (isset($input['settings'])) {
            $widget_settings = $input['settings'];
            $settings = ReviewHelper::getCompanyReviewSettings($company_id);
            if ($settings == false) {
                ReviewHelper::createNewCompanySettings($company_id);
            }
            ReviewHelper::updateCompanyRequestForm($widget_settings, $company_id);
            return response()->success(['status' => 'success']);
        }
        return response()->error('Settings not found.');
    }

    public function postSaveEmbedCode()
    {
        $input = Input::get();
        $user = Auth::user();
        $company_id = $user->company_id;
        if (isset($input['settings'])) {
            $widget_settings = $input['settings'];
            $settings = ReviewHelper::getCompanyReviewSettings($company_id);
            if ($settings == false) {
                ReviewHelper::createNewCompanySettings($company_id);
            }
            ReviewHelper::updateCompanyEmbeddedCode($widget_settings, $company_id);
            return response()->success(['status' => 'success']);
        }
        return response()->error('Settings not found.');
    }

    public function getEmbedCode()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $settings = ReviewHelper::getCompanyReviewSettings($company_id);
        if ($settings == false) {
            ReviewHelper::createNewCompanySettings($company_id);
        }

        $embded_code = ReviewHelper::getCompanyReviewScripts($company_id, 'embedded_code');
        if ($embded_code) {
            return response()->success(compact('embded_code'));
        }
        return response()->error('Code not fond');
    }

    public function getRequestForm()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $settings = ReviewHelper::getCompanyReviewSettings($company_id);
        if ($settings == false) {
            ReviewHelper::createNewCompanySettings($company_id);
        }
        $request_form = ReviewHelper::getCompanyReviewScripts($company_id, 'request_form');
        if ($request_form) {
            return response()->success(compact('request_form'));
        }
        return response()->error('Code not fond');
    }

    public static function getComapanyReviews()
    {
        $limit = 500;
        $input = Input::get();
        $featured = 0;
        $provider_id = null;
        $company_id = $input['company_id'];
        if (isset($input['limit'])) {
            $limit = $input['limit'];
        }
        if (isset($input['provider_id'])) {
            $provider_id = $input['provider_id'];
        }
        if (isset($input['featured'])) {
            $featured = $input['featured'];
        }
        $reviews = ReviewHelper::getCompanyReviews($company_id, $limit, $featured, $provider_id);
        return response()->success(compact('reviews'));
    }
}
