<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasFactory;

    protected $table = 'personal_access_tokens';

    protected $fillable = [
        'tokenable_id',
        'tokenable_type',
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the parent tokenable model (user or other entity).
     */
    public function tokenable(): MorphTo
    {
        return $this->morphTo();
    }
}
