<?php
namespace App\Models\AdministrationApp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserAttendance extends Model
{
    protected $table = "user_attendances";
    protected $fillable = [
        'user_id',
        'user_timework_schedule_id',
        'time_in',
        'time_out',
        'type_in',
        'type_out',
        'lat_in',
        'lat_out',
        'long_in',
        'long_out',
        'image_in',
        'image_out',
        'status_in',
        'status_out',
        'created_by',
        'updated_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
                $model->created_by = $model->user_id;
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }

    public const STATUS = [
        'late' => 'Late',
        'unlate' => 'Unlate',
        'normal' => 'Normal',
    ];
    public const TYPE_ATTENDANACE = [
        'qrcode' => 'Late',
        'face-device' => 'Unlate',
        'face-geolocation' => 'Normal',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function getUserDeptAttribute()
    {
        return DB::table('users as u')
            ->join('user_employes as ue', 'u.id', '=', 'ue.user_id')
            ->join('departements as d', 'ue.departement_id', '=', 'd.id')
            ->where('u.id', $this->user_id)
            ->pluck('d.name')
            ->first() ?? 'Unknown';
    }
    public function schedule()
    {
        return $this->belongsTo(UserTimeworkSchedule::class, 'user_timework_schedule_id', 'id');
    }
    public function qrPresenceTransactions()
    {
        return $this->hasOne(QrPresenceTransaction::class, 'user_attendance_id');
    }
}
