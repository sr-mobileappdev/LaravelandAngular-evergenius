<?php 
namespace App\Traits;

use App\CompanySetting;
use Cache;
use DB;

trait companySettings {

    // get setting value
    public function hello(){
        print_r($this);
    }
    public function getSetting($name)
    { 
        $settings = $this->getCache();
        print_r($settings);
        //$value = array_get($settings, $name);
        //return ($value !== '') ? $value : NULL;
    }

    // create-update setting
    public function setSetting($name, $value)
    {
        $this->storeSetting($name, $value);
        $this->setCache();
    }

    // create-update multiple settings at once
    public function setSettings($data = [])
    {
        foreach($data as $name => $value)
        {
            $this->storeSetting($name, $value);
        }
        $this->setCache();
    }

    private function storeSetting($name, $value)
    {
        $record = CompanySetting::where(['user_id' => $this->id, 'name' => $name])->first();
        if($record)
        {
            $record->value = $value;
            $record->save();
        } else {
            $data = new CompanySetting(['name' => $name, 'value' => $value]);
            $this->settings()->save($data);
        }
    }

    private function getCache()
    {
        if (Cache::has('company_settings_' . $this->id))
        {
            return Cache::get('company_settings_' . $this->id);
        }
        return $this->setCache();
    }

    private function setCache()
    {
        if (Cache::has('company_settings_' . $this->id))
        {
            Cache::forget('company_settings_' . $this->id);
        }
        $settings = $this->settings->lists('value','name');
        Cache::forever('company_settings_' . $this->id, $settings);
        return $this->getCache();
    }

}