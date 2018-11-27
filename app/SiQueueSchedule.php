<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class SiQueueSchedule extends Model
{
    use SoftDeletes;
	protected $table = 'si_queue_schedule';
}
