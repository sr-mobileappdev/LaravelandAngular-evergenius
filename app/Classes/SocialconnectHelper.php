<?php
namespace App\Classes;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Classes\AppOptionsHelper;
use App\Classes\CompanyHelper;
use App\Classes\CompanySettingsHelper;
use App\Classes\NotificationHelper;
use App\Hashtag;
use App\SiCampaigns;
use App\SiNetwork;
use App\SiPostError;
use App\SiPosts;
use App\SiPostsMeta;
use App\SiQueueSchedule;
use Auth;
use Carbon\Carbon;
use Curl;
use dateTime;
use Illuminate\Support\Facades\Log;
use Request;
use Session;
use Twilio\Rest\Client;

class SocialconnectHelper
{
    public static function isFbSettingsExists($fb_app_id, $fb_app_secret)
    {
        if ($fb_app_id != false && $fb_app_id != false) {
            return true;
        }
        return false;
    }

    public static function checkIsNetworkConnected($company_id, $network)
    {
        $count_SI_networks = SiNetwork::where('company_id', $company_id)->where('network_name', $network)->count();
        if ($count_SI_networks > 0) {
            return true;
        }
        return false;
    }
    public static function getNetworkDetails($company_id, $network)
    {
        $count_SI_networks = SiNetwork::where('company_id', $company_id)->where('network_name', $network)->first();
        if (count($count_SI_networks) > 0) {
            return $count_SI_networks;
        }
        return false;
    }

    public static function getConnectUrl($network)
    {
        if ($network == 'facebook') {
            $link = SocialconnectHelper::getFacebookConnectUrl();
        } elseif ($network == 'facebook_pages') {
            $link = SocialconnectHelper::getFacebookPageConnectUrl();
        } elseif ($network == 'facebook_groups') {
            $link = SocialconnectHelper::getFacebookGroupsConnectUrl();
        } elseif ($network == 'twitter') {
            $link = SocialconnectHelper::getTwitterConnectUrl();
        } elseif ($network == 'google_plus') {
            $link = SocialconnectHelper::getGoogleConnectUrl();
        } elseif ($network == 'instagram') {
            $link = url('/') . "/social/user/connect/instagram";
        } elseif ($network == 'linkedin') {
            $link = SocialconnectHelper::getlinkedInConnectUrl();
        } else {
            $link = "#";
        }
        return $link;
    }

    /* Get facebook network URL */

    public static function getFacebookConnectUrl()
    {
        $fb_app_id = AppOptionsHelper::getOptionValue('facebook_app_id');
        $fb_app_secret = AppOptionsHelper::getOptionValue('facebook_app_secret');
        $fb = SocialconnectHelper::config_fb_profile($fb_app_id, $fb_app_secret);
        $helper = $fb->getRedirectLoginHelper();
        $permissions = ['email', 'publish_actions','public_profile'];
        $loginUrl = $helper->getLoginUrl(url('/') . '/api/social/callback/facebook', $permissions);
        return $loginUrl;
    }
    /* Get facebook Pages network URL */
    public static function getFacebookPageConnectUrl()
    {
        $fb_app_id = AppOptionsHelper::getOptionValue('facebook_app_id');
        $fb_app_secret = AppOptionsHelper::getOptionValue('facebook_app_secret');
        $fb = SocialconnectHelper::config_fb_profile($fb_app_id, $fb_app_secret);
        $helper = $fb->getRedirectLoginHelper();
        $permissions = ['manage_pages', 'publish_pages'];
        $loginUrl = $helper->getLoginUrl(url('/') . '/api/social/callback/facebook_pages', $permissions);
        return $loginUrl;
    }

    /* Get facebook Groups network URL */
    public static function getFacebookGroupsConnectUrl()
    {
        $fb_app_id = AppOptionsHelper::getOptionValue('facebook_app_id');
        $fb_app_secret = AppOptionsHelper::getOptionValue('facebook_app_secret');
        $fb = SocialconnectHelper::config_fb_profile($fb_app_id, $fb_app_secret);
        $helper = $fb->getRedirectLoginHelper();
        $permissions = ['publish_actions', 'user_managed_groups'];
        $loginUrl = $helper->getLoginUrl(url('/') . '/api/social/callback/facebook_groups', $permissions);
        return $loginUrl;
    }

    public static function config_fb_profile($fb_app_id, $fb_app_secret, $token = null)
    {
        if ($token == null) {
            $token = '{access-token}';
        }
        try {
            $fb = new \Facebook\Facebook(
                [
                    'app_id' => $fb_app_id,
                    'app_secret' => $fb_app_secret,
                    'default_graph_version' => 'v2.10',
                    'default_access_token' => $token,
                ]
            );
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error

            get_instance()->session->set_flashdata('error', 'Graph returned an error: ' . $e->getMessage());
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            get_instance()->session->set_flashdata('error', 'Facebook SDK returned an error: ' . $e->getMessage());
        }
        return $fb;
    }

    public static function saveFacebokCallback($access_token)
    {
        $fb_app_id = AppOptionsHelper::getOptionValue('facebook_app_id');
        $fb_app_secret = AppOptionsHelper::getOptionValue('facebook_app_secret');
        $fb = SocialconnectHelper::config_fb_profile($fb_app_id, $fb_app_secret);

        $helper = $fb->getRedirectLoginHelper();
        if (isset($_GET['state'])) {
            $helper->getPersistentDataHandler()->set('state', $_GET['state']);
        }

        $access_token = $helper->getAccessToken();
        $access_token = (array) $access_token;
        $access_token = array_values($access_token);
        if (isset($access_token[0])) {
            $getUserdata = json_decode(file_get_contents('https://graph.facebook.com/me?fields=id,name,email&access_token=' . $access_token[0]));
            if (property_exists($getUserdata, 'id')) {
                /* Current Compay ID */
                $a = (array) $access_token[1];
                if (array_key_exists('date', $a)) {
                    $expires = $a['date'];
                } else {
                    $expires = '';
                }
                if (!isset($_SESSION['company_id'])) {
                    return false;
                }
                $company_id = $_SESSION['company_id']; // Get From sesstion
                if (SocialconnectHelper::checkIsNetworkConnected($company_id, 'facebook')) {
                } else {
                    SocialconnectHelper::add_network('facebook', $getUserdata->id, $access_token[0], $company_id, $expires, $getUserdata->name, 'http://graph.facebook.com/' . $getUserdata->id . '/picture?type=square');
                    return true;
                }
            }
        } else {
            return false;
        }
    }

    public static function saveFacebokPageCallback($access_token)
    {
        $fb_app_id = AppOptionsHelper::getOptionValue('facebook_app_id');
        $fb_app_secret = AppOptionsHelper::getOptionValue('facebook_app_secret');
        $fb = SocialconnectHelper::config_fb_profile($fb_app_id, $fb_app_secret);
        $helper = $fb->getRedirectLoginHelper();
        if (isset($_GET['state'])) {
            $helper->getPersistentDataHandler()->set('state', $_GET['state']);
        }
        $access_token = $helper->getAccessToken();
        $access_token = (array) $access_token;
        $access_token = array_values($access_token);
        if (!isset($access_token[0])) {
            return false;
        }
        $response = json_decode(file_get_contents('https://graph.facebook.com/me/accounts?limit=500&access_token=' . $access_token[0]));

        if (count(@$response->data) == 1 && @$response->data[0]->access_token) {
            if (property_exists($response->data[0], 'id')) {
                $company_id = $_SESSION['company_id'];
                for ($y = 0; $y < count($response->data); $y++) {
                    if (!SocialconnectHelper::checkIsNetworkConnected($company_id, 'facebook_pages')) {
                        SocialconnectHelper::add_network('facebook_pages', $response->data[$y]->id, $access_token[0], $company_id, '', $response->data[$y]->name, '');
                    }
                }
            }
            return true;
        } elseif (count(@$response->data) > 1) {
            $multiple_pages_data = array();
            $token = @$access_token[0];
            foreach (@$response->data as $value) {
                $multiple_pages_data[] = array(
                    'name' => $value->name,
                    'id' => $value->id,
                    'access_token' => $token,
                );
            }

            $pages_data = json_encode($multiple_pages_data);
            CompanyHelper::updateSocialCodes($_SESSION['company_id'], $pages_data);
            return array('multiple_pages' => true, 'pages_data' => $pages_data);
        } else {
            return false;
        }
    }

