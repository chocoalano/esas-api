<?php
namespace App\Models\UserApp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserFamily extends Model
{
    protected $table = "user_families";
    protected $fillable = [
        'user_id',
        'fullname',
        'relationship',
        'birthdate',
        'marital_status',
        'job',
    ];

    public const MARITAL_STATUS = [
        'single' => 'Single',
        'married' => 'Married',
        'widow' => 'Widow',
        'widower' => 'Widower'
    ];
    public const RELATIONSHIP = [
        'wife' => 'Wife',
        'husband' => 'Husband',
        'mother' => 'Mother',
        'father' => 'Father',
        'brother' => 'Brother',
        'sister' => 'Sister',
        'child' => 'Child'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
