<?php

namespace App\Models\views;

use Illuminate\Database\Eloquent\Model;

class EmployeView extends Model
{
    protected $table = "users_view";
    protected $fillable = [
        'company_id',
        'name',
        'nip',
        'email',
        'email_verified_at',
        'password',
        'avatar',
        'status',
        'remember_token',
        'company',
        'company_lat',
        'company_long',
        'company_radius',
        'company_address',
        'basic_salary',
        'payment_type',
        'bank_name',
        'bank_number',
        'bank_holder',
        'phone',
        'placebirth',
        'datebirth',
        'gender',
        'blood',
        'marital_status',
        'religion',
        'identity_type',
        'identity_numbers',
        'province',
        'city',
        'citizen_address',
        'residential_address',
        'departement_id',
        'job_position_id',
        'job_level_id',
        'approval_line_id',
        'approval_manager_id',
        'join_date',
        'sign_date',
        'resign_date',
        'departement',
        'position',
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'basic_salary' => 'decimal:2',
        'datebirth' => 'date',
        'join_date' => 'date',
        'sign_date' => 'date',
        'resign_date' => 'date',
        'company_lat' => 'double',
        'company_long' => 'double',
        'company_radius' => 'int',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
