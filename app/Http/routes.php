<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::group(['middleware' => ['web','cors']], function () {
    Route::get('/', 'AngularController@serveApp');
    Route::get('/unsupported-browser', 'AngularController@unsupported');
    Route::get('user/verify/{verificationCode}', ['uses' => 'Auth\AuthController@verifyUserEmail']);
    Route::get('auth/{provider}', ['uses' => 'Auth\AuthController@redirectToProvider']);
    Route::get('auth/{provider}/callback', ['uses' => 'Auth\AuthController@handleProviderCallback']);
    Route::get('/api/authenticate/user', 'Auth\AuthController@getAuthenticatedUser');

});

Route::get('/delete-duplicate','ContactController@deleteDuplicateEntries');

Route::post('/rest-hook/infusionsoft/add-contact', 'InfusionsoftController@addContactHook');
Route::post('/sendgrid/webhook','EmailMarketingController@SendGridWebHookResponse');
/*Un subscribed*/
Route::get('/unsubscribe/{uuid}','EmailMarketingController@Unsubscribe');
Route::get('/unsubscribe/contact/{baseid}','EmailMarketingController@getUnsubscribeContact');
/* For Add Appointment Via Web */
Route::post('/sms_callback', 'NotificationController@updateSmsStatus');
Route::get('/call/incoming/call-back', 'CallsController@incomingCallBack');
Route::post('/sms/incoming/call-back', 'SmsController@callBackSmsStore');
Route::get('/api/social/callback/{netrwok?}', 'SocialController@Callback'); // For call back values from social netwroks
Route::get('social/user/connect/instagram', 'SocialController@connectInstagram');
Route::post('social/user/connect/instagram', 'SocialController@saveConnectInstagram');
Route::post('/api/notifiction/test', 'NotificationController@sms_notification');
Route::post('/api/test/social', 'SocialController@publishScheduledPosts');
Route::post('/api/notifiction/yext', 'ReviewController@pull_yext_reviews');
Route::get('/funnel/cron','FunnelController@runFunnelCron');
Route::get('/sms-boradcast/cron','SmsBroadcastController@ConfigureCron');
Route::get('/email-marketing/campaign-mail-job','EmailMarketingController@campaignMailJob');


/* Google Analytics */
Route::get('/api/google/login-google-accont/{apikey?}', 'GoogleanalyticsContoller@getConnectGoogleLoginUrl'); // For call back values from social netwroks
Route::get('/api/google/callback/', 'GoogleanalyticsContoller@getGoogleTokenCallack');
Route::post('/cron/LeadsUpdate', 'InfusionsoftController@fetchContactDetails');

Route::get('/api/infusionsoft-connect-callback', 'SuperAdminController@CallBackInfusionsoft');
Route::get('/api/test/d', 'NotificationController@send_notification');

/*Twillio response*/
Route::get('/twiml/incoming-call/{apikey?}', 'CompanyController@getCompanyResponseTwiml');


/* External Scripts URL */
Route::get('/scripts/widgets/form', 'ExtrernalScriptsController@webFormRequest');
Route::get('/scripts/widgets/form-preview', 'ExtrernalScriptsController@webFormPreview');
Route::post('/scripts/widgets/form/review-store', 'ExtrernalScriptsController@storeWidgetReviews');
Route::post('/scripts/widgets_3/form/review-store', 'ExtrernalScriptsController@storeWidgetThreeReviews');
Route::get('/scripts/widgets/display-reviews', 'ExtrernalScriptsController@webReviewPreviewRequest');
Route::get('/scripts/widgets/review-preview', 'ExtrernalScriptsController@reviewPreviewIframe');


Route::post('/scripts/upload-image', 'ExtrernalScriptsController@postUploadImage');
Route::post('/scripts/upload-video', 'ExtrernalScriptsController@postUploadVideo');
Route::post('/scripts/upload-audio', 'ExtrernalScriptsController@postUploadAudio');

Route::get('/api/isactive', 'CompanyController@isCompanyActive');