    public static function saveFacebokGroupsCallback($access_token)
    {
        $fb_app_id = AppOptionsHelper::getOptionValue('facebook_app_id');
        $fb_app_secret = AppOptionsHelper::getOptionValue('facebook_app_secret');
        $fb = SocialconnectHelper::config_fb_profile($fb_app_id, $fb_app_secret);
        $helper = $fb->getRedirectLoginHelper();
        if (isset($_GET['state'])) {
            $helper->getPersistentDataHandler()->set('state', $_GET['state']);
        }
        $access_token = $helper->getAccessToken();

        $access_token = (array) $access_token;

        $access_token = array_values($access_token);
        if (!isset($access_token[0])) {
            return false;
        }
        //$getUserdata = json_decode(file_get_contents('https://graph.facebook.com/me?fields=id,name,email&access_token=' . $access_token[0]));
        return false;

       // $response = json_decode(file_get_contents('https://graph.facebook.com/v2.10/'.$getUserdata->id.'/groups?token=' . $access_token[0]));
        if (count(@$response->data) == 1 && @$access_token[0]) {
            if (@property_exists($response->data[0], 'id')) {
                $company_id = $_SESSION['company_id'];
                for ($y = 0; $y < count($response->data); $y++) {
                    if (!SocialconnectHelper::checkIsNetworkConnected($company_id, 'facebook_groups')) {
                        SocialconnectHelper::add_network('facebook_groups', $response->data[$y]->id, $access_token[0], $company_id, '', $response->data[$y]->name, '');
                    }
                }
                // $this->CI->session->set_flashdata('deleted', display_mess(81));
            }
        } elseif (count(@$response->data) > 1 && @$access_token[0]) {
            $multiple_groups_data = array();
            $token = @$access_token[0];
            foreach (@$response->data as $key => $value) {
                $multiple_groups_data[] = array(
                    'name' => $value->name,
                    'id' => $value->id,
                    'access_token' => $token,
                );
            }

            $groups_data = json_encode($multiple_groups_data);
            CompanyHelper::updateSocialCodes($_SESSION['company_id'], $groups_data);
            return array('multiple_groups' => true, 'groups_data' => $groups_data);
        } else {
            return false;
        }

        return true;
    }

    public static function add_network($name, $net_id, $token, $company_id, $expires, $user_name, $user_avatar, $secret = null)
    {

        /* Del If Existing */
        $del_where = array('network_name' => strtolower($name), 'net_id' => $net_id, 'company_id' => $company_id);
        SiNetwork::where($del_where)->delete();

        $secret = ($secret == null) ? ' ' : $secret;
        $data_save = array('network_name' => strtolower($name), 'net_id' => $net_id, 'company_id' => $company_id, 'user_name' => $user_name, 'user_avatar' => $user_avatar, 'date' => date('Y-m-d h:i:s'), 'expires' => $expires, 'token' => $token, 'secret' => $secret);

        SiNetwork::insert($data_save);
    }

    /********************* Twitter Application Configure *********************/
    public static function config_twitter_app()
    {
        $twitter_app_id = AppOptionsHelper::getOptionValue('twitter_app_id');
        $twitter_app_secret = AppOptionsHelper::getOptionValue('twitter_app_secret');
        if ($twitter_app_id != false && $twitter_app_secret != false) {
            return new TwitterOAuth($twitter_app_id, $twitter_app_secret);
        }
        return false;
    }

