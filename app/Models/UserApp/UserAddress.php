<?php
namespace App\Models\UserApp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    protected $table = "user_address";
    protected $fillable = [
        'user_id',
        'identity_type',
        'identity_numbers',
        'province',
        'city',
        'citizen_address',
        'residential_address',
    ];

    public const IDENTITYY_TYPE = [
        'ktp' => 'KTP',
        'sim' => 'SIM',
        'passport' => 'Passport'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
