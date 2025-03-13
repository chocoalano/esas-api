<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FcmModel extends Model
{
    protected $table = "fcm_models";
    protected $fillable = [
        "user_id",
        "device_token",
    ];
}
