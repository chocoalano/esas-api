<?php

namespace App\Models\views;

use App\Models\CoreApp\Departement;
use App\Models\CoreApp\JobLevel;
use App\Models\CoreApp\JobPosition;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AttendanceView extends Model
{
    protected $table = 'attendance_view';
    protected $fillable = [
        'id',
        'user_id',
        'name',
        'nip',
        'avatar',
        'departement_id',
        'job_position_id',
        'job_level_id',
        'approval_line_id',
        'approval_manager_id',
        'join_date',
        'sign_date',
        'departement',
        'position',
        'level',
        'work_day',
        'shiftname',
        'in',
        'out',
        'user_timework_schedule_id',
        'time_in',
        'lat_in',
        'long_in',
        'image_in',
        'status_in',
        'time_out',
        'lat_out',
        'long_out',
        'image_out',
        'status_out',
        'created_at',
        'updated_at',
    ];

    public const STATUS = [
        'late' => 'Late',
        'unlate' => 'Unlate',
        'normal' => 'Normal',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function departement_relation()
    {
        return $this->belongsTo(Departement::class, 'departement_id', 'id');
    }
    public function lvl_relation()
    {
        return $this->belongsTo(JobLevel::class, 'job_level_id', 'id');
    }
    public function position_relation()
    {
        return $this->belongsTo(JobPosition::class, 'job_position_id', 'id');
    }
}
