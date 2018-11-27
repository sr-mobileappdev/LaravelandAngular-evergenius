<?php

namespace App\Http\Controllers;

use App\CiPostsView;
use App\Classes\AppOptionsHelper;
use App\Classes\CompanyHelper;
use App\Classes\CronHelper;
use App\Classes\SocialconnectHelper;
use App\SiCampaigns;
use App\SiNetwork;
use Auth;
use Datatables;
use dateTime;
use Illuminate\Http\Request;
use Input;
use Session;

class SocialController extends Controller
{
    public $fb;
    public $fb_app_id;
    public $fb_app_secret;
    public $company_id;
    public $profile_names;
    /* Set Social settings first*/
    public function __construct()
    {
        if (!session_id()) {
            session_start();
        }
        $this->fb_app_id = AppOptionsHelper::getOptionValue('facebook_app_id');
        $this->fb_app_secret = AppOptionsHelper::getOptionValue('facebook_app_secret');

        /* Get Current User comapny Id */
        $this->profile_names = array('facebook', 'facebook_pages', 'facebook_groups', 'twitter', 'google_plus', 'instagram', 'linkedin');
    }

    public function configFbApp($fb_app_id, $fb_app_secret)
    {
        try {
            $this->fb = new Facebook\Facebook(
                [
                    'app_id' => $this->fb_app_id,
                    'app_secret' => $this->fb_app_secret,
                    'default_graph_version' => 'v2.5',
                    'default_access_token' => '{access-token}',
                ]
            );
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            get_instance()->session->set_flashdata('error', 'Graph returned an error: ' . $e->getMessage());
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            get_instance()->session->set_flashdata('error', 'Facebook SDK returned an error: ' . $e->getMessage());
        }
    }

    public function getIndex()
    {
        $this->set_current_user(); //Compersory to set in every function

        $fb_app_id = $this->fb_app_id;
        $fb_app_secret = $this->fb_app_secret;
        $settingsExists = SocialconnectHelper::isFbSettingsExists($fb_app_id, $fb_app_secret);
        if ($settingsExists) {
        }
    }