Route::get('/work_summary', 'LeadsController@dailyWorkSummaryMail');
Route::get('/email_work_summary/{id}', 'LeadsController@generateDailyWorkEmailView');
Route::get('/email-marketing/template-preview/{template_id}', 'EmailMarketingController@templatePreview');
Route::get('/api/email-template/{slug_template}', 'ExtrernalScriptsController@getEmailTemplate');

Route::get('/honest-doctor-impoort', 'HonestdoctorController@importHdFromTemp');


Route::group(['middleware' => ['Apicheck','cors']], function () { 
    Route::post('/api/appointment/add', 'AppointmentController@webAppointmentAdd');
    Route::get('/api/appointment/available-slots', 'AppointmentController@webavailableSlots');
    Route::post('/api/review/add', 'ReviewController@addReview');
    Route::get('/api/insurance-companies','AppointmentController@insuranceCompanies');
    Route::get('/api/review/company', 'ReviewController@getComapanyReviews');

    Route::get('/api/services/click-funnel', function(){
        return response()->success('failed');;
    });
});
//Route::post('/webhooks/click_funnel', 'ServiceController@addOppertunityClickFunnel');
/* Global web API */
Route::group(['middleware' => ['AuthApi','cors']], function () {
    Route::post('/api/lead/add-new', 'LeadsController@postCreateNewLead');
    Route::get('/api/lead/services', 'LeadsController@getLeadServices');
    Route::get('/api/list/', 'EmailMarketingController@getSubscriptionList');
    Route::post('/api/list/subscribe/{subscription_id}', 'EmailMarketingController@postSubscribed');
    Route::post('/api/services/opportunity', 'ServiceController@addOppertunity');
    Route::post('/api/services/new-agent', 'ServiceController@addNewAgentUser');
    
});

Route::post('/api/list/subscription/{subscription_id}', 'EmailMarketingController@postSubscribed');

$api->group(['middleware' => ['api','cors']], function ($api) {
    $api->controller('auth', 'Auth\AuthController');
    // Password Reset Routes...
    $api->post('auth/password/email', 'Auth\PasswordResetController@sendResetLinkEmail');
    $api->get('auth/password/verify', 'Auth\PasswordResetController@verify');
    $api->post('auth/password/reset', 'Auth\PasswordResetController@reset');
});

$api->group(['middleware' => ['api','cors', 'api.auth','MultiTenant']], function ($api) {
    $api->get('users/me', 'UserController@getMe');
    $api->put('users/me', 'UserController@putMe');
});

$api->group(['middleware' => ['api','cors','MultiTenant', 'api.auth']], function ($api) {
    $api->controller('users', 'UserController');
});

$api->group(['middleware' => ['api','cors','MultiTenant', 'api.auth', 'role:admin.super|admin.user|super.call.center|super.admin.agent']], function ($api) {
    $api->controller('superadmin', 'SuperAdminController');
});

$api->group(['middleware' => ['api','MultiTenant', 'api.auth']], function ($api) {
    $api->controller('users', 'UserController');
    $api->controller('contacts', 'ContactController');
    $api->controller('appointments', 'AppointmentController');
    $api->controller('celendars', 'CelendarController');
    $api->controller('company', 'CompanyController');
    $api->controller('calls', 'CallsController');
    $api->controller('sms', 'SmsController');
    $api->controller('analytics', 'GoogleanalyticsContoller');
    $api->controller('dashboard', 'DashboardController');
    $api->controller('profilelisting', 'ProfilelistngController');
    $api->controller('recent-activity', 'RecentActivityController');
    $api->controller('mailchimp-analytics', 'MailchimpAnaytics');
    $api->controller('social', 'SocialController');
    $api->controller('reviews', 'ReviewController');
    $api->controller('perfectaudience', 'PerfectAudienceContoller');
    $api->controller('email-marketing', 'EmailMarketingController');
    $api->controller('funnel', 'FunnelController');
    $api->controller('conversation', 'ConversationController');
    $api->controller('leads', 'LeadsController');
    $api->controller('agentreports', 'AgentReportController');
    $api->controller('honestdoctor', 'HonestdoctorController');
    $api->controller('sms-broadcast', 'SmsBroadcastController');

});
Route::get('test', function () {
    // this checks for the event
    return view('test');
});

