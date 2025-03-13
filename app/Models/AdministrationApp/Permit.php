<?php

namespace App\Models\AdministrationApp;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'permit_numbers',
        'permit_type_id',
        'user_timework_schedule_id',
        'timein_adjust',
        'timeout_adjust',
        'current_shift_id',
        'adjust_shift_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'notes',
        'file',
    ];

    /**
     * Relationship: Permit belongs to a PermitType.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function permitType()
    {
        return $this->belongsTo(PermitType::class, 'permit_type_id', 'id');
    }

    /**
     * Relationship: Permit belongs to many approvals.
     */
    public function approvals()
    {
        return $this->hasMany(PermitApprove::class);
    }

    /**
     * Relationship: Permit belongs to a UserTimeworkSchedule.
     */
    public function userTimeworkSchedule()
    {
        return $this->belongsTo(UserTimeworkSchedule::class);
    }
}