    public function postIndex()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input_data = Input::get();
        $where = array();
        $order_field = "id";
        $order_by = "desc";
        if (isset($input_data['customFilter']['status'])) {
            $where = array('status' => $input_data['customFilter']['status']);
            /* if Post status is history and draft  order_by */

            if ($input_data['customFilter']['status'] == 1 || $input_data['customFilter']['status'] == 0) {
                $order_field = "updated_at";
                $order_by = "desc";
            } elseif ($input_data['customFilter']['status'] == 1) {
                $order_field = "schedule_time";
                $order_by = "asc";
            }
        }
        $posts = CiPostsView::select()
            ->where('company_id', $company_id)
            ->where($where)
            ->orderBy($order_field, $order_by)->get();
        return Datatables::of($posts)->make(true);
    }

    public function getIsProfilesConnected()
    {
        $this->set_current_user(); //Compersory to set in every function
        $company_id = $this->company_id;
        $social_profiles = $this->profile_names;
        /* set sesion for logged users */
        Session::set('company_id', $company_id);
        Session::put('company_id', $company_id);
        $_SESSION['company_id'] = $company_id;
        /* set sesion for logged users */

        $out = array();
        foreach ($social_profiles as $network) {
            $is_connected = SocialconnectHelper::checkIsNetworkConnected($company_id, $network);
            if ($is_connected) {
                $network_details = SocialconnectHelper::getNetworkDetails($company_id, $network);
                $out[$network] = array('connected' => true, 'details' => $network_details);
            } else {
                $connect_link = SocialconnectHelper::getConnectUrl($network);
                $out[$network] = array('connected' => false, 'connect_url' => $connect_link);
            }
        }
        return response()->success($out);
    }

    public function Callback($network)
    {
        $link = url('/') . "/#/social-connect-profiles";
        $cookie_val = false;
        if ($network == 'facebook') {
            $token = Input::get();
            SocialconnectHelper::saveFacebokCallback($token);
        } elseif ($network == 'facebook_pages') {
            $token = Input::get();
            $facebook_connected = SocialconnectHelper::saveFacebokPageCallback($token);
            /* if Multiple Pages */
            if ($facebook_connected != false && isset($facebook_connected['multiple_pages'])) {
                $link = url('/') . "/#/social-connect-profiles?multiple_pages=1";
            }
        } elseif ($network == 'facebook_groups') {
            $token = Input::get();
            $facebook_connected = SocialconnectHelper::saveFacebokGroupsCallback($token);
            if ($facebook_connected != false && isset($facebook_connected['multiple_groups'])) {
                $link = url('/') . "/#/social-connect-profiles?multiple_groups=1";
            }
        } elseif ($network == 'twitter') {
            $token = Input::get();
            SocialconnectHelper::saveTwitterCallback($token);
        } elseif ($network == 'google_plus') {
            $token = Input::get();
            SocialconnectHelper::saveGoogleCallback($token);
        } elseif ($network == 'linkedin') {
            $token = Input::get();
            SocialconnectHelper::saveLinkedInCallback($token);
        }

        return \Redirect::to($link);
    }

    public function connectInstagram()
    {
        $action_url = url('/') . "/social/user/connect/instagram";
        return view('social.instagram', ['name' => 'James', 'action_url' => $action_url]);
    }

    public function saveConnectInstagram()
    {
        $Input = Input::get();
        if (!empty($Input['username']) && empty($Input['password'])) {
            {
                $username = trim($Input['username']);
                $password = trim($Input['password']);
                $check = new \InstagramAPI\Instagram(false, false);
                $check->setUser($username, $password);
                try {
                    $company_id = $_SESSION['company_id'];
                    $check->login();
                    if (SocialconnectHelper::checkIsNetworkConnected($company_id, 'instagram')) {
                        //$this->CI->session->set_flashdata('deleted', display_mess(79, 'twitter'));
                    } else {
                        SocialconnectHelper::add_network('instagram', $username, $password, $company_id, '', $username,
                            '');
                        $link = url('/') . "/#/social-connect-profiles";
                        return \Redirect::to($link);
                    }
                } catch (QueryException $e) {
                    throw new UserCreateException('You custom error message here');
                }
            }
        }
    }
    public function set_current_user()
    {
        $user = Auth::user();
        $this->company_id = $user->company_id;
    }
    public function postPublishPost()
    {
        $schedule_time = null;
        $user = Auth::user();
        $company_id = $user->company_id;
        $seleted_networks = Input::get('selected_networks');
        $network_posts = Input::get('network_posts');
        $post_title = Input::get('post_title');
        $post_tumb = Input::get('post_tumb');
        $post_status = Input::get('post_status');
        $Input = Input::get();
        if (isset($Input['schedule_time']) && $post_status == 2) {
            $schedule_time = new dateTime($Input['schedule_time']);
        }
        $campign_id = SocialconnectHelper::add_si_campaign($company_id, $post_status, $post_title, $post_tumb, $schedule_time);
        foreach ($seleted_networks as $network => $enable) {
            if ($enable == 1) {
                $network_post = $network_posts[$network];
                SocialconnectHelper::post_social_data($network, $network_post, $campign_id, $post_status);
            }
        }
        $msg[0] = 'Post successfully saved to draft';
        $msg[1] = 'Post successfully published';
        $msg[2] = 'Post successfully scheduled';
        $msg[3] = 'Post successfully queued';
        return response()->success($msg[$post_status]);
    }

    public function postUploadSocialImage(Request $request)
    {
        $user = Auth::user();
        $company_id = $company_id = $user->company_id;
        $image = $request->file('social_image');
        $network = $request->network;
        if ($image) {
            $this->validate(
                $request,
                [
                    'social_image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:10024',
                ]
            );
            $input['social_image'] = time() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('/images/social');
            $image->move($destinationPath, $input['social_image']);
            $image_path = "/images/social/" . $input['social_image'];
            $path = url('/') . $image_path;
            $out = array(
                'path' => $path,
                'network' => $network,
            );
            return response()->success($out);
        }
        return response()->error('file not found');
    }

    public static function publishScheduledPosts()
    {
        // Create Cron Record
        $cron_id = CronHelper::createCronRecord('si_scheduled_posts');
        SocialconnectHelper::publishScheduledPosts();
        CronHelper::udateCronEndTime($cron_id);
        return response()->success('schedule_campign_sent');
    }

    public function deletePost($campign_id)
    {
        $user = SiCampaigns::find($campign_id);
        $user->delete();
        return response()->success('Deleted');
    }

    public function deleteNetwork($network)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        SiNetwork::where('network_name', $network)->where('company_id', $company_id)->delete();
        return response()->success('Deleted');
    }

    public function getCampaignDetails($campign_id)
    {
        $campign = SiCampaigns::with('posts', 'posts.meta')->where('id', $campign_id)->first()->toArray();
        return response()->success(compact('campign'));
    }

    public function postEditPost()
    {
        $postId = Input::get('campign_id');
        $schedule_time = null;
        $user = Auth::user();
        $company_id = $user->company_id;
        $seleted_networks = Input::get('selected_networks');
        $network_posts = Input::get('network_posts');
        $post_title = Input::get('post_title');
        $post_tumb = Input::get('post_tumb');
        $post_status = Input::get('post_status');
        $Input = Input::get();
        $publish_draft = false;
        $post_s = $post_status;
        if (isset($Input['schedule_time']) && $post_status == 2 && $Input['schedule_time'] != 'publish') {
            $schedule_time = new dateTime($Input['schedule_time']);
        } elseif (isset($Input['schedule_time']) && $post_status == 0 && $Input['schedule_time'] == 'publish') {
            $publish_draft = true;
            $post_status = 1;
            $post_s = 4;
        } elseif (isset($Input['schedule_time']) && $post_status == 0 && $Input['schedule_time'] != '') {
            $publish_draft = false;
            $post_status = 2;
            $post_s = 2;
        }

        SocialconnectHelper::edit_si_campaign($postId, $company_id, $post_status, $post_title, $post_tumb, $schedule_time);
        foreach ($seleted_networks as $network => $enable) {
            if ($enable == 1) {
                $url = null;
                $img = null;
                $video = null;

                $network_post = $network_posts[$network];
                $body = $network_post['title'];
                if (isset($network_post['link'])) {
                    $url = $network_post['link'];
                }
                if (isset($network_post['image_url'])) {
                    $img = $network_post['image_url'];
                }
                if (isset($network_post['video_url'])) {
                    $video = $network_post['video_url'];
                }
                $title = '';
                if ($publish_draft != true) {
                    SocialconnectHelper::update_post($network, $company_id, $body, $title, $url, $img, $video, $postId, $post_status);
                } else {
                    SocialconnectHelper::post_social_data($network, $network_post, $postId, 1, $company_id, $publish_draft);
                }
            }
        }

        $msg[0] = 'Post edited successfully to draft';
        $msg[1] = 'Post successfully published';
        $msg[2] = 'Post edited successfully to scheduled';
        $msg[3] = 'Post edited to queue successfully!';
        $msg[4] = 'Post successfully Published';
        return response()->success($msg[$post_s]);
    }

    public function getLatestTrends()
    {
        $tweets = SocialconnectHelper::getTwitterTrends();
        return response()->success($tweets);
    }

    public function getHashTags()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $hastags = SocialconnectHelper::getHashTags();
        $stored_hashtags = SocialconnectHelper::getStoredHashTags($company_id);
        if ($stored_hashtags != false) {
            foreach ($stored_hashtags as $key => $stored_tag) {
                array_push($hastags, $stored_tag);
            }
        }
        return response()->success(compact('hastags'));
    }

    public function getTwitterSearch()
    {
        $q = "#yoga";
        SocialconnectHelper::getTwitterSearch($q);
    }
    public function postAddHashTag()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $tag = Input::get('tag');
        $tag_info = SocialconnectHelper::getHashTagStats($tag);
        $tag_exits = SocialconnectHelper::checkTagExists($tag, $company_id);
        if ($tag_exits != true && $tag_info != false) {
            SocialconnectHelper::storeHashTag($company_id, $tag_info);
            return response()->success($tag_info);
        }
        $tag_info = array();
        return response()->success($tag_info);
    }

    /* For Generate content */

    public function postLatestFeeds()
    {
        $query = Input::get('keyword');
        $input = Input::get();
        if (!isset($input['query_type'])) {
            return 'Please enter Query type';
        }
        $query_type = Input::get('query_type');
        $query = urlencode($query);
        if ($query_type == 'news') {
            $output = SocialconnectHelper::getLatestNews($query);
        } elseif ($query_type == 'images') {
            $output = SocialconnectHelper::getLatesImages($query);
        } elseif ($query_type == 'video') {
            $output = SocialconnectHelper::searchVideoListByKeyword($query);
        }
        return response()->success($output);
    }

    public function getWebsiteMeta()
    {
        $webiste_url = Input::get('url');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $webiste_url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $data = curl_exec($ch);
        curl_close($ch);
        // return $data;
        $html = $data;
        preg_match('/<title>(.+)<\/title>/', $html, $matches);
        $title = $matches[1];
        return response()->success($title);
    }

    public function deleteCampaign($del_id)
    {
        $post = SiCampaigns::find($del_id);
        $post->delete();
        return response()->success("Deleted successfully");
    }

    public function getLatetsGoogleTrends($location_id)
    {
        $feed_url = "https://trends.google.com/trends/hottrends/atom/feed?pn=" . $location_id;
        $content = file_get_contents($feed_url);
        $content = str_replace(":", '', $content);
        $xml = simplexml_load_string($content, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json, true);
        $trends = $array['channel']['item'];
        $rank = 1;
        foreach ($trends as $key => $value) {
            $news_title = null;
            $desc = null;

            /* News Title */
            if (isset($value['htnews_item']['htnews_item_title'])) {
                $news_title = strip_tags($value['htnews_item']['htnews_item_title']);
            } elseif (isset($value['htnews_item'][0]['htnews_item_title'])) {
                $news_title = strip_tags($value['htnews_item'][0]['htnews_item_title']);
            }

            /* News Description */
            if (isset($value['htnews_item']['htnews_item_snippet'])) {
                $desc = strip_tags($value['htnews_item']['htnews_item_snippet']);
            } elseif (isset($value['htnews_item'][0]['htnews_item_snippet'])) {
                $desc = strip_tags($value['htnews_item'][0]['htnews_item_snippet']);
            }

            $out[] = array(
                'trend_rank' => $rank,
                'title' => $value['title'],
                'search_rate' => $value['htapprox_traffic'],
                'image' => $content = str_replace("images?q=tbn", 'images?q=tbn:', $value['htpicture']),
                'search_rate' => $value['htapprox_traffic'],
                'news_title' => $news_title,
                'news_description' => $desc,
                'link' => $value['link'],
            );
            $rank++;
        }
        return response()->success($out);
    }

    /* Function for get Facebook Impressions */

    public function getFbInsights()
    {
        $post_id = Input::get('post_id');
        $insights = SocialconnectHelper::facebookPostInsights($post_id);
        return response()->success($insights);
    }

    public function postPostQueueSettings()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $days = Input::get('selected_days');
        $times = Input::get('times');
        SocialconnectHelper::saveQueueSchedule($company_id, $days, $times);
        return response()->success('success');
    }

    public function getPostQueueSchedule()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $response = SocialconnectHelper::getCompanyPostQueueSchedule($company_id);
        return response()->success($response);
    }

    public static function PublishQueuePost()
    {
        $last_fetch_time = CronHelper::getRecentExecutedTime('si_post_queued_posts');
        $cron_id = CronHelper::createCronRecord('si_post_queued_posts');
        SocialconnectHelper::publishQueuePosts();
        /* Update  Cron Records */
        CronHelper::udateCronEndTime($cron_id);
        return response()->success('queued_campign_sent');
    }

    public function postConnectSocialNetwork()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        if (Input::get('network_details')) {
            $network_data = Input::get('network_details');
            SocialconnectHelper::deleteCompanyNetworkProfile($company_id, $network_data['network_name']);
            SocialconnectHelper::add_network($network_data['network_name'], $network_data['net_id'], $network_data['token'], $company_id, '', $network_data['user_name'], '');
            return response()->success(array('status' => 'success'));
        }
        return response()->failed(array('Message' => 'unknown'));
    }

    public function getSocialCodes()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $social_code = CompanyHelper::getSocialCodes($company_id);
        return response()->success(json_decode($social_code));
    }

    public function getNetworkError()
    {
        $Input = Input::get();
        if (isset($Input['campaign_id']) && isset($Input['error_network'])) {
            $campaign_id = $Input['campaign_id'];
            $ntwrk_id = $Input['error_network'];
            $error = SocialconnectHelper::GetErrorNetwork($campaign_id, $ntwrk_id);
            return response()->success(compact('error'));
        }
        return response()->error('required keys not found');
    }
}
