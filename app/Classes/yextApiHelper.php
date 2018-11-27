<?php
namespace App\Classes;

use App\Classes\CompanySettingsHelper;
use App\Classes\ContactHelper;
use App\Classes\ReviewHelper;
use App\Review;
use Curl;
use DateTime;
use DB;
use App\Http\Controllers\ExtrernalScriptsController;

class yextApiHelper
{
    public static function getProfileListingCurl($api_url, $api_key, $account_id, $location_id, $date)
    {
        $full_url = $api_url . '/v2/accounts/' . $account_id . '/powerlistings/listings';
        $response = Curl::to($full_url)
            ->withData(array('api_key' => $api_key,
                'locationIds' => $location_id,
                'v' => $date,
            ))
            ->asJson()
            ->get();

        return $response;
    }

    public static function getPublisherListingCurl($api_url, $api_key, $account_id, $location_id, $date)
    {
        $full_url = $api_url . '/v2/accounts/me/powerlistings/publishers';
        $response = Curl::to($full_url)
            ->withData(array('api_key' => $api_key,
                'locationIds' => $location_id,
                'v' => $date,
            ))
            ->asJson()
            ->get();

        return $response;
    }

    public static function updateYextReviews($compnies, $last_update)
    {
        $last_update_date = null;
        $yex_api_key = getenv('YEXT_API_KEY');
        $yex_account_ID = getenv('YEXT_ACCOUNT_ID');
        $yex_api_url = getenv('YEXT_API_URL');
        $date = new \DateTime;
        $date_today = $date->format('Y-m-d');
        $date_V = $date->format('Ymd');
        if ($last_update != false) {
            $last_update = new \DateTime($last_update);
            $last_update_date = $date->format('Y-m-d');
        }

        foreach ($compnies as $key => $company) {
            $company_id = $company['id'];
            $yext_location_id = CompanySettingsHelper::getSetting($company_id, 'yext_location_id');


            if ($yext_location_id != false) {
                $reviews = yextApiHelper::get_review_before_date($yex_api_key, $yex_account_ID, $yext_location_id, $yex_api_url, $date_today, $date_V, $last_update_date);
                if (count($reviews)>0) {
                    foreach ($reviews as $key => $review) {
                        yextApiHelper::storeYextReview($company_id, $review);
                    }
                }
            }
        }
        return true;
    }

    public static function get_review_before_date($yex_api_key, $yex_account_ID, $location_id, $yex_api_url, $date, $date_v, $date_last_update = '')
    {
        $full_url = $yex_api_url . '/v2/accounts/' . $yex_account_ID . '/reviews';
        $response = Curl::to($full_url)
            ->withData(array('api_key' => $yex_api_key,
                'locationIds' => $location_id,
                'v' => $date_v,
                'limit' => 100,
            ))
            ->asJson()
            ->get();
        if (isset($response->response->reviews)) {
            return $response->response->reviews;
        }
        return [];
    }

    public static function storeYextReview($company_id, $review)
    {
        $seconds = time();
        $rating = null;
        $name = split_name($review->authorName);
        $yext_review_id = $review->id;
        if (isset($review->rating)) {
            $rating = $review->rating;
        }
        $review_u = $review->content;
        $url = $review->url;
        $publisher_id = $review->publisherId;
        $name = split_name($review->authorName);
        $yext_review_id = $review->id;
        if (isset($review->publisherDate)) {
            $seconds = $review->publisherDate / 1000;
        }
        $publish_time = date('Y-m-d H:i:s', $seconds);

        ReviewHelper::IncrementReviewOrder($company_id);
        // If yext review not exists
        if (!yextApiHelper::checkYextReviewExists($yext_review_id, $company_id)) {
            $input = array(
                'first_name'=>$name[0],
                'last_name'=>$name[1]
            );
            if (isset($review->rating)) {
                $input['rating'] =  $review->rating;
            }
            if (isset($review->authorEmail)) {
                $input['email'] =  $review->authorEmail;
            }
            if (isset($review->content)) {
                $input['review'] =  $review->content;
            }
            if (isset($yext_review_id)) {
                $input['review_id'] =  $yext_review_id;
            }
            ReviewHelper::sendReviewNotification($company_id, $input);
            ContactHelper::addContactReview(null, null, $name[0], $name[1], null, $company_id, null, null, null, null, null, $rating, $review_u, $url, 'yext', $publisher_id, $yext_review_id, $publish_time);
        }
        return true;
    }

