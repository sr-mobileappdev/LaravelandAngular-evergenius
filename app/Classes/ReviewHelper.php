<?php
namespace App\Classes;

use App\Review;
use App\ReviewQuestion;
use App\ReviewSetting;
use DateTime;
use DB;

class ReviewHelper
{

    public static function getCompanyReviewSettings($company_id)
    {
        $settings = ReviewSetting::where('company_id', $company_id)->first();
        if (count($settings) > 0) {
            return $settings;
        }
        return false;
    }

    public static function createNewCompanySettings($company_id)
    {
        $time = new \DateTime();
        $ins_data = array('company_id' => $company_id, 'text_enable' => '1', 'audio_enable' => '1', 'video_enable' => '1', 'testimonial_one_pic' => '1', 'created_at' => $time);
        ReviewSetting::insert($ins_data);
    }
    public static function updateReviewSettings($data, $company_id)
    {
        $data['updated_at'] = new \DateTime();
        ReviewSetting::where('company_id', $company_id)->update($data);
    }

    public static function getCompanyRatingByStars($company_id)
    {
        $w = ['company_id' => $company_id];
        $reviews = Review::select('rating', DB::raw('count(*) as total'))
            ->where($w)
            ->whereNotNull('rating')
            ->groupBy('rating')
            ->where('hide', 0)
            ->orderBy('rating', 'desc')
            ->get();
        $rates = [5, 4, 3, 2, 1];
        $out = [];
        $rand_i = [];
        if (count($reviews) > 0) {
            $reviews_a = $reviews->toArray();
            $i = 1;
            $i = 0;
            foreach ($rates as $r_key => $rate) {
                $rand_i[] = $rate;
                foreach ($reviews_a as $value) {
                    $total_i = 0;
                    if ($value['rating'] == $rate) {
                        $total_i = $value['total'];
                        break;
                    }
                }
                $out[] = array('rating' => $rate, 'total_reviews' => $total_i);
            }
        } else {
            foreach ($rates as $r_key => $rate) {
                $out[] = array('rating' => $rate, 'total_reviews' => 0);
            }
        }
        return $out;
    }

    public static function getCompanyReviews($company_id, $limit = 5, $featured = null, $provider_id = null)
    {
        $w = ['company_id' => $company_id];
        if ($featured != null) {
            $w['featured'] = 1;
        }
        if ($provider_id != null) {
            $w['provider_id'] = $provider_id;
        }

        $reviews = Review::where($w)
            ->whereNotNull('rating')
            ->where('hide', 0)
            ->orderBy('order_review', 'asc')
            ->limit($limit)
            ->get();
        if (count($reviews) > 0) {
            return $reviews->toArray();
        }
        return [];
    }

    public static function getCompanyAllReviews($company_id, $limit = 5, $featured = null, $date_start = null, $date_end = null)
    {
        $where = [];
        //echo $date_end; die();
        if ($date_start != null) {
            $d_s = array('created_at', '>', date('Y-m-d 00:00:00', strtotime($date_start)));
            $d_e = array('created_at', '<', date('Y-m-d 23:59:59', strtotime($date_end)));
            array_push($where, $d_s, $d_e);
        }

        $w = ['company_id' => $company_id];
        if ($featured != null) {
            $w['featured'] = 1;
        }

        $reviews = Review::where($w)
            ->where($where)
            ->whereNotNull('rating')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        if (count($reviews) > 0) {
            return $reviews->toArray();
        }
        return [];
    }

    public static function getTotalCompanyReviews($company_id, $limit = 5)
    {
        $w = ['company_id' => $company_id];
        $reviews = Review::where($w)
            ->whereNotNull('rating')
            ->where('hide', 0)
            ->count();
        return $reviews;
    }

    public static function updateReviewOrder($data, $company_id)
    {
        $update_data = [];
        $old_order = ReviewHelper::getReviewOrder($company_id);
        if ($old_order) {
            //print_r($data);
            $updated_data = $data;
            //$updated_data = array_diff($old_order,$data);
        }
        //print_r($updated_data); die();
        foreach ($updated_data as $key => $value) {
            $id = $value['id'];
            $order_review = $value['order_review'];
            Review::where('id', $id)->update(array('order_review' => $order_review));

            //eview::where($w)
        }
    }

    public static function getReviewOrder($company_id)
    {
        $reviews = Review::select('order_review', 'id')->where('company_id', $company_id)->get();
        if (count($reviews) > 0) {
            return $reviews->toArray();
        }
        return false;
    }

    public static function IncrementReviewOrder($company_id)
    {
        $old_order = ReviewHelper::getReviewOrder($company_id);
        if ($old_order) {
            foreach ($old_order as $value) {
                $id = $value['id'];
                $order_review = $value['order_review'] + 1;
                Review::where('id', $id)->update(array('order_review' => $order_review));
            }
        }
    }

    public static function updateReviewStatus($review_id, $status, $company_id)
    {
        if ($status == 'hide') {
            $update_data = array('hide' => 1, 'company_id' => $company_id);
        } else {
            $update_data = array('hide' => 0, 'company_id' => $company_id);
        }
        Review::where('id', $review_id)->update($update_data);
        return true;
    }

    public static function updateReviewFeature($review_id, $status, $company_id)
    {
        $update_data = array('featured' => $status, 'company_id' => $company_id);
        Review::where('id', $review_id)->update($update_data);
        return true;
    }

