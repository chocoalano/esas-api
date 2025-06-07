<?php
namespace App\Models\CoreApp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;
    protected $table = "companies";
    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'radius',
        'full_address',
    ];

    protected $casts = [
        'latitude' => 'double',
        'longitude' => 'double',
        'radius' => 'integer',
    ];

    public function departments()
    {
        return $this->hasMany(Departement::class);
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

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
