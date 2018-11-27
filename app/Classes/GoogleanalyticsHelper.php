<?php
namespace App\Classes;

use App\Classes\CompanySettingsHelper;

class GoogleanalyticsHelper
{
    public static function getAnalyticsProfileID($company_id)
    {
        return CompanySettingsHelper::getSetting($company_id, 'analytics_profile_id');
    }

    public static function getAnalyticsFilePath()
    {
        return storage_path() . '/app/analytics_key.json';
    }
}
