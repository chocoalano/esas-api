<?php
namespace App\Models\CoreApp;

use Illuminate\Database\Eloquent\Model;

class JobPosition extends Model
{
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
