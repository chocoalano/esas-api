<?php
namespace App\Models\UserApp;

use App\Models\CoreApp\Departement;
use App\Models\CoreApp\JobLevel;
use App\Models\CoreApp\JobPosition;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserEmploye extends Model
{
    protected $table = "user_employes";
    protected $fillable = [
        'user_id',
        'departement_id',
        'job_position_id',
        'job_level_id',
        'approval_line_id',
        'approval_manager_id',
        'join_date',
        'sign_date',
        'resign_date',
        'bank_name',
        'bank_number',
        'bank_holder',
        'saldo_cuti',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function approval_line()
    {
        return $this->belongsTo(User::class);
    }
    public function approval_manager()
    {
        return $this->belongsTo(User::class);
    }
    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    public function jobPosition()
    {
        return $this->belongsTo(JobPosition::class);
    }

    public function jobLevel()
    {
        return $this->belongsTo(JobLevel::class);
    }
}
