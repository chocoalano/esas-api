<?php
namespace App\Models\UserApp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserSalary extends Model
{
    protected $table = "user_salaries";
    protected $fillable = [
        'user_id',
        'basic_salary',
        'payment_type',
    ];

    public const PAYMENT_TYPE = [
        'Monthly' => 'Monthly',
        'Weekly' => 'Weekly',
        'Daily' => 'Daily'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
