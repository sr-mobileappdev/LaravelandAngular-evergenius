<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SiPostsMeta extends Model
{
    use SoftDeletes;
	protected $table = 'si_posts_meta';
}
