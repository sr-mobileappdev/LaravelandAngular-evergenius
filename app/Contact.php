<?php


namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use HipsterJazzbo\Landlord\BelongsToTenants;

class Contact extends Model
{
    use BelongsToTenants;
    use SoftDeletes;
    public $tenantColumns = ['company_id'];

    protected $fillable = [
        'first_name', 'last_name', 'email', 'mobile_number', 'mobile_number', 'gender', 'birth_date', 'address', 'gender', 'state', 'zip_code', 'notes', 'is_existing', 'insurance_Id', 'insurance_provider', 'insurance_group', 'insurance_phone', 'country', 'additional_information',
    ];

    public function appointments()
    {
        return $this->hasMany('App\Appointment', 'contact_id');
    }

    public function status()
    {
        return $this->hasOne('App\EmListStatus');
    }

    public function NotSeen()
    {
        return $this->hasMany('App\SmsRecord', 'contact_id', 'id')->whereNotNull('not_seen');
    }

    public function lead()
    {
        return $this->BelongsTo('App\Lead', 'contact_id');
    }

    public function company()
    {
        return $this->BelongsTo('App\Company', 'company_id', 'id');
    }

    public function getFirstNameAttribute($value)
    {
        if (empty($value)) {
            $value = "";
            return $value;
        }
        return $value;
    }

    public function getLastNameAttribute($value)
    {
        if (empty($value)) {
            $value = "";
            return $value;
        }
        return $value;
    }

    public function contacts()
    {
        return $this->belongsTo('App\Contact', 'contact_id', 'id');
    }

    public function assignlist(){
        return $this->hasMany('App\EmNewsletterContacts','contact_id');
    }
}
