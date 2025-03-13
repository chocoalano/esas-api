<?php

namespace App\Models\AdministrationApp;

use Illuminate\Database\Eloquent\Model;

class PermitType extends Model
{
    protected $fillable = [
        'type',
        'is_payed',
        'approve_line',
        'approve_manager',
        'approve_hr',
        'show_mobile',
        'with_file',
    ];

    /**
     * Relationship: PermitType has many Permits.
     */
    public function permits()
    {
        return $this->hasMany(Permit::class);
    }

    protected $casts = [
        'is_payed' => 'boolean',
        'approve_line' => 'boolean',
        'approve_manager' => 'boolean',
        'approve_hr' => 'boolean',
        'show_mobile' => 'boolean',
        'with_file' => 'boolean',
    ];
}
