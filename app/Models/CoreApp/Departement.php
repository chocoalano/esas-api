<?php
namespace App\Models\CoreApp;

use App\Models\UserApp\UserEmploye;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Departement extends Model
{
    use SoftDeletes;
    protected $table = "departements";
    protected $fillable = [
        'company_id',
        'name',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function timeWorks()
    {
        return $this->hasMany(TimeWork::class);
    }

    public function jobPositions()
    {
        return $this->hasMany(JobPosition::class);
    }

    public function jobLevels()
    {
        return $this->hasMany(JobLevel::class);
    }

    public function employees()
    {
        return $this->hasMany(UserEmploye::class);
    }
}