    public static function checkYextReviewExists($review_id, $company_id)
    {
        $reviews = Review::where(array('type_review' => 'yext', 'yext_review_id' => $review_id, 'company_id' => $company_id))->count();
        if ($reviews > 0) {
            return true;
        }
        return false;
    }

    public static function get_total_reviews($company_id, $start_date = null, $end_date = null, $rate = null)
    {
        $where = [];
        if ($start_date != null && $end_date != null) {
            $d_s = array('created_at', '>=', $start_date);
            $d_e = array('created_at', '<=', $end_date);
            array_push($where, $d_s, $d_e);
        }
        if ($rate != null) {
            $rt = array('rating', '!=', null);
            array_push($where, $rt);
        }

        $reviews = Review::where(array('company_id' => $company_id))
            ->where($where)
            ->count();
        return $reviews;
    }

    public static function get_avg_ratings($company_id, $start_date = null, $end_date = null)
    {
        $where = [];
        if ($start_date != null && $end_date != null) {
            $d_s = array('created_at', '>=', $start_date);
            $d_e = array('created_at', '<=', $end_date);
            array_push($where, $d_s, $d_e);
        }
        $stars = [];
        $ratings = Review::select('rating')->where(array('company_id' => $company_id))
            ->whereNotNull('rating')
            ->where($where)
            ->get()
            ->toArray();
        foreach ($ratings as $rating) {
            $stars[] = $rating['rating'];
        }
        $total = array_sum($stars);
        if (count($stars) > 0) {
            $avg = $total / count($stars);
            return round($avg, 2);
        }
        return 0;
    }

    public static function get_revies_platforms($company_id)
    {
        $reviews = Review::select('publisher_id', DB::raw('count(id) as total'))->groupBy('publisher_id')->where(array('company_id' => $company_id))->orderBy('total', 'desc')->get()->toArray();
        return $reviews;
    }

    public static function getCompanyRatingByStars($company_id, $start_date = null, $end_date = null)
    {
        $where = [];
        if ($start_date != null && $end_date != null) {
            $d_s = array('created_at', '>=', $start_date);
            $d_e = array('created_at', '<=', $end_date);
            array_push($where, $d_s, $d_e);
        }

        $w = ['company_id' => $company_id];
        $reviews = Review::select('rating', DB::raw('count(*) as total'))
            ->where($w)
            ->where($where)
            ->whereNotNull('rating')
            ->groupBy('rating')
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
                foreach ($reviews_a as $key => $value) {
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
    public static function ratingAboveEuqal($rate, $company_id)
    {
        $reviews = Review::select(DB::raw('count(*) as total'))
            ->where('rating', '>=', $rate)
            ->where('company_id', '=', $company_id)
            ->first();
        if (count($reviews) > 0) {
            return $reviews->total;
        }
        return 0;
    }
    public static function ratingLessEuqal($rate, $company_id)
    {
        $reviews = Review::select(DB::raw('count(*) as total'))
            ->where('rating', '<=', $rate)
            ->where('company_id', '=', $company_id)
            ->first();
        if (count($reviews) > 0) {
            return $reviews->total;
        }
        return 0;
    }

    public static function publishReviewYext($autherName, $autherEmail, $rating, $content, $date, $company_id, $review_id)
    {
        $api_url = getenv('YEXT_API_URL');
        $yext_api_key = getenv('YEXT_API_KEY');
        $account_id = getenv('YEXT_ACCOUNT_ID');
        $yext_location_id = CompanySettingsHelper::getSetting($company_id, 'yext_location_id');
        $review_post = array(
            'rating' => $rating,
            'content' => $content,
            'authorName' => $autherName,
            'authorEmail' => $autherEmail,
            'status' => 'LIVE',
            'date' => $date,
            'accountId' => $account_id,
            'locationId' => $yext_location_id,
        );
        $date = new DateTime;
        $date_v = $date->format('Ymd');
        $full_url = $api_url . '/v2/accounts/' . $account_id . '/reviews?api_key=' . $yext_api_key . '&v=' . $date_v;
        $response = Curl::to($full_url)
            ->withData($review_post)
            ->asJson()
            ->post();
        if (isset($response->response)) {
            if (isset($response->response->id)) {
                $response_id = $response->response->id;
                ReviewHelper::updateYextId($review_id, $response_id);
            }
        }
        return true;
    }
}
