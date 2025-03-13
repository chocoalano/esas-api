<?php

namespace App\Models\AdministrationApp;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermitDetail extends Model
{
    use HasFactory;

    // Nama tabel
    protected $table = 'permit_detail_view';

    // Kolom yang bisa diisi (mass assignable)
    protected $fillable = [
        'permit_numbers',
        'user_id',
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
        'type',
        'is_payed',
        'approve_line',
        'approve_manager',
        'approve_hr',
        'with_file',
        'company',
        'user_name',
        'nip',
        'departement',
        'position',
        'levels',
    ];

    // Tipe data yang di-cast
    protected $casts = [
        'timein_adjust' => 'datetime:H:i:s',
        'timeout_adjust' => 'datetime:H:i:s',
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'is_payed' => 'boolean',
        'approve_line' => 'boolean',
        'approve_manager' => 'boolean',
        'approve_hr' => 'boolean',
        'with_file' => 'boolean',
    ];

    // Relasi dengan model User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function permitType()
    {
        return $this->belongsTo(PermitType::class);
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