    /* Get Twitter Network URL */
    public static function getTwitterConnectUrl()
    {
        $connection = SocialconnectHelper::config_twitter_app();

        $request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => url('/') . '/api/social/callback/twitter'));

        $_SESSION['oauth_token'] = $request_token['oauth_token'];
        $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
        $url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
        return $url;
    }

    public static function saveTwitterCallback($access_token)
    {
        $twitter_app_id = AppOptionsHelper::getOptionValue('twitter_app_id');
        $twitter_app_secret = AppOptionsHelper::getOptionValue('twitter_app_secret');
        if ($twitter_app_id != false && $twitter_app_secret != false) {
            $twitterOauth = new TwitterOAuth($twitter_app_id, $twitter_app_secret, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
            $twToken = $twitterOauth->oauth('oauth/access_token', array('oauth_verifier' => $_GET['oauth_verifier']));
            $newTwitterOauth = new TwitterOAuth($twitter_app_id, $twitter_app_secret, $twToken['oauth_token'], $twToken['oauth_token_secret']);
            $response = (array) $newTwitterOauth->get('account/verify_credentials');

            if ($twToken['oauth_token']) {
                $company_id = $_SESSION['company_id'];
                if (SocialconnectHelper::checkIsNetworkConnected($company_id, 'twitter')) {
                    //$this->CI->session->set_flashdata('deleted', display_mess(79, 'twitter'));
                } else {
                    SocialconnectHelper::add_network('twitter', @$response['id'], $twToken['oauth_token'], $company_id, '', @$response['name'], @$response['profile_image_url'], $twToken['oauth_token_secret']);
                }
            } else {
                return false;
            }
        }
        return true;
    }

    /********************* Google Application Configure *********************/
    public static function config_google_app()
    {
        $g_app_name = AppOptionsHelper::getOptionValue('google_plus_application_name');
        $g_api_key = AppOptionsHelper::getOptionValue('google_plus_api_key');
        $g_client_secret = AppOptionsHelper::getOptionValue('google_plus_client_secret');
        $g_client_id = AppOptionsHelper::getOptionValue('google_plus_client_id');

        $scriptUri = url('/') . "/api/social/callback/google_plus";

        if ($g_app_name != false && $g_api_key != false && $g_client_secret != false && $g_client_id != false) {
            $connection = new \Google_Client();
            $connection->setAccessType('offline');
            $connection->setApplicationName($g_app_name);
            $connection->setClientId($g_client_id);
            $connection->setClientSecret($g_client_secret);
            $connection->setRedirectUri($scriptUri);
            $connection->setDeveloperKey($g_api_key);
            $connection->setApprovalPrompt('force');
            $connection->setScopes(array(
                "https://www.googleapis.com/auth/plus.me https://www.googleapis.com/auth/plus.stream.read https://www.googleapis.com/auth/plus.stream.write https://www.googleapis.com/auth/userinfo.profile",
            ));
            return $connection;
        }
        return false;
    }

    /* Get Google Network URL */
    public static function getGoogleConnectUrl()
    {
        $google_app = SocialconnectHelper::config_google_app();
        $authUrl = $google_app->createAuthUrl();
        return $authUrl;
    }

    public static function saveGoogleCallback($token)
    {
        $google_app = SocialconnectHelper::config_google_app();
        if (isset($_GET["code"])) {
            $google_app->authenticate($_GET["code"]);
            $token = $google_app->getAccessToken();
            $google_app->setAccessToken($token);
            $token = (object) $token;
            if (@$token->access_token) {
                $refresh = @$token->refresh_token;
                $token = @$token->access_token;
                $expires_in = '';
                $curl = curl_init();
                // Set some options - we are passing in a useragent too here
                curl_setopt_array(
                    $curl,
                    array(
                        CURLOPT_RETURNTRANSFER => 1,
                        CURLOPT_URL => 'https://www.googleapis.com/oauth2/v3/userinfo?access_token=' . $token,
                        CURLOPT_HEADER => false,
                    )
                );
                // Send the request & save response to $resp
                $udata = curl_exec($curl);
                // Close request to clear up some resources
                curl_close($curl);
                if ($udata) {
                    $udecode = json_decode($udata);
                    if (@$udecode->sub && isset($_SESSION['company_id'])) {
                        $company_id = $_SESSION['company_id'];
                        if (!SocialconnectHelper::checkIsNetworkConnected($company_id, 'google_plus')) {
                            SocialconnectHelper::add_network('google_plus', $udecode->sub, $token, $company_id, $expires_in, $udecode->name, $udecode->picture, $refresh);
                        }
                    }
                }
            }
        }
    }

    /********************* LinkedIn Application Configure *********************/

    public static function config_linkedin_app()
    {
        $linked_in_C_id = AppOptionsHelper::getOptionValue('linkedin_client_id');
        $linked_in_C_sec = AppOptionsHelper::getOptionValue('linkedin_client_secret');
        if ($linked_in_C_id != false && $linked_in_C_sec != false) {
            $scriptUri = url('/') . "/api/social/callback/linkedin";
            try {
                return new \LinkedIn\LinkedIn(
                    [
                        'api_key' => $linked_in_C_id,
                        'api_secret' => $linked_in_C_sec,
                        'callback_url' => $scriptUri,
                    ]
                );
            } catch (Exception $e) {
                $ex = $e->getMessage();
                return false;
            }
        }
        return false;
    }

    public static function getlinkedInConnectUrl()
    {
        $linkedin_app = SocialconnectHelper::config_linkedin_app();
        $url = $linkedin_app->getLoginUrl(array('r_basicprofile', 'r_emailaddress', 'w_share'));
        return $url;
    }
    public static function saveLinkedInCallback($token)
    {
        $linkedin_app = SocialconnectHelper::config_linkedin_app();
        if (isset($_GET['code'])) {
            $token = $linkedin_app->getAccessToken($_GET['code']);
            if ($token) {
                $token_expires = $linkedin_app->getAccessTokenExpiration();
                $linkedin_app->setAccessToken($token);
                $udata = $linkedin_app->get('/people/~:(id,first-name,lastName,email-address,headline,positions:(title,company:(name,id)))');
                if ($udata) {
                    $name = @$udata['firstName'] . ' ' . @$udata['lastName'];
                    $id = @$udata['id'];
                    $company_id = $_SESSION['company_id'];

                    $expires = date('Y-m-d H:i:s', time() + $token_expires);
                    if (!SocialconnectHelper::checkIsNetworkConnected($company_id, 'linkedin')) {
                        SocialconnectHelper::add_network('linkedin', $id, $token, $company_id, $expires, $name, '', '');
                    }
                }
            }
        }
    }

    /* ***************** Store posts which send ***************** */

    public static function store_posts($netw, $company_id, $body, $title, $url, $img, $video, $network_id, $network_name, $campign_id, $post_status, $si_post_id = null)
    {
        SocialconnectHelper::deletePostSI($campign_id, $netw);
        $current_time = new dateTime();
        $post = new SiPosts;
        $post->company_id = $company_id;
        $post->body = $body;
        $post->network = $netw;
        $post->campaign_id = $campign_id;
        $post->status = $post_status;
        $post->title = $title;
        $post->url = $url;
        $post->img = $img;
        $post->video = $video;
        $post->created_at = $current_time;
        $post->ip_address = Request::ip();
        $post->fb_net_post_id = $si_post_id;
        $post->save();
        $post_id = $post->id;
        SocialconnectHelper::store_post_meta($post_id, $network_id, $network_name, $post_status);
    }

    public static function deletePostSI($campign_id, $network)
    {
        SiPosts::where(['network' => $network, 'campaign_id' => $campign_id])
            ->delete();
    }

    public static function store_post_meta($post_id, $network_id, $network_name, $post_status)
    {
        $current_time = new dateTime();
        $post_meta = new SiPostsMeta;
        $post_meta->post_id = $post_id;
        $post_meta->network_id = $network_id;
        $post_meta->network_name = $network_name;
        $post_meta->network_name = $network_name;
        //update time when status is published
        if ($post_status == 1 || $post_status == 2) {
            $post_meta->sent_time = $current_time;
        }
        $post_meta->save();
        return $post_meta->id;
    }

    public static function log_error_post($campign_id, $post_id = null, $network_id, $message)
    {
        if ($post_id === null) {
            $p_id = SocialconnectHelper::getPostIdCampign($campign_id, $network_id);
            if ($p_id != false) {
                $post_id = $post_id;
            }
        }
        $error_post = new SiPostError();
        $error_post->campign_id = $campign_id;
        $error_post->post_id = $post_id;
        $error_post->network_id = $network_id;
        $error_post->message = $message;
        $error_post->save();

        /* Add Meta in netwrok */
        return $error_post->id;
    }

    public static function post_social_data($network, $post_data, $campign_id, $post_status, $company_id = null, $publish_draft = null)
    {
        if ($company_id == null) {
            $user = Auth::user();
            $company_id = $user->company_id;
        }

        if ($network == 'facebook') {
            SocialconnectHelper::publish_facebook($post_data, $company_id, $campign_id, $post_status, $publish_draft);
        }
        if ($network == 'facebook_pages') {
            SocialconnectHelper::publish_facebook_pages($post_data, $company_id, $campign_id, $post_status, $publish_draft);
        }
        if ($network == 'facebook_groups') {
            SocialconnectHelper::publish_facebook_groups($post_data, $company_id, $campign_id, $post_status, $publish_draft);
        }
        if ($network == 'twitter') {
            SocialconnectHelper::publish_twitter_posts($post_data, $company_id, $campign_id, $post_status, $publish_draft);
        }
        if ($network == 'google_plus') {
            SocialconnectHelper::publish_google_p_posts($post_data, $company_id, $campign_id, $post_status, $publish_draft);
        }
        if ($network == 'linkedin') {
            SocialconnectHelper::publish_linkedIn_posts($post_data, $company_id, $campign_id, $post_status, $publish_draft);
        }
        return true;
    }

    /* *************** Publish data on facebook profile *************** */
    public static function publish_facebook($publish_data, $company_id, $campign_id, $post_status, $publish_draft = null)
    {
        $network_details = false;
        $network_d = SocialconnectHelper::getNetworkDetails($company_id, 'facebook');
        if ($network_d != false) {
            $network_details = $network_d->toArray();
        }
        $fb_app_id = AppOptionsHelper::getOptionValue('facebook_app_id');
        $fb_app_secret = AppOptionsHelper::getOptionValue('facebook_app_secret');
        $body = null;
        $title = null;
        $url = null;
        $img = null;
        $video = null;
        if ($post_status == 1 && $network_details != false) {
            try {
                $fb = SocialconnectHelper::config_fb_profile($fb_app_id, $fb_app_secret);
                $post = $publish_data['title'];
                if (@$publish_data['title']) {
                    $body = $post;
                }
                if (isset($publish_data['video_url'])) {
                    $linkData = ['link' => $publish_data['video_url'], 'message' => $post];
                    $post = $fb->post('/me/feed', $linkData, $network_details['token']);
                    $video = $publish_data['video_url'];
                } elseif (isset($publish_data['image_url'])) {
                    $linkData = ['url' => $publish_data['image_url'], 'message' => $post];
                    $post = $fb->post('/me/photos', $linkData, $network_details['token']);
                    $img = $publish_data['image_url'];
                } elseif (isset($publish_data['link'])) {
                    $short_url = SocialconnectHelper::getShortUlr($publish_data['link']);
                    $linkData = ['link' => $short_url, 'message' => $post];
                    $post = $fb->post('/me/feed', $linkData, $network_details['token']);
                    $url = $short_url;
                } else {
                    $linkData = ['message' => $post];
                    $post = $fb->post('/me/feed', $linkData, $network_details['token']);
                }
                if ($post->getDecodedBody()) {
                    $mo = $post->getDecodedBody();
                    if (@$mo['id'] && @$args['id']) {
                        sami($mo['id'], $args['id'], $args['account'], 'facebook', $user_id);
                    }
                    if ($publish_draft != null && $publish_draft != false) {
                        SocialconnectHelper::update_post('facebook', $network_details['company_id'], $body, $title, $url, $img, $video, $campign_id, 1);
                        return true;
                    }
                    SocialconnectHelper::store_posts('facebook', $network_details['company_id'], $body, $title, $url, $img, $video, $network_details['id'], 'facebook', $campign_id, $post_status);
                    return true;
                } else {
                    return false;
                }
            } catch (\Facebook\Exceptions\FacebookResponseException $e) {
                $message = $e->getMessage();
                SocialconnectHelper::log_error_post($campign_id, null, 'facebook', $message);
                $previousException = $e->getPrevious();
                $logFile = 'errors.log';
                \Log::useDailyFiles(storage_path() . '/logs/' . $logFile);
                \Log::emergency("Company ID:" . $company_id . ", campign_id:" . $campign_id . ", Network:facebook, Message-" . $message);
                return false;
            }
        } else {
            $body = $publish_data['title'];
            if (isset($publish_data['image_url'])) {
                $img = $publish_data['image_url'];
            }
            if (isset($publish_data['video_url'])) {
                $video = $publish_data['video_url'];
            }
            if (isset($publish_data['link'])) {
                $url = $publish_data['link'];
            }

            SocialconnectHelper::store_posts('facebook', $network_details['company_id'], $body, $title, $url, $img, $video, $network_details['id'], 'facebook', $campign_id, $post_status);
        }
    }

    public static function publish_facebook_pages($publish_data, $company_id, $campign_id, $post_status, $publish_draft = null)
    {
        $network_details = false;
        $network_d = SocialconnectHelper::getNetworkDetails($company_id, 'facebook_pages');
        if ($network_d != false) {
            $network_details = $network_d->toArray();
        }
        $fb_app_id = AppOptionsHelper::getOptionValue('facebook_app_id');
        $fb_app_secret = AppOptionsHelper::getOptionValue('facebook_app_secret');
        $fb = SocialconnectHelper::config_fb_profile($fb_app_id, $fb_app_secret);
        $body = null;
        $title = null;
        $url = null;
        $img = null;
        $video = null;
        $post = $publish_data['title'];
        if (@$publish_data['title']) {
            $body = $post;
        }
        if ($post_status == 1 && $network_details != false) {
            try {
                try {
                    $response = json_decode(file_get_contents('https://graph.facebook.com/me/accounts?limit=500&access_token=' . $network_details['token']));
                } catch (\ErrorException $e) {
                    $message = $e->getMessage();
                    SocialconnectHelper::log_error_post($campign_id, null, 'facebook_pages', $message);
                    $previousException = $e->getPrevious();
                    $logFile = 'errors.log';
                    \Log::useDailyFiles(storage_path() . '/logs/' . $logFile);
                    \Log::emergency("Company ID:" . $company_id . ", campign_id:" . $campign_id . ", Network:facebook_pages, Message-" . $message);

                    return false;
                }

                $token = '';
                foreach ($response->data as $page) {
                    if ($network_details['net_id'] == $page->id) {
                        $token = $page->access_token;
                    }
                }
                if ($token) {
                    $fb->setDefaultAccessToken($token);

                    if (isset($publish_data['video_url'])) {
                        $linkData = ['link' => $publish_data['video_url'], 'message' => $post];
                        $post = $fb->post('/' . $network_details['net_id'] . '/feed', $linkData, $token);
                        $video = $publish_data['video_url'];
                    } elseif (isset($publish_data['image_url'])) {
                        $linkData = ['url' => $publish_data['image_url'], 'message' => $post];
                        $post = $fb->post('/' . $network_details['net_id'] . '/photos', $linkData, $token);
                        $img = $publish_data['image_url'];
                    } elseif (isset($publish_data['video_url'])) {
                        $linkData = ['link' => $publish_data['video_url'], 'message' => $post];
                        $post = $fb->post('/' . $network_details['net_id'] . '/feed', $linkData, $token);
                        $video = $publish_data['video_url'];
                    } elseif (isset($publish_data['link'])) {
                        $short_url = SocialconnectHelper::getShortUlr($publish_data['link']);
                        $linkData = ['link' => $short_url, 'message' => $post];
                        $post = $fb->post('/' . $network_details['net_id'] . '/feed', $linkData, $token);
                        $url = $short_url;
                    } else {
                        $linkData = ['message' => $post];
                        $post = $fb->post('/' . $network_details['net_id'] . '/feed', $linkData, $token);
                    }
                    $post_decode_body = $post->getDecodedBody();
                    if ($post->getDecodedBody()) {
                        $mo = $post->getDecodedBody();
                        $response_post_id = $mo['id'];
                        if (@$mo['id'] && @$args['id']) {
                            sami($mo['id'], $args['id'], $args['account'], 'facebook_pages', $user_id);
                        }
                        /* *********** if publish draft posts *********** */
                        if ($publish_draft != null && $publish_draft != false) {
                            SocialconnectHelper::update_post('facebook', $network_details['company_id'], $body, $title, $url, $img, $video, $campign_id, 1);
                            return true;
                        }
                        SocialconnectHelper::store_posts('facebook_pages', $network_details['company_id'], $body, $title, $url, $img, $video, $network_details['id'], 'facebook_pages', $campign_id, $post_status, $response_post_id);
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } catch (\Facebook\Exceptions\FacebookResponseException $e) {
                $message = $e->getMessage();
                SocialconnectHelper::log_error_post($campign_id, null, 'facebook_pages', $message);
                $previousException = $e->getPrevious();
                $logFile = 'errors.log';
                \Log::useDailyFiles(storage_path() . '/logs/' . $logFile);
                \Log::emergency("Company ID:" . $company_id . ", campign_id:" . $campign_id . ", Network:facebook_pages, Message-" . $message);

                return false;
            }
        } else {
            $body = $publish_data['title'];
            if (isset($publish_data['image_url'])) {
                $img = $publish_data['image_url'];
            }
            if (isset($publish_data['video_url'])) {
                $video = $publish_data['video_url'];
            }
            if (isset($publish_data['link'])) {
                $url = $publish_data['link'];
            }
            SocialconnectHelper::store_posts('facebook_pages', $network_details['company_id'], $body, $title, $url, $img, $video, $network_details['id'], 'facebook_pages', $campign_id, $post_status);
        }
    }

    public static function publish_facebook_groups($publish_data, $company_id, $campign_id, $post_status, $publish_draft = null)
    {
        $network_details = false;
        $network_d = SocialconnectHelper::getNetworkDetails($company_id, 'facebook_groups');
        if ($network_d != false) {
            $network_details = $network_d->toArray();
        }

        $fb_app_id = AppOptionsHelper::getOptionValue('facebook_app_id');
        $fb_app_secret = AppOptionsHelper::getOptionValue('facebook_app_secret');
        $fb = SocialconnectHelper::config_fb_profile($fb_app_id, $fb_app_secret);
        $body = null;
        $title = null;
        $url = null;
        $img = null;
        $video = null;
        $post = $publish_data['title'];
        if ($post_status == 1 && $network_details != false) {
            try {
                $fb->setDefaultAccessToken($network_details['token']);
                $token = $network_details['token'];

                $post = $publish_data['title'];
                if (@$publish_data['title']) {
                    $body = $post;
                }
                if (isset($publish_data['video_url'])) {
                    $linkData = ['link' => $publish_data['video_url'], 'message' => $post];
                    $post = $fb->post('/' . $network_details['net_id'] . '/feed', $linkData, $token);
                    $video = $publish_data['video_url'];
                } elseif (isset($publish_data['image_url'])) {
                    $linkData = ['url' => $publish_data['image_url'], 'message' => $post];
                    $post = $fb->post('/' . $network_details['net_id'] . '/photos', $linkData, $token);
                    $img = $publish_data['image_url'];
                } elseif (isset($publish_data['link'])) {
                    $short_url = SocialconnectHelper::getShortUlr($publish_data['link']);
                    $linkData = ['link' => $short_url, 'message' => $post];
                    $post = $fb->post('/' . $network_details['net_id'] . '/feed', $linkData, $token);
                    $url = $short_url;
                } else {
                    $linkData = ['message' => $post];
                    $post = $fb->post('/' . $network_details['net_id'] . '/feed', $linkData, $token);
                }

                if ($post->getDecodedBody()) {
                    $mo = $post->getDecodedBody();
                    if (@$mo['id'] && @$args['id']) {
                        sami($mo['id'], $args['id'], $args['account'], 'facebook_groups', $user_id);
                    }
                    /* *********** if publish draft posts *********** */
                    if ($publish_draft != null && $publish_draft != false) {
                        SocialconnectHelper::update_post('facebook_groups', $network_details['company_id'], $body, $title, $url, $img, $video, $campign_id, 1);
                        return true;
                    }

                    SocialconnectHelper::store_posts('facebook_groups', $network_details['company_id'], $body, $title, $url, $img, $video, $network_details['id'], 'facebook_groups', $campign_id, $post_status);
                    return true;
                } else {
                    return false;
                }
            } catch (\Facebook\Exceptions\FacebookResponseException $e) {
                $message = $e->getMessage();
                SocialconnectHelper::log_error_post($campign_id, null, 'facebook_groups', $message);
                $previousException = $e->getPrevious();
                $logFile = 'errors.log';
                \Log::useDailyFiles(storage_path() . '/logs/' . $logFile);
                \Log::emergency("Company ID:" . $company_id . ", campign_id:" . $campign_id . ", Network:facebook_groups, Message-" . $message);
                return false;
            }
        } else {
            $body = $publish_data['title'];
            if (isset($publish_data['image_url'])) {
                $img = $publish_data['image_url'];
            }
            if (isset($publish_data['video_url'])) {
                $video = $publish_data['video_url'];
            }
            if (isset($publish_data['link'])) {
                $url = $publish_data['link'];
            }

            SocialconnectHelper::store_posts('facebook_groups', $network_details['company_id'], $body, $title, $url, $img, $video, $network_details['id'], 'facebook_groups', $campign_id, $post_status);
        }
    }
    public static function publish_twitter_posts($publish_data, $company_id, $campign_id, $post_status, $publish_draft = null)
    {
        $network_details = false;
        $network_d = SocialconnectHelper::getNetworkDetails($company_id, 'twitter');
        if ($network_d != false) {
            $network_details = $network_d->toArray();
        }
        $twitter_app_id = AppOptionsHelper::getOptionValue('twitter_app_id');
        $twitter_app_secret = AppOptionsHelper::getOptionValue('twitter_app_secret');
        $body = null;
        $title = null;
        $url = null;
        $img = null;
        $video = null;
        $post = $publish_data['title'];
        if ($post_status == 1 && $network_details != false) {
            try {
                $connection = new TwitterOAuth($twitter_app_id, $twitter_app_secret, $network_details['token'], $network_details['secret']);
                $post = $publish_data['title'];
                if (@$publish_data['title']) {
                    $body = $post;
                }

                if (isset($publish_data['image_url'])) {
                    try {
                        $media = $connection->upload('media/upload', array('media' => $publish_data['image_url']));
                        if (isset($media->media_id_string)) {
                            $check = $connection->post('statuses/update', ['status' => mb_substr(rawurldecode($post), 0, 179), 'media_ids' => $media->media_id_string]);
                        }

                        $img = $publish_data['image_url'];
                    } catch (\Exception $e) {
                        $message = $e->getMessage();
                        SocialconnectHelper::log_error_post($campign_id, null, 'twitter', $message);
                        $previousException = $e->getPrevious();
                        $logFile = 'errors.log';
                        \Log::useDailyFiles(storage_path() . '/logs/' . $logFile);
                        \Log::emergency("Company ID:" . $company_id . ", campign_id:" . $campign_id . ", Network:twitter, Message-" . $message);
                        return false;
                    }
                } else {
                    $length = 280;
                    $url = '';

                    if (isset($publish_data['video_url'])) {
                        $length = 279 - strlen($publish_data['video_url']);
                        $url = ' ' . $publish_data['video_url'];
                    }
                    if (isset($publish_data['link'])) {
                        $short_url = SocialconnectHelper::getShortUlr($publish_data['link']);
                        $length = 279 - strlen($short_url);
                        $url = ' ' . $short_url;
                    }

                    $check = $connection->post('statuses/update', ['status' => mb_substr(rawurldecode($post), 0, $length) . $url]);
                }
                if (isset($check)) {
                    /* *********** if publish draft posts *********** */
                    if ($publish_draft != null && $publish_draft != false) {
                        SocialconnectHelper::update_post('twitter', $network_details['company_id'], $body, $title, $url, $img, $video, $campign_id, 1);
                        return true;
                    }

                    SocialconnectHelper::store_posts('twitter', $network_details['company_id'], $body, $title, $url, $img, $video, $network_details['id'], 'twitter', $campign_id, $post_status);
                    return true;
                } else {
                    return false;
                }
            } catch (\Abraham\TwitterOAuth\TwitterOAuthException $e) {
                $message = $e->getMessage();
                SocialconnectHelper::log_error_post($campign_id, null, 'twitter', $message);
                $previousException = $e->getPrevious();
                $logFile = 'errors.log';
                \Log::useDailyFiles(storage_path() . '/logs/' . $logFile);
                \Log::emergency("Company ID:" . $company_id . ", campign_id:" . $campign_id . ", Network:twitter, Message-" . $message);
                return false;
            }
        } else {
            $body = $publish_data['title'];
            if (isset($publish_data['image_url'])) {
                $img = $publish_data['image_url'];
            }
            if (isset($publish_data['video_url'])) {
                $video = $publish_data['video_url'];
            }
            if (isset($publish_data['link'])) {
                $url = $publish_data['link'];
            }

            SocialconnectHelper::store_posts('twitter', $network_details['company_id'], $body, $title, $url, $img, $video, $network_details['id'], 'twitter', $campign_id, $post_status);
        }
    }

    public static function publish_google_p_posts($publish_data, $company_id, $campign_id, $post_status, $publish_draft = null)
    {
        $con = false;
        $network_d = SocialconnectHelper::getNetworkDetails($company_id, 'google_plus');
        if ($network_d != false) {
            $con = $network_d->toArray();
        }

        $google_plus_client_id = AppOptionsHelper::getOptionValue('google_plus_client_id');
        $google_plus_client_secret = AppOptionsHelper::getOptionValue('google_plus_client_secret');
        $google_plus_api_key = AppOptionsHelper::getOptionValue('google_plus_api_key');
        $google_plus_application_name = AppOptionsHelper::getOptionValue('google_plus_application_name');
        $google_con = SocialconnectHelper::config_google_app();
        $body = null;
        $title = null;
        $url = '';
        $img = null;
        $video = null;
        if ($post_status == 1 && $con != false) {
            try {
                $google_con->refreshToken($con['secret']);
                $post = $publish_data['title'];
                if (@$publish_data['title']) {
                    $body = $post;
                }
                if (isset($publish_data["link"])) {
                    $url = $publish_data["link"];
                }

                $token = $google_con->getAccessToken();
                $token = $token['access_token'];
                $headers = array(
                    'Authorization : Bearer ' . $token,
                    'Content-Type : application/json',
                );

                $post_data = array("object" => array("originalContent" => $post . ' ' . $url), "access" => array("items" => array(array("type" => "domain")), "domainRestricted" => true));
                $data_string = json_encode($post_data);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/plusDomains/v1/people/' . $con['net_id'] . '/activities');
                $published = curl_exec($ch);
                curl_close($ch);
                if ($published) {
                    /* *********** if publish draft posts *********** */
                    if ($publish_draft != null && $publish_draft != false) {
                        SocialconnectHelper::update_post('google_plus', $con['company_id'], $body, $title, $url, $img, $video, $campign_id, 1);
                        return true;
                    }

                    SocialconnectHelper::store_posts('google_plus', $con['company_id'], $body, $title, $url, $img, $video, $con['id'], 'google_plus', $campign_id, $post_status);
                    return true;
                } else {
                    return false;
                }
            } catch (Exception $e) {
                return false;
            }
        } else {
            $body = $publish_data['title'];
            if (isset($publish_data['image_url'])) {
                $img = $publish_data['image_url'];
            }
            if (isset($publish_data['video_url'])) {
                $video = $publish_data['video_url'];
            }
            if (isset($publish_data['link'])) {
                $url = $publish_data['link'];
            }
            SocialconnectHelper::store_posts('google_plus', $con['company_id'], $body, $title, $url, $img, $video, $con['id'], 'google_plus', $campign_id, $post_status);
        }
    }

    public static function publish_instagram_posts($publish_data, $company_id, $campign_id, $post_status, $publish_draft = null)
    {
        $con = SocialconnectHelper::getNetworkDetails($company_id, 'instagram')->toArray();
        $body = null;
        $title = null;
        $url = '';
        $img = null;
        $video = null;
        if ($post_status == 1 && $network_details) {
            $im = explode(url('/'), $publish_data['image_url']);
            if (isset($im[1])) {
                $destinationPath = public_path();
                $filename = str_replace(url('/'), $destinationPath, $publish_data['image_url']);

                if (exif_imagetype($filename) != IMAGETYPE_JPEG) {
                    $in = get_files_in($publish_data['image_url']);

                    if ($in) {
                        $filename = $destinationPath . uniqid() . time() . '.jpg';

                        file_put_contents($filename, $in);
                        if (file_exists($filename)) {
                            $file = $filename;
                        } else {
                            return false;
                        }
                    } else {
                        return false;
                    }
                }
                $file = $filename;
            } else {
                $in = get_files_in($publish_data['image_url']);
                if ($in) {
                    $destinationPath = public_path('/images/social/');
                    $filename = $destinationPath . uniqid() . time() . '.jpg';

                    file_put_contents($filename, $in);
                    if (file_exists($filename)) {
                        $file = $filename;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            }
            $post = $publish_data['title'];
            $photo = $file;
            $caption = $post;
            $check = new \InstagramAPI\Instagram(false, false);
            $check->setUser($con['net_id'], $con['token']);
            $proxies = '';
            if ($proxies) {
                $proxies = explode('<br>', nl2br($proxies, false));
                $rand = rand(0, count($proxies));
                if (@$proxies[$rand]) {
                    $check->setProxy($proxies[$rand]);
                }
            }
            try {
                $check->login();
            } catch (Exception $e) {
                echo $e->getMessage();
            }
            try {
                $myphoto = $check->uploadPhoto($photo, $post);

                if ($myphoto) {
                    $moph = json_encode((array) $myphoto);
                    $str = explode('media_id":"', $moph);
                    if (@$str[1]) {
                        $rd = explode('"', $str[1]);
                        $img = $publish_data['image_url'];

                        /* *********** if publish draft posts *********** */
                        if ($publish_draft != null && $publish_draft != false) {
                            SocialconnectHelper::update_post('instagram', $con['company_id'], $body, $title, $url, $img, $video, $campign_id, 1);
                            return true;
                        }

                        SocialconnectHelper::store_posts('instagram', $con['company_id'], $post, $title, $url, $img, $video, $con['id'], 'instagram', $campign_id, $post_status);
                        return true;
                    }
                } else {
                    return false;
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        } else {
            $body = $publish_data['title'];
            if (isset($publish_data['image_url'])) {
                $img = $publish_data['image_url'];
            }
            if (isset($publish_data['video_url'])) {
                $video = $publish_data['video_url'];
            }
            if (isset($publish_data['link'])) {
                $url = $publish_data['link'];
            }
            SocialconnectHelper::store_posts('instagram', $con['company_id'], $body, $title, $url, $img, $video, $con['id'], 'instagram', $campign_id, $post_status);
        }
    }
    public static function publish_linkedIn_posts($publish_data, $company_id, $campign_id, $post_status, $publish_draft = null)
    {
        $con = false;
        $network_d = SocialconnectHelper::getNetworkDetails($company_id, 'linkedin');

        if ($network_d != false) {
            $con = $network_d->toArray();
        }

        try {
            $linked_cof = SocialconnectHelper::config_linkedin_app();
            $body = null;
            $title = null;
            $url = '';
            $img = null;
            $video = null;
            $length = 600;
            $msg = '';
            $length = 600;
            $msg = '';

            if ($post_status == 1 && $con != false) {
                $post = $publish_data['title'];
                if (@$publish_data['title']) {
                    $body = $post;
                }
                if (isset($publish_data['link'])) {
                    $short_url = SocialconnectHelper::getShortUlr($publish_data['link']);
                    $length = $length - strlen($short_url);
                    $msg .= ' ' . $short_url;
                    $url = $short_url;
                }
                if (isset($publish_data['video_url'])) {
                    $length = $length - strlen($publish_data['video_url']);
                    $msg .= ' ' . $publish_data['video_url'];
                    $video = $publish_data['video_url'];
                }
                if (isset($publish_data['image_url'])) {
                    $length = $length - strlen($publish_data['image_url']);
                    $msg .= ' ' . $publish_data['image_url'];
                }

                $msg = mb_substr($post, 0, $length) . $msg;
                $object = ['comment' => $msg,
                    'visibility' => [
                        'code' => 'anyone',
                    ],
                ];
                // try {

                $linked_cof->setAccessToken($con['token']);
                $result = $linked_cof->fetch('/people/~/shares?format=json', $object, \LinkedIn\LinkedIn::HTTP_METHOD_POST, ['Authorization: Bearer' . $con['token']]);
                SocialconnectHelper::store_posts('linkedin', $con['company_id'], $body, $title, $url, $img, $video, $con['id'], 'linkedin', $campign_id, $post_status);
                return true;
            } else {
                $body = $publish_data['title'];
                if (isset($publish_data['image_url'])) {
                    $img = $publish_data['image_url'];
                }
                if (isset($publish_data['video_url'])) {
                    $video = $publish_data['video_url'];
                }
                if (isset($publish_data['link'])) {
                    $url = $publish_data['link'];
                }

                /* *********** if publish draft posts *********** */
                if ($publish_draft != null && $publish_draft != false) {
                    SocialconnectHelper::update_post('linkedin', $con['company_id'], $body, $title, $url, $img, $video, $campign_id, 1);
                    return true;
                }
                SocialconnectHelper::store_posts('linkedin', $con['company_id'], $body, $title, $url, $img, $video, $con['id'], 'linkedin', $campign_id, $post_status);
                return true;
            }
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            SocialconnectHelper::log_error_post($campign_id, null, 'linkedin', $message);
            $previousException = $e->getPrevious();
            $logFile = 'errors.log';
            \Log::useDailyFiles(storage_path() . '/logs/' . $logFile);
            \Log::emergency("Company ID:" . $company_id . ", campign_id:" . $campign_id . ", Network:linkedin, Message-" . $message);
            return false;
            exit;
        }
        return false;
    }
    public static function add_si_campaign($company_id, $status, $title, $post_tumb, $schedule_time)
    {
        $created_time = new dateTime();
        $campaign = new SiCampaigns;
        $campaign->company_id = $company_id;
        $campaign->status = $status;
        $campaign->title_post = $title;
        $campaign->schedule_time = $schedule_time;
        $campaign->post_thumb = $post_tumb;
        $campaign->created_at = $created_time;
        $campaign->save();
        return $campaign->id;
    }

    public static function edit_si_campaign($campign_id, $company_id, $status, $title, $post_tumb, $schedule_time)
    {
        $updated_date = new dateTime();
        $update_data = array('title_post' => $title,
            'schedule_time' => $schedule_time,
            'post_thumb' => $post_tumb,
            'updated_at' => $updated_date,
            'status' => $status,
        );
        SiCampaigns::where('id', $campign_id)
            ->where('company_id', $company_id)
            ->update($update_data);
        return $campign_id;
    }

    public static function publishScheduledPosts()
    {
        $cmpgn_ids = [];
        $bcc_email = getenv('BCC_EMAIL');
        $all_companies = CompanyHelper::getAllCompanies();
        $scheduled_posts_now = array();
        foreach ($all_companies as $company) {
            $company_id = $company['id'];
            $scheduled_posts_n = SocialconnectHelper::getScheduledPostsNow($company_id);
            if ($scheduled_posts_n != false) {
                $scheduled_posts_now = array_merge($scheduled_posts_now, $scheduled_posts_n);
            }
        }

        $post_published = [];

        if ($scheduled_posts_now != false && count($scheduled_posts_now) > 0) {
            foreach ($scheduled_posts_now as $key => $post) {
                $campaign_id = $post['id'];
                $post_published[] = array('title_post' => $post['title_post'], 'company_id' => $post['company_id'], 'campaign_id' => $campaign_id);
                $posts = SocialconnectHelper::getSiCampignPosts($campaign_id);
                if ($posts != false) {
                    foreach ($posts as $key => $social_post) {
                        $network = $social_post['meta'][0]['network_name'];
                        $post_data = array(
                            'title' => $social_post['body'],
                            'link' => $social_post['url'],
                            'image_url' => $social_post['img'],
                            'video_url' => $social_post['video'],
                        );
                        SocialconnectHelper::post_social_data($network, $post_data, $campaign_id, 1, $post['company_id'], null);
                    }
                }
                SocialconnectHelper::updateCampaignStatus($campaign_id, 1);
            }
        }

        if (count($post_published) > 0) {
            foreach ($post_published as $pst) {
                $cmpny_id = $pst['company_id'];
                $title_p = $pst['title_post'];
                $company_details = CompanyHelper::getCompanyDetais($cmpny_id);
                $message = NotificationHelper::getNotificationMethod($cmpny_id, 'general_settings', 'SCHEDULE_POST_PUBLISH');
                $subject = NotificationHelper::getNotificationSubject($cmpny_id, 'general_settings', 'SCHEDULE_POST_PUBLISH');
                if ($message != false && $subject != false) {
                    $message = str_replace('{$post_title}', $title_p, $message);
                    $message = nl2br($message);
                    $app_from_email = app_from_email();

                    $data['company_information'] = $company_details;
                    $data['content_data'] = $message;
                    $bcc_email = getenv('BCC_EMAIL');
                    $data['company_information']['logo'] = 'img/mail_image_preview.png';
                    /**Send notification to admin on new post publish**/
                    $enable_social_publish_email = CompanySettingsHelper::getSetting($cmpny_id, "social_post_publish_email");
                    if ($enable_social_publish_email == 1) {
                        CompanySettingsHelper::sendCompanyEmailNotifcation($cmpny_id, $data, $subject, $bcc_email, 'emails.social_post_publish', $app_from_email, 'schedule_post');
                        \App\Classes\CompanySettingsHelper::sendCompanyEmailNotifcationLogs($cmpny_id, $message, $subject, $campaign_id, 'social_connect', 'SCHEDULE_POST_PUBLISH');
                    }

                    $logFile = 'scheduled_posts_published.log';
                    \Log::useDailyFiles(storage_path() . '/logs/' . $logFile);
                    \Log::info("Company ID:" . $cmpny_id . "-" . json_encode($pst));
                }
            }
        }
        return false;
    }

    public static function getScheduledPostsNow($company_id)
    {
        $dateTime = new dateTime();
        $time_fetch = $dateTime->format('Y-m-d H:i');
        $tz = '';
        $tz = CompanySettingsHelper::getSetting($company_id, 'timezone');
        if ($tz != '' && $tz != false) {
            $time_fetch = Carbon::createFromTimestamp(strtotime($time_fetch))
                ->timezone($tz)
                ->toDateTimeString();
        }
        $dateTime = new dateTime($time_fetch);
        $time_fetch = $dateTime->format('Y-m-d H:i:59');
        $posts_now = SiCampaigns::select('si_campaigns.*')
            ->leftJoin('si_post_errors', 'si_post_errors.campign_id', '=', 'si_campaigns.id')
            ->where('si_campaigns.schedule_time', '<=', $time_fetch)
            ->where('si_campaigns.company_id', $company_id)
            ->where('si_campaigns.status', 2)
            ->Wherenull('si_post_errors.id')
            ->get();
        if (count($posts_now) > 0) {
            return $posts_now->toArray();
        }
        return false;
    }

    public static function getSiCampignPosts($campign_id, $status_id = null)
    {
        if ($status_id == null) {
            $status_id = 2;
        }
        $posts = SiPosts::with('meta')->where('campaign_id', '=', $campign_id)->where('status', '=', $status_id)->get()->toArray();
        if (count($posts) > 0) {
            return $posts;
        }
        return false;
    }

    public static function updateCampaignStatus($cam_id, $status)
    {
        SiCampaigns::where('id', $cam_id)->update(['status' => $status]);
        return true;
    }

    public static function update_post($netw, $company_id, $body, $title, $url, $img, $video, $campign_id, $post_status)
    {
        $updated_at = new dateTime();
        $update_data = array(
            'body' => $body,
            'title' => $title,
            'url' => $url,
            'img' => $img,
            'video' => $video,
            'status' => $post_status,
            'updated_at' => $updated_at,
        );
        SiPosts::where('network', $netw)
            ->where('company_id', $company_id)
            ->where('campaign_id', $campign_id)
            ->update($update_data);

        $p = SiPosts::select('id')
            ->where('network', $netw)
            ->where('company_id', $company_id)
            ->where('campaign_id', $campign_id)
            ->first();
        if (count($p) > 0) {
            $post_id = $p->id;
            SiPostsMeta::where('post_id', $post_id)->delete();
            SocialconnectHelper::store_post_meta($post_id, $netw, $netw, $post_status);
        }
    }

    public static function getPostIdCampign($campignId, $networ_id)
    {
        $post_ids = SiPosts::select('id')
            ->where('network', $networ_id)
            ->where('campaign_id', $campignId)
            ->first();
        if (count($post_ids) > 0) {
            return $post_ids->id;
        }
        return false;
    }

    public static function getTwitterTrends()
    {
        $network_details = SocialconnectHelper::getNetworkDetails(1, 'twitter')->toArray();
        $twitter_app_id = AppOptionsHelper::getOptionValue('twitter_app_id');
        $twitter_app_secret = AppOptionsHelper::getOptionValue('twitter_app_secret');
        try {
            $connection = new TwitterOAuth($twitter_app_id, $twitter_app_secret, $network_details['token'], $network_details['secret']);
            $tweets = $connection->get("trends/place", array("id" => 1));
            return $tweets;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getTwitterSearch($q)
    {
        $network_details = SocialconnectHelper::getNetworkDetails(1, 'twitter')->toArray();
        $twitter_app_id = AppOptionsHelper::getOptionValue('twitter_app_id');
        $twitter_app_secret = AppOptionsHelper::getOptionValue('twitter_app_secret');
        try {
            $connection = new TwitterOAuth($twitter_app_id, $twitter_app_secret, $network_details['token'], $network_details['secret']);
            $tweets = $connection->get("search/tweets", array("q" => $q, 'result_type' => 'recent', 'include_entities' => true, 'count' => 2));
            print_r($tweets);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getHashTags()
    {
        $full_url = "https://api.ritekit.com/v1/search/trending/";
        $response = Curl::to($full_url)
            ->asJson(true)
            ->get();

        return (array) $response['tags'];
    }

    public static function getHashTagStats($tag)
    {
        $full_url = "https://api.ritekit.com/v1/stats/basic/" . $tag;
        $response = Curl::to($full_url)
            ->asJson()
            ->get();
        if (isset($response->stats)) {
            return $response->stats;
        }
        return false;
    }

    public static function storeHashTag($company_id, $tag_info)
    {
        $time_now = new dateTime();
        $hashtags = new Hashtag;
        $hashtags->company_id = $company_id;
        $hashtags->tag = $tag_info->tag;
        $hashtags->tweets = $tag_info->tweets;
        $hashtags->exposure = $tag_info->exposure;
        $hashtags->retweets = $tag_info->retweets;
        $hashtags->images = $tag_info->images;
        $hashtags->links = $tag_info->links;
        $hashtags->mentions = $tag_info->mentions;
        $hashtags->color = $tag_info->color;
        $hashtags->created_at = $time_now;
        $hashtags->updated_at = $time_now;
        $hashtags->save();
        return $hashtags->id;
    }

    public static function getStoredHashTags($company_id)
    {
        $hashtags = Hashtag::select('tag', 'tweets', 'retweets', 'exposure', 'links', 'photos', 'mentions', 'color')
            ->where('company_id', $company_id)
            ->get()
            ->toArray();
        if (count($hashtags) > 0) {
            return $hashtags;
        }
        return false;
    }

    public static function checkTagExists($tag, $company_id)
    {
        $hashtags = Hashtag::where('company_id', $company_id)->where('tag', $tag)->count();
        if ($hashtags > 0) {
            return true;
        }
        return false;
    }

    public static function getLatestNews($query)
    {
        $token = AppOptionsHelper::getOptionValue('webhose_token');
        $full_url = "http://webhose.io/filterWebContent?token=" . $token . "&format=json&ts=1497429025162&sort=relevancy&q=$query%20site_type%3Anews%20language%3Aenglish";
        $response = Curl::to($full_url)
            ->asJson(true)
            ->get();

        $out = [];
        $i = 0;
        foreach ($response['posts'] as $key => $item) {
            $title = $item['thread']['title'];
            $text = null;
            $image = null;
            if (isset($item['thread']['main_image'])) {
                $image = $item['thread']['main_image'];
            }
            if (isset($item['text'])) {
                $text = $item['text'];
            }
            if (isset($item['thread']['url'])) {
                $url = $item['thread']['url'];
            }
            $out[] = array(
                'title' => $title,
                'link' => $url,
                'image' => $image,
                'description' => $text,
            );
            if ($i == 19) {
                break;
            }
            $i++;
        }
        return $out;
    }

    public static function getLatesImages($query)
    {
        $google_cse_api_key = AppOptionsHelper::getOptionValue('google_cse_api_key');
        $google_cse_cx = AppOptionsHelper::getOptionValue('google_cse_cx');
        $full_url = "https://www.googleapis.com/customsearch/v1?key=" . $google_cse_api_key . "&cx=" . $google_cse_cx . "&q=" . $query . "&searchType=image&imgSize=large";
        $response = Curl::to($full_url)
            ->asJson(true)
            ->get();

        $out = [];
        if (isset($response['items'])) {
            foreach ($response['items'] as $key => $item) {
                $title = $item['title'];
                $text = null;
                $image = null;
                $url = null;
                if (isset($item['link'])) {
                    $image = $item['link'];
                }
                $out[] = array(
                    'title' => $title,
                    'link' => $url,
                    'image' => $image,
                    'description' => $text,
                );
            }
        }

        return $out;
    }

    public static function initializeYouTube()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $analytics_key = GoogleanalyticsHelper::getAnalyticsFilePath();

        // Create and configure a new client object.
        $client = new \Google_Client();
        $client->setApplicationName("Hello Analytics Reporting");
        $client->setAuthConfig($analytics_key);
        $client->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
        $client->setScopes(['https://www.googleapis.com/auth/youtube.force-ssl']);
        $service = new \Google_Service_YouTube($client);
        return $service;
    }

    public static function searchVideoListByKeyword($query)
    {
        $service = SocialconnectHelper::initializeYouTube();
        $part = 'snippet';
        $params = array('q' => $query, 'type' => '', 'maxResults' => 20);
        $params = array_filter($params);
        $response = $service->search->listSearch(
            $part,
            $params
        );
        $videos = $response->items;
        foreach ($videos as $key => $video) {
            $title = null;
            $url = null;
            $image = null;
            $text = null;

            if (isset($video->snippet->title)) {
                $title = $video->snippet->title;
            }

            if (isset($video->snippet->thumbnails->high)) {
                $image = $video->snippet->thumbnails->high->url;
            }
            if (isset($video->id->videoId)) {
                $url = "https://www.youtube.com/watch?v=" . $video->id->videoId;
            }

            if (isset($video->snippet->description)) {
                $text = $video->snippet->description;
            }

            $out[] = array(
                'title' => $title,
                'video_link' => $url,
                'image' => $image,
                'description' => $text,
            );
        }
        return $out;
    }

    public static function facebookPostInsights($post_id)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $network_details = SocialconnectHelper::getNetworkDetails($company_id, 'facebook_pages')->toArray();
        $fb_app_id = AppOptionsHelper::getOptionValue('facebook_app_id');
        $fb_app_secret = AppOptionsHelper::getOptionValue('facebook_app_secret');
        $token = $network_details['token'];
        $fb = SocialconnectHelper::config_fb_profile($fb_app_id, $fb_app_secret, $token);

        $request = $fb->request(
            'GET',
            '/' . $post_id . '/insights',
            array(
                'metric' => 'post_impressions_unique,post_consumptions,post_reactions_like_total',
            )
        );
        // Send the request to Graph
        try {
            $post_imprestions = [];
            $response = $fb->getClient()->sendRequest($request);
            $graphEdge = $response->getGraphEdge();
            foreach ($graphEdge as $graphNode) {
                $post_imprestions[] =
                array($graphNode['name'] => $graphNode['values'][0]['value']);
            }
            return $post_imprestions;
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }
    }

    /* Save Queue Schedule */
    public static function saveQueueSchedule($company_id, $days_in, $times_in)
    {
        $is_exits = SiQueueSchedule::where('company_id', $company_id)->count();
        $days = null;
        $times = null;

        if (count($days_in) > 0) {
            $days = implode(", ", $days_in);
        }

        if (count($times_in) > 0) {
            $times = implode(", ", $times_in);
        }

        $time_now = new dateTime();
        if ($is_exits > 0) {
            $array_update = array('days' => $days, 'post_times' => $times, 'updated_at' => $time_now);
            SiQueueSchedule::where('company_id', $company_id)->update($array_update);
            return true;
        } else {
            $schedule_time = new SiQueueSchedule;
            $schedule_time->days = $days;
            $schedule_time->company_id = $company_id;
            $schedule_time->post_times = $times;
            $schedule_time->created_at = $time_now;
            $schedule_time->save();
            return $schedule_time->id;
        }
    }

    public static function getCompanyPostQueueSchedule($company_id)
    {
        $out = [];
        $company_queue_settings = SiQueueSchedule::where('company_id', $company_id)->first();
        if (count($company_queue_settings) > 0) {
            $days = explode(", ", $company_queue_settings->days);
            $times = explode(", ", $company_queue_settings->post_times);
            $out = array('company_id' => $company_id,
                'days' => $days,
                'times' => $times,
            );
        }
        return $out;
    }

    public static function is_time_queue_avail($company_id, $time, $day)
    {
        $query = SiQueueSchedule::where('company_id', '=', $company_id)
            ->where('days', 'like', '%' . $day . '%')
            ->where('post_times', 'like', '%' . $time . '%')
            ->count();
        if ($query > 0) {
            return true;
        }
        return false;
    }

    public static function get_next_queue_post($company_id)
    {
        $posts_now = SiCampaigns::where('status', 3)
            ->where('company_id', $company_id)
            ->limit(1)
            ->get()
            ->toArray();
        if (count($posts_now) > 0) {
            return $posts_now;
        }
        return false;
    }

    public static function getShortUlr($longUrl)
    {
        /*$googl_api_key = AppOptionsHelper::getOptionValue('googl_short_url_key');
        $api_url = "https://www.googleapis.com/urlshortener/v1/url?key=".$googl_api_key;
        $request_para = array('longUrl'=>$longUrl);
        $response = Curl::to($api_url)
        ->withData( $request_para )
        ->asJson()
        ->post();
        if(isset($response->id)){return $response->id;};*/
        return $longUrl;
    }

    public static function publishQueuePosts()
    {
        $date_time = new dateTime();
        $time_now = $date_time->format("Y-m-d H:i:s");
        $time = $time_now;
        $compnies = CompanyHelper::getAllCompanies();
        $queued_posts_now = array();
        foreach ($compnies as $key => $company) {
            $company_id = $company['id'];
            $tz = CompanySettingsHelper::getSetting($company_id, 'timezone');
            if ($tz != '' && $tz != false) {
                $time = Carbon::createFromTimestamp(strtotime($time_now))
                    ->timezone($tz)
                    ->toDateTimeString();
            }
            $now_post_time = date('H:i', strtotime($time));
            $now_post_day = date('l', strtotime($time));
            $can_send_post = SocialconnectHelper::is_time_queue_avail($company_id, $now_post_time, $now_post_day);

            if ($can_send_post) {
                $que_posts = SocialconnectHelper::get_next_queue_post($company_id);
                if ($que_posts != false) {
                    $queued_posts_now = array_merge($queued_posts_now, $que_posts);
                }
            }
        }

        /* Publish Posts */
        $post_published = [];
        if ($queued_posts_now != false && count($queued_posts_now) > 0) {
            foreach ($queued_posts_now as $key => $post) {
                $campaign_id = $post['id'];
                $post_published[] = array('title_post' => $post['title_post'], 'company_id' => $post['company_id'], 'campaign_id' => $campaign_id);
                $posts = SocialconnectHelper::getSiCampignPosts($campaign_id, 3);

                if ($posts != false) {
                    foreach ($posts as $key => $social_post) {
                        $network = $social_post['meta'][0]['network_name'];
                        $post_data = array(
                            'title' => $social_post['body'],
                            'link' => $social_post['url'],
                            'image_url' => $social_post['img'],
                            'video_url' => $social_post['video'],
                        );
                        SocialconnectHelper::post_social_data($network, $post_data, $campaign_id, 1, $post['company_id'], null);
                    }
                }
                SocialconnectHelper::updateCampaignStatus($campaign_id, 1);
            }
        }

        if (count($post_published) > 0) {
            foreach ($post_published as $pst) {
                $cmpny_id = $pst['company_id'];
                $title_p = $pst['title_post'];
                $company_details = CompanyHelper::getCompanyDetais($cmpny_id);
                $emails_array = array();
                $message = NotificationHelper::getNotificationMethod($cmpny_id, 'general_settings', 'QUEUED_POST_PUBLISH');
                $subject = NotificationHelper::getNotificationSubject($cmpny_id, 'general_settings', 'QUEUED_POST_PUBLISH');

                if ($message != false && $subject != false) {
                    $message = str_replace('{$post_title}', $title_p, $message);
                    $message = nl2br($message);
                    $app_from_email = app_from_email();

                    $data['company_information'] = $company_details;
                    $data['content_data'] = $message;

                    $bcc_email = getenv('BCC_EMAIL');
                    $data['company_information']['logo'] = 'img/mail_image_preview.png';
                    /**On publish queue post notify admin**/
                    $enable_social_publish_email = CompanySettingsHelper::getSetting($cmpny_id, "social_post_publish_email");
                    if ($enable_social_publish_email == 1) {
                        CompanySettingsHelper::sendCompanyEmailNotifcation($cmpny_id, $data, $subject, $bcc_email, 'emails.social_post_publish', $app_from_email);
                        \App\Classes\CompanySettingsHelper::sendCompanyEmailNotifcationLogs($cmpny_id, $message, $subject, $campaign_id, 'social_connect', 'QUEUED_POST_PUBLISH');
                        $logFile = 'queued_posts_published.log';
                        \Log::useDailyFiles(storage_path() . '/logs/' . $logFile);
                        \Log::info("Company ID:" . $cmpny_id . "-" . json_encode($pst));
                    }
                }
            }
        }
    }

    public static function deleteCompanyNetworkProfile($company_id, $network_name)
    {
        SiNetwork::where(array('company_id' => $company_id, 'network_name' => $network_name))->delete();
        return true;
    }

    public static function SaveSocialCode($code)
    {
        $company_id = $_SESSION['company_id'];
    }

    public static function GetErrorNetwork($campaign_id, $network_id)
    {
        $errors = SiPostError::select(['id', 'message'])->where(['campign_id' => $campaign_id, 'network_id' => $network_id])->first();
        if (count($errors) > 0) {
            return $errors;
        }
        return false;
    }
}
