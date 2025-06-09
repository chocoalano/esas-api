<?php

namespace App\Models\AdministrationApp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LogUserAttendance extends Model
{
    protected $table='log_user_attendances';
    protected $fillable=['user_id', 'type'];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
