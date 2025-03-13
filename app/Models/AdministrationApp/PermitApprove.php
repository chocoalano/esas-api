<?php

namespace App\Models\AdministrationApp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PermitApprove extends Model
{
    protected $fillable = [
        'permit_id',
        'user_id',
        'user_type',
        'user_approve',
        'notes',
    ];

    /**
     * Relationship: PermitApprove belongs to a Permit.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function permit()
    {
        return $this->belongsTo(Permit::class);
    }

    /**
     * Check if the permit is approved by a specific user type.
     */
    public function isApproved()
    {
        return $this->user_approve === 'y';
    }
}
