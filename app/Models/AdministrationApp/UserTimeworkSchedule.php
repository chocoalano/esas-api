<?php
namespace App\Models\AdministrationApp;

use App\Models\CoreApp\Company;
use App\Models\CoreApp\TimeWork;
use App\Models\User;
use App\Models\UserApp\UserEmploye;
use Illuminate\Database\Eloquent\Model;

class UserTimeworkSchedule extends Model
{
    protected $table = "user_timework_schedules";
    protected $fillable = [
        'user_id',
        'time_work_id',
        'work_day',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function timework()
    {
        return $this->belongsTo(TimeWork::class, 'time_work_id', 'id');
    }

    public function employee()
    {
        return $this->hasOneThrough(
            UserEmploye::class,    // Model yang ingin diakses
            User::class,    // Model perantara
            'id',   // Foreign key di tabel `users` (tabel perantara)
            'user_id',      // Foreign key di tabel `posts`
            'user_id',           // Primary key di tabel `countries`
            'id'            // Primary key di tabel `users`
        );
    }
    public function company()
    {
        return $this->hasOneThrough(
            Company::class,    // Model yang ingin diakses
            User::class,    // Model perantara
            'company_id',   // Foreign key di tabel `users` (tabel perantara)
            'id',      // Foreign key di tabel `posts`
            'user_id',           // Primary key di tabel `countries`
            'id'            // Primary key di tabel `users`
        );
    }
}
