<?php

namespace App\Classes;

use App\AppOption;

class AppOptionsHelper
{
    public static function getOptionValue($option_key)
    {
        $data = AppOption::where('option_key', $option_key)->first();
        if (count($data) > 0) {
            return $data->option_value;
        } else {
            return false;
        }
    }

    public static function updateOptionValue($option_key, $val)
    {
        AppOption::where('option_key', $option_key)->update(['option_value' => $val]);
        return true;
    }
}
