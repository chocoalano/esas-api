<?php
namespace App\Models\UserApp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserWorkExperience extends Model
{
    protected $table = "user_work_experiences";
    protected $fillable = [
        'user_id',
        'company_name',
        'position',
        'start',
        'finish',
        'certification',
    ];

    protected $casts = [
        'certification' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
