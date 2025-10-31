<?php

namespace App\Models\OHD;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'artime_users';

    protected $connection = 'ohdmysql';

    protected $primaryKey = 'user_id';

    public $timestamps = false;
}