    public static function updateCompanyRequestForm($form_data, $company_id)
    {
        ReviewSetting::where('company_id', $company_id)->update(['request_form' => json_encode($form_data)]);
        return true;
    }

    public static function updateCompanyEmbeddedCode($form_data, $company_id)
    {
        ReviewSetting::where('company_id', $company_id)->update(['embedded_code' => json_encode($form_data)]);
        return true;
    }

    public static function getCompanyReviewScripts($company_id, $type_script = 'embedded_code')
    {
        $scripts = ReviewSetting::select($type_script)->where('company_id', $company_id)->first();
        if (count($scripts) > 0) {
            $script_a = $scripts->toArray();
            return json_decode($script_a[$type_script]);
        }
        return false;
    }

    public static function getAverageRatingByType($company_id, $column)
    {
        $review_avg = Review::where('company_id', $company_id)
            ->whereNotNull($column)
            ->where('hide', 0)
            ->avg($column);
        return $review_avg;
    }
    public static function getReviewQuestions($company_id)
    {
        $review_q = ReviewQuestion::all()->toArray();
        return $review_q;
    }

    public static function sendReviewNotification($company_id, $input)
    {
        $company_information = CompanyHelper::getCompanyDetais($company_id);
        $email_message = NotificationHelper::getNotificationMethod(0, 'mail', 'RECEIVE_REVIEW_EMAIL');
        $email_subject = NotificationHelper::getNotificationSubject(0, 'mail', 'RECEIVE_REVIEW_EMAIL');
        $enable_email_notification = CompanySettingsHelper::getSetting($company_id, 'new_review_notification_email');
        if ($email_message!==false && $email_subject!=false && ($enable_email_notification!==false && $enable_email_notification!=0)) {
            $email_subject = self::renderReviewMessage($company_id, $input, $email_subject);
            $email_message = self::renderReviewMessage($company_id, $input, $email_message);
            $email_message = nl2br($email_message);
            $app_from_email = app_from_email();
            $data['company_information'] = $company_information;
            $data['company_information']['logo'] = '/img/mail_image_preview.png';
            $data['content_data'] = $email_message;
            $bcc_email = getenv('BCC_EMAIL');
            CompanySettingsHelper::sendCompanyEmailNotifcation($company_id, $data, $email_subject, $bcc_email, 'emails.social_post_publish', $app_from_email);
            \App\Classes\CompanySettingsHelper::sendCompanyEmailNotifcationLogs($company_id, $email_message, $email_subject, $input['review_id'], 'review', 'RECEIVE_REVIEW_EMAIL');
        }
         $review_sms = NotificationHelper::getNotificationMethod(0, 'sms', 'RECEIVE_REVIEW_SMS');
         $enable_sms_notification = CompanySettingsHelper::getSetting($company_id, 'new_review_notification_email');
        if ($review_sms!==false && ($enable_sms_notification!==false && $enable_sms_notification!=0)) {
            $review_sms = self::renderReviewMessage($company_id, $input, $review_sms);
            $company_phone = $company_information['phone'];
            CompanySettingsHelper::sendSmsToCompanyNotifyUsers($company_id, $review_sms, $company_phone);
            \App\Classes\CompanySettingsHelper::sendCompanySmsNotifcationLogs($company_id, $review_sms, $company_phone, $input['review_id'], 'RECEIVE_REVIEW_SMS', 'review', $company_id);
        }
    }

    public static function renderReviewMessage($company_id, $input, $message)
    {
        $default_phone_country_code = default_phone_country_code();
        $company_information = CompanyHelper::getCompanyDetais($company_id);
        $company_email = $company_information['email'];
        $message = str_replace("{{client_name}}", ucwords($company_information['name']), $message);
        $message = str_replace('{$client_name}', ucwords($company_information['name']), $message);
        /* Change Logo of email */
        $url_app = url('');
        $message = str_replace("{{app_url}}", $url_app, $message);
        $message = str_replace('{$app_url}', $url_app, $message);

        $message = str_replace("{{contact_name}}", ucwords($input['first_name'] . " " . $input['last_name']), $message);
        $message = str_replace('{$contact_name}', ucwords($input['first_name'] . " " . $input['last_name']), $message);

        if (isset($input['review'])) {
            $message = str_replace("{{review_text}}", $input['review'], $message);
            $message = str_replace('{$review_text}', $input['review'], $message);
        } else {
            $message = str_replace("{{review_text}}", "", $message);
            $message = str_replace('{$review_text}', "", $message);
        }

        if (isset($input['rating'])) {
            $message = str_replace("{{rating}}", $input['rating'], $message);
            $message = str_replace('{$rating}', $input['rating'], $message);
        } else {
            $message = str_replace("{{rating}}", "", $message);
            $message = str_replace('{$rating}', "", $message);
        }
        if (isset($input['email'])) {
            $message = str_replace("{{email}}", $input['email'], $message);
            $message = str_replace('{$email}', $input['email'], $message);
        } else {
            $message = str_replace("{{email}}", "", $message);
            $message = str_replace('{$email}', "", $message);
        }

        $bob_s = '<img src="' . url('/') . '/img/bob_sign.png" alt="Bob Signature">';
        $message = str_replace("{{bob_signature}}", $bob_s, $message);
        $message = str_replace('{$bob_signature}', $bob_s, $message);
        return $message;
    }
}
