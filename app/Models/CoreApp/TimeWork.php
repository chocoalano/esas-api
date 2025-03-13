<?php
namespace App\Models\CoreApp;

use App\Models\AdministrationApp\UserTimeworkSchedule;
use Illuminate\Database\Eloquent\Model;

class TimeWork extends Model
{
    protected $table = "time_workes";
    protected $fillable = [
        'company_id',
        'departemen_id',
        'name',
        'in',
        'out',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function department()
    {
        return $this->belongsTo(Departement::class, 'departemen_id', 'id');
    }

    public function userSchedules()
    {
        return $this->hasMany(UserTimeworkSchedule::class);
    }
}
