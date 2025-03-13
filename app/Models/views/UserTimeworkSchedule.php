<?php

namespace App\Models\views;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\CoreApp\TimeWork;
use App\Models\CoreApp\Departement;

class UserTimeworkSchedule extends Model
{
    use HasFactory;

    protected $table = 'user_timework_schedules';

    protected $fillable = [
        'user_id',
        'time_work_id',
        'work_day',
        'name',
        'nip',
        'departement_id',
        'departement',
        'timework',
        'timein',
        'timeout',
    ];

    protected $casts = [
        'work_day' => 'date',
        'timein' => 'H:i:s',
        'timeout' => 'H:i:s',
    ];

    // Relasi ke model User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke model TimeWork
    public function timeWork()
    {
        return $this->belongsTo(TimeWork::class, 'time_work_id');
    }

    // Relasi ke model Departement
    public function departement()
    {
        return $this->belongsTo(Departement::class, 'departement_id');
    }
}
