<?php
namespace App\Models\UserApp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    protected $table = "user_details";
    protected $fillable = [
        'user_id',
        'phone',
        'placebirth',
        'datebirth',
        'gender',
        'blood',
        'marital_status',
        'religion',
    ];

    public const GENDER = [
        'm' => 'Man',
        'w' => 'Woman'
    ];
    public const BLOOD_TYPE = [
        'a' => 'A',
        'b' => 'B',
        'o' => 'O',
        'ab' => 'AB',
    ];
    public const MARITAL_STATUS = [
        'single' => 'Single',
        'married' => 'Married',
        'widow' => 'Widow',
        'widower' => 'Widower',
    ];
    public const RELIGION = [
        'islam' => 'Islam',
        'protestan' => 'Protestant',
        'khatolik' => 'Catholic',
        'hindu' => 'Hindu',
        'buddha' => 'Buddha',
        'khonghucu' => 'Khonghucu',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
