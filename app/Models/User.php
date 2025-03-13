<?php

namespace App\Models;

use App\Models\AdministrationApp\UserAttendance;
use App\Models\AdministrationApp\UserTimeworkSchedule;
use App\Models\CoreApp\Company;
use App\Models\UserApp\UserAddress;
use App\Models\UserApp\UserDetail;
use App\Models\UserApp\UserEmploye;
use App\Models\UserApp\UserFamily;
use App\Models\UserApp\UserFormalEducation;
use App\Models\UserApp\UserInformalEducation;
use App\Models\UserApp\UserSalary;
use App\Models\UserApp\UserWorkExperience;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'nip',
        'name',
        'email',
        'password',
        'avatar',
        'status',
        'device_id',
    ];

    public const STATUS = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'resign' => 'Resign',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function timeworkSchedules()
    {
        return $this->hasMany(UserTimeworkSchedule::class);
    }

    public function attendances()
    {
        return $this->hasMany(UserAttendance::class);
    }

    public function details()
    {
        return $this->hasOne(UserDetail::class);
    }
    public function address()
    {
        return $this->hasOne(UserAddress::class);
    }

    public function salaries()
    {
        return $this->hasOne(UserSalary::class);
    }

    public function families()
    {
        return $this->hasMany(UserFamily::class);
    }

    public function formalEducations()
    {
        return $this->hasMany(UserFormalEducation::class);
    }

    public function informalEducations()
    {
        return $this->hasMany(UserInformalEducation::class);
    }

    public function workExperiences()
    {
        return $this->hasMany(UserWorkExperience::class);
    }

    public function employee()
    {
        return $this->hasOne(UserEmploye::class);
    }
}
