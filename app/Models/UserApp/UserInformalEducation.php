<?php
namespace App\Models\UserApp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserInformalEducation extends Model
{
    protected $table = "user_informal_educations";
    protected $fillable = [
        'user_id',
        'institution',
        'start',
        'finish',
        'type',
        'duration',
        'status',
        'certification',
    ];
    protected $casts = [
        'certification' => 'boolean',
    ];
    public const TYPE = ['day' => 'Day', 'year' => 'Year', 'month' => 'Month'];
    public const STATUS = ['passed' => 'Passed', 'not-passed' => 'Not Passed', 'in-progress' => 'In Progress'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
