<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class CompanySetting extends Model
{
    protected $fillable = ['name','value'];
    protected $casts = [
        'name' => 'string'
    ];
    use SoftDeletes;
    public function company()
    {
        return $this->belongsTo('App\Company', 'id');
    }

}