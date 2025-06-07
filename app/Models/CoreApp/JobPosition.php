<?php
namespace App\Models\CoreApp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobPosition extends Model
{
    use SoftDeletes;
    protected $table = "job_positions";
    protected $fillable = [
        'company_id',
        'departement_id',
        'name',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }
}
